#!/usr/bin/env python3
"""
Создаёт через API: 7 родительских категорий (только подкатегории),
7 дочерних (только товары) и по 5 товаров в каждой дочерней.
Повторный запуск идемпотентен по slug (пропускает уже существующее).
"""
import json
import sys
from typing import Any, Dict, List, Tuple

from smoke_common import env_client

ROOT_SLUGS: List[Tuple[str, str]] = [
    ("seed7-root-1", "Тестовая группа 1"),
    ("seed7-root-2", "Тестовая группа 2"),
    ("seed7-root-3", "Тестовая группа 3"),
    ("seed7-root-4", "Тестовая группа 4"),
    ("seed7-root-5", "Тестовая группа 5"),
    ("seed7-root-6", "Тестовая группа 6"),
    ("seed7-root-7", "Тестовая группа 7"),
]

# подкатегория i привязана к root i
CHILDREN: List[Tuple[str, str, str]] = [
    ("seed7-sub-1", "Подкатегория товаров 1", "seed7-root-1"),
    ("seed7-sub-2", "Подкатегория товаров 2", "seed7-root-2"),
    ("seed7-sub-3", "Подкатегория товаров 3", "seed7-root-3"),
    ("seed7-sub-4", "Подкатегория товаров 4", "seed7-root-4"),
    ("seed7-sub-5", "Подкатегория товаров 5", "seed7-root-5"),
    ("seed7-sub-6", "Подкатегория товаров 6", "seed7-root-6"),
    ("seed7-sub-7", "Подкатегория товаров 7", "seed7-root-7"),
]

PRODUCTS_PER_LEAF = 5


def _fetch_categories_map(client) -> Dict[str, str]:
    status, payload = client.call("GET", "/api/categories")
    if status != 200:
        raise RuntimeError(f"GET /api/categories failed: {status} {payload}")
    return {
        item.get("slug"): item.get("id")
        for item in payload.get("hydra:member", [])
        if isinstance(item, dict) and item.get("slug")
    }


def _post_category(client, body: Dict[str, Any]) -> Tuple[int, Any]:
    return client.call(
        "POST",
        "/api/categories",
        body,
        content_type="application/ld+json",
    )


def ensure_roots(client, existing: Dict[str, str]) -> Dict[str, str]:
    for slug, name in ROOT_SLUGS:
        if existing.get(slug):
            continue
        status, resp = _post_category(
            client,
            {
                "name": name,
                "slug": slug,
                "description": f"Тест: родительская категория «{name}»",
                "display_mode": "subcategories_only",
                "aggregate_products": False,
            },
        )
        if status not in (200, 201):
            raise RuntimeError(f"POST root {slug} failed: {status} {resp}")
        existing[slug] = resp.get("id")
    return existing


def ensure_children(client, existing: Dict[str, str]) -> Dict[str, str]:
    changed = True
    while changed:
        changed = False
        for slug, name, parent_slug in CHILDREN:
            if existing.get(slug):
                continue
            parent_id = existing.get(parent_slug)
            if not parent_id:
                continue
            status, resp = _post_category(
                client,
                {
                    "name": name,
                    "slug": slug,
                    "description": f"Тест: подкатегория «{name}»",
                    "parent_id": parent_id,
                    "display_mode": "products_only",
                    "aggregate_products": True,
                },
            )
            if status not in (200, 201):
                raise RuntimeError(f"POST child {slug} failed: {status} {resp}")
            existing[slug] = resp.get("id")
            changed = True
    missing = [s for s, _, _ in CHILDREN if not existing.get(s)]
    if missing:
        raise RuntimeError(f"Не удалось создать подкатегории: {missing}")
    return existing


def ensure_products(client, existing: Dict[str, str]) -> int:
    status, payload = client.call("GET", "/api/products")
    if status != 200:
        raise RuntimeError(f"GET /api/products failed: {status} {payload}")
    existing_slugs = {
        item.get("slug")
        for item in payload.get("hydra:member", [])
        if isinstance(item, dict) and item.get("slug")
    }
    created = 0
    global_idx = 0
    prefixes = ["S7A", "S7B", "S7C", "S7D", "S7E", "S7F", "S7G"]
    for sub_idx, (sub_slug, _, _) in enumerate(CHILDREN):
        category_id = existing[sub_slug]
        prefix = prefixes[sub_idx]
        for i in range(1, PRODUCTS_PER_LEAF + 1):
            global_idx += 1
            slug = f"seed7-prod-{sub_slug}-{i}"
            if slug in existing_slugs:
                continue
            body = {
                "category_id": category_id,
                "name": f"Тестовый товар {sub_slug} №{i}",
                "slug": slug,
                "article": f"{prefix}-{7000 + global_idx}",
                "description": f"Автосид: товар {i} в «{sub_slug}»",
                "technical_specs": {
                    "batch": [f"seed7-{global_idx}"],
                    "slot": [str(i)],
                },
                "price": f"{1200 + global_idx * 50:.2f}",
                "stock_status": "in_stock" if global_idx % 2 else "on_order",
                "manufacturing_time": "5-7 дней",
                "gost_number": f"ГОСТ-SEED7-{global_idx}",
                "has_verification": bool(global_idx % 2),
            }
            status, resp = client.call(
                "POST",
                "/api/products",
                body,
                content_type="application/ld+json",
            )
            if status not in (200, 201):
                raise RuntimeError(f"POST product {slug} failed: {status} {resp}")
            created += 1
            existing_slugs.add(slug)
    return created


def main() -> None:
    client = env_client()
    client.login()
    existing = _fetch_categories_map(client)
    ensure_roots(client, existing)
    ensure_children(client, existing)
    products_created = ensure_products(client, existing)
    out = {
        "roots": [s for s, _ in ROOT_SLUGS],
        "children": [s for s, _, _ in CHILDREN],
        "products_created_this_run": products_created,
        "category_ids": {s: existing[s] for s, _, _ in CHILDREN},
    }
    print(json.dumps(out, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    try:
        main()
    except Exception as exc:
        print(str(exc), file=sys.stderr)
        sys.exit(1)
