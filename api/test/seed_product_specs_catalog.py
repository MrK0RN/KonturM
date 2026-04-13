#!/usr/bin/env python3
"""
Загружает design/product-specs-by-category.json и создаёт через API:
- категории (лист = категория, display_mode=products_only);
- товары с technical_specs из характеристик;
- filter_config на каждой категории (keys + labels по ключам характеристик).
- поле price_rub из JSON (после merge_prices_into_product_specs.py) передаётся в API как price.

Повторный запуск идемпотентен: категории и товары с тем же slug пропускаются.

Обновить только цены у уже созданных товаров:
  python3 api/test/seed_product_specs_catalog.py --sync-prices

Переменные окружения (как в smoke_common):
  API_BASE_URL, API_ADMIN_USER, API_ADMIN_PASSWORD

Запуск из корня репозитория:
  python3 api/test/seed_product_specs_catalog.py

или из api/:
  python3 test/seed_product_specs_catalog.py
"""
from __future__ import annotations

import argparse
import hashlib
import json
import math
import re
import sys
from pathlib import Path
from typing import Any, Dict, List, Set, Tuple
from urllib.parse import urlparse

# Запуск: из корня репо `python3 api/test/...` или из `api/` — добавляем каталог test в PYTHONPATH
_SCRIPT_DIR = Path(__file__).resolve().parent
if str(_SCRIPT_DIR) not in sys.path:
    sys.path.insert(0, str(_SCRIPT_DIR))

from smoke_common import SmokeClient, env_client

# Транслитерация для slug категорий (латиница, без внешних зависимостей)
_RU2LAT = str.maketrans(
    {
        "а": "a",
        "б": "b",
        "в": "v",
        "г": "g",
        "д": "d",
        "е": "e",
        "ё": "e",
        "ж": "zh",
        "з": "z",
        "и": "i",
        "й": "j",
        "к": "k",
        "л": "l",
        "м": "m",
        "н": "n",
        "о": "o",
        "п": "p",
        "р": "r",
        "с": "s",
        "т": "t",
        "у": "u",
        "ф": "f",
        "х": "h",
        "ц": "ts",
        "ч": "ch",
        "ш": "sh",
        "щ": "sch",
        "ъ": "",
        "ы": "y",
        "ь": "",
        "э": "e",
        "ю": "yu",
        "я": "ya",
    }
)


def _repo_root() -> Path:
    return Path(__file__).resolve().parents[2]


def default_json_path() -> Path:
    return _repo_root() / "design" / "product-specs-by-category.json"


def slugify(text: str, max_len: int = 180) -> str:
    s = text.lower().translate(_RU2LAT)
    s = re.sub(r"[^a-z0-9]+", "-", s)
    s = re.sub(r"-+", "-", s).strip("-")
    if not s:
        s = "cat"
    return s[:max_len]


def normalize_technical_specs(raw: Dict[str, Any]) -> Dict[str, Any]:
    """
    Согласовано с api/test/smoke_seed.py и CategoryQueryService:
    - строковые значения — в виде [str] (для фильтров по @>);
    - числа (int/float) — скаляры;
    - bool — скаляр.
    """
    out: Dict[str, Any] = {}
    for key, val in raw.items():
        if val is None:
            continue
        if isinstance(val, bool):
            out[key] = val
        elif isinstance(val, int) and not isinstance(val, bool):
            out[key] = val
        elif isinstance(val, float):
            if math.isnan(val):
                continue
            out[key] = int(val) if val == int(val) else val
        elif isinstance(val, str):
            out[key] = [val] if val else []
        elif isinstance(val, list):
            out[key] = val
        else:
            out[key] = [str(val)]
    return out


def stable_product_slug(category_slug: str, name: str, characteristics: Dict[str, Any]) -> str:
    payload = json.dumps(
        {"c": category_slug, "n": name, "ch": characteristics},
        ensure_ascii=False,
        sort_keys=True,
    )
    h = hashlib.sha256(payload.encode("utf-8")).hexdigest()[:14]
    base = f"spec-{category_slug}"[:200]
    return f"{base}-{h}"[:255]


def fetch_all_hydra_members(
    client: SmokeClient, path: str, public_read: bool = False
) -> List[Dict[str, Any]]:
    items: List[Dict[str, Any]] = []
    sep = "&" if "?" in path else "?"
    next_path = f"{path}{sep}itemsPerPage=500"
    while next_path:
        if public_read:
            status, payload = client.call("GET", next_path, token="")
        else:
            status, payload = client.call("GET", next_path)
        if status != 200:
            raise RuntimeError(f"GET {next_path} failed: {status} {payload}")
        member = payload.get("hydra:member", [])
        if isinstance(member, list):
            items.extend(member)
        view = payload.get("hydra:view") or {}
        nxt = view.get("hydra:next")
        if not nxt:
            break
        p = urlparse(nxt)
        next_path = p.path + ("?" + p.query if p.query else "")
    return items


def load_category_slug_map(client: SmokeClient) -> Dict[str, Tuple[str, str]]:
    """slug -> (id, name)"""
    out: Dict[str, Tuple[str, str]] = {}
    for row in fetch_all_hydra_members(client, "/api/categories", public_read=True):
        if not isinstance(row, dict):
            continue
        s = row.get("slug")
        cid = row.get("id")
        name = row.get("name") or ""
        if s and cid:
            out[str(s)] = (str(cid), str(name))
    return out


def resolve_category_slug(name: str, slug_map: Dict[str, Tuple[str, str]]) -> str:
    """Устойчивый slug; при коллизии с другой категорией — суффикс из хэша (до свободного slug)."""
    base = slugify(name)
    cand = base
    n = 0
    while True:
        if cand not in slug_map:
            return cand
        _eid, existing_name = slug_map[cand]
        if existing_name == name:
            return cand
        n += 1
        suf = hashlib.sha256(f"{name}:{n}".encode("utf-8")).hexdigest()[:8]
        cand = f"{base}-{suf}"[:255]


def ensure_category(
    client: SmokeClient,
    slug: str,
    name: str,
    description: str,
    slug_map: Dict[str, Tuple[str, str]],
) -> Tuple[str, bool]:
    """Возвращает (id, created). Обновляет slug_map при создании."""
    if slug in slug_map:
        eid, ename = slug_map[slug]
        if ename == name:
            return eid, False
    body = {
        "name": name,
        "slug": slug,
        "description": description,
        "display_mode": "products_only",
        "aggregate_products": True,
    }
    status, resp = client.call(
        "POST",
        "/api/categories",
        body,
        content_type="application/ld+json",
    )
    if status not in (200, 201):
        raise RuntimeError(f"POST /api/categories {slug!r} failed: {status} {resp}")
    cid = str(resp.get("id"))
    slug_map[slug] = (cid, name)
    return cid, True


def put_category_filter_config(
    client: SmokeClient,
    category_id: str,
    keys: List[str],
    labels: Dict[str, str],
) -> None:
    # GET публичный; PUT требует полного JSON-LD (см. Api Platform + Doctrine).
    status, current = client.call("GET", f"/api/categories/{category_id}", token="")
    if status != 200:
        raise RuntimeError(f"GET category {category_id} failed: {status} {current}")
    body = {k: v for k, v in current.items() if not k.startswith("hydra:")}
    body["filter_config"] = {"keys": keys, "labels": labels}
    status, resp = client.call(
        "PUT",
        f"/api/categories/{category_id}",
        body,
        content_type="application/ld+json",
    )
    if status not in (200, 204):
        raise RuntimeError(f"PUT filter_config {category_id} failed: {status} {resp}")


def put_product_price(client: SmokeClient, product_id: str, price_rub: float) -> None:
    status, cur = client.call("GET", f"/api/products/{product_id}", token="")
    if status != 200:
        raise RuntimeError(f"GET product {product_id} failed: {status} {cur}")
    body = {k: v for k, v in cur.items() if not k.startswith("hydra:")}
    body["price"] = f"{float(price_rub):.2f}"
    status, resp = client.call(
        "PUT",
        f"/api/products/{product_id}",
        body,
        content_type="application/ld+json",
    )
    if status not in (200, 204):
        raise RuntimeError(f"PUT product {product_id} price failed: {status} {resp}")


def sync_prices_from_json(client: SmokeClient, json_path: Path) -> Dict[str, int]:
    """Выставляет price в API по slug, совпадающему с расчётом сидера."""
    with open(json_path, "r", encoding="utf-8") as f:
        data = json.load(f)
    categories_in = data.get("categories") or []
    existing = fetch_all_hydra_members(client, "/api/products", public_read=False)
    by_slug: Dict[str, str] = {}
    for p in existing:
        if isinstance(p, dict) and p.get("slug") and p.get("id"):
            by_slug[str(p["slug"])] = str(p["id"])

    slug_map = load_category_slug_map(client)
    updated = 0
    missing_slug = 0
    for block in categories_in:
        cat_name = (block.get("category") or "").strip()
        if not cat_name:
            continue
        products = block.get("products") or []
        slug = resolve_category_slug(cat_name, slug_map)
        for prod in products:
            pr = prod.get("price_rub")
            if pr is None:
                continue
            orig = (prod.get("original_name") or prod.get("name") or "").strip()
            if not orig:
                continue
            chars = prod.get("characteristics") or {}
            if not isinstance(chars, dict):
                chars = {}
            pslug = stable_product_slug(slug, orig, chars)
            pid = by_slug.get(pslug)
            if not pid:
                missing_slug += 1
                continue
            put_product_price(client, pid, float(pr))
            updated += 1
    return {"prices_updated": updated, "products_not_found_by_slug": missing_slug}


def main() -> None:
    parser = argparse.ArgumentParser(description="Seed categories/products from product-specs-by-category.json")
    parser.add_argument(
        "--json-path",
        type=Path,
        default=None,
        help="Путь к JSON (по умолчанию: design/product-specs-by-category.json в корне репо)",
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Только показать план, без запросов к API",
    )
    parser.add_argument(
        "--sync-prices",
        action="store_true",
        help="Только обновить цены у существующих товаров из price_rub в JSON",
    )
    args = parser.parse_args()
    json_path = args.json_path or default_json_path()
    if not json_path.is_file():
        print(f"Файл не найден: {json_path}", file=sys.stderr)
        sys.exit(1)

    if args.sync_prices:
        client = env_client()
        client.login()
        stats = sync_prices_from_json(client, json_path)
        print(json.dumps(stats, ensure_ascii=False, indent=2))
        return

    with open(json_path, "r", encoding="utf-8") as f:
        data = json.load(f)

    categories_in = data.get("categories") or []
    if not categories_in:
        print("В JSON нет categories", file=sys.stderr)
        sys.exit(1)

    if args.dry_run:
        for block in categories_in:
            print(block.get("category"), "→", len(block.get("products") or []), "товаров")
        return

    client = env_client()
    client.login()

    existing_products = fetch_all_hydra_members(client, "/api/products", public_read=False)
    existing_slugs: Set[str] = {
        str(p.get("slug"))
        for p in existing_products
        if isinstance(p, dict) and p.get("slug")
    }

    slug_map = load_category_slug_map(client)
    stats = {
        "categories_created": 0,
        "categories_existing": 0,
        "products_created": 0,
        "products_skipped": 0,
        "categories_filters_updated": 0,
    }

    for block in categories_in:
        cat_name = (block.get("category") or "").strip()
        if not cat_name:
            continue
        products = block.get("products") or []
        slug = resolve_category_slug(cat_name, slug_map)

        desc = f"Каталог: {cat_name}"
        cat_id, created = ensure_category(
            client,
            slug,
            cat_name,
            desc,
            slug_map,
        )
        if created:
            stats["categories_created"] += 1
        else:
            stats["categories_existing"] += 1

        all_keys: Set[str] = set()
        for prod in products:
            name = (prod.get("name") or "").strip()
            if not name:
                continue
            orig = (prod.get("original_name") or name).strip()
            chars = prod.get("characteristics") or {}
            if not isinstance(chars, dict):
                chars = {}
            specs = normalize_technical_specs(chars)
            for k in specs.keys():
                if k != "has_verification":
                    all_keys.add(k)

            pslug = stable_product_slug(slug, orig, chars)
            if pslug in existing_slugs:
                stats["products_skipped"] += 1
                continue

            price_val = prod.get("price_rub")
            price_api = f"{float(price_val):.2f}" if price_val is not None else None

            body = {
                "category_id": cat_id,
                "name": name,
                "slug": pslug,
                "article": None,
                "description": None,
                "technical_specs": specs,
                "price": price_api,
                "stock_status": "on_order",
                "manufacturing_time": None,
                "has_verification": False,
            }
            status, resp = client.call(
                "POST",
                "/api/products",
                body,
                content_type="application/ld+json",
            )
            if status not in (200, 201):
                raise RuntimeError(f"POST product {pslug!r} failed: {status} {resp}")
            existing_slugs.add(pslug)
            stats["products_created"] += 1

        keys_sorted = sorted(all_keys, key=lambda x: (x.lower(), x))
        labels = {k: k for k in keys_sorted}
        if keys_sorted:
            put_category_filter_config(client, cat_id, keys_sorted, labels)
            stats["categories_filters_updated"] += 1
        else:
            put_category_filter_config(client, cat_id, [], {})
            stats["categories_filters_updated"] += 1

    print(json.dumps(stats, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
