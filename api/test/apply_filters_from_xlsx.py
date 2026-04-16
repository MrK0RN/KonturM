#!/usr/bin/env python3
"""
Читает Excel с колонками «Название параметра» / «Значение» по группам товаров (строка 0 — заголовок
группы, строка 1 — шапка, далее пары имя/значение; пустое имя означает продолжение предыдущего параметра).

Для каждой группы находит категории в API по имени, запрашивает discover-ключи характеристик,
сопоставляет названия из файла с реальными ключами technical_specs и выставляет filter_config
(keys + labels), как в админке и seed_product_specs_catalog.py.

Переменные окружения (как в smoke_common):
  API_BASE_URL, API_ADMIN_USER, API_ADMIN_PASSWORD

Пример:
  python3 api/test/apply_filters_from_xlsx.py --xlsx /path/to/file.xlsx
  python3 api/test/apply_filters_from_xlsx.py --xlsx ... --dry-run
  python3 api/test/apply_filters_from_xlsx.py --xlsx ... --only-group Мерники
"""
from __future__ import annotations

import argparse
import difflib
import json
import re
import sys
from pathlib import Path
from typing import Any, Dict, List, Optional, Sequence, Tuple

_SCRIPT_DIR = Path(__file__).resolve().parent
if str(_SCRIPT_DIR) not in sys.path:
    sys.path.insert(0, str(_SCRIPT_DIR))

from seed_product_specs_catalog import (  # noqa: E402
    fetch_all_hydra_members,
    load_category_slug_map,
    put_category_filter_config,
)
from smoke_common import SmokeClient, env_client  # noqa: E402

try:
    import pandas as pd
except ImportError as e:
    raise SystemExit("Нужен пакет pandas (и openpyxl для xlsx): pip install pandas openpyxl") from e


def normalize_label(s: str) -> str:
    t = str(s).strip().lower().replace("ё", "е")
    t = re.sub(r"\s+", " ", t)
    return t


def _spaceless(s: str) -> str:
    return re.sub(r"\s+", "", normalize_label(s))


def _key_by_normalized(hint: str, keys: Sequence[str]) -> Optional[str]:
    nh = normalize_label(hint)
    for k in keys:
        if normalize_label(k) == nh:
            return k
    return None


def best_match_excel_to_key(
    excel_label: str,
    discovered_keys: Sequence[str],
    *,
    hint_key: Optional[str] = None,
) -> Optional[str]:
    """Сопоставляет подпись из Excel с ключом из technical_specs (discover)."""
    keys = list(discovered_keys)
    if not keys:
        return None
    n_excel = normalize_label(excel_label)
    if not n_excel:
        return None

    if hint_key:
        found = _key_by_normalized(hint_key, keys)
        if found:
            return found

    # Точное совпадение после нормализации
    for k in keys:
        if normalize_label(k) == n_excel:
            return k

    # Без пробелов (др. «Длина, м» vs «Длина,м»)
    se = _spaceless(excel_label)
    for k in keys:
        if se and se == _spaceless(k):
            return k

    # has_verification — часто не в Excel, но допускаем явное имя
    if n_excel in ("has_verification", "наличие поверки", "поверка"):
        if "has_verification" in keys:
            return "has_verification"

    best_k: Optional[str] = None
    best_score = 0.72
    for k in keys:
        nk = normalize_label(k)
        ratio = difflib.SequenceMatcher(None, n_excel, nk).ratio()
        if ratio > best_score:
            best_score = ratio
            best_k = k
    return best_k


# Подсказки, когда названия в Excel и ключи в каталоге расходятся (см. design/product-specs-by-category.json).
GROUP_LABEL_HINTS: Dict[str, Dict[str, str]] = {
    "Мерники": {
        "Тип": "Исполнение",
    },
    "Рулетки": {
        "Точность": "класс точности",
        "матерал ленты": "тип стали ленты",
        "Длина, м": "Длина,м",
        "тип ленты": "тип стали ленты",
        # «груз, кг» — resolve_gruz_kg() (лот vs кольцо)
    },
}


def resolve_material_label(excel_label: str, keys: Sequence[str]) -> Optional[str]:
    """«Материал изготовления» в Excel → «тип стали» или «материал», что есть в категории."""
    if normalize_label(excel_label) != normalize_label("Материал изготовления"):
        return None
    ks = set(keys)
    if "тип стали" in ks:
        return "тип стали"
    if "материал" in ks:
        return "материал"
    return None


def resolve_sliv_label(excel_label: str, keys: Sequence[str]) -> Optional[str]:
    """«Слив» в Excel → любой ключ technical_specs с «слив» (тип слива / Тип слива)."""
    if normalize_label(excel_label) != normalize_label("Слив"):
        return None
    for k in keys:
        if "слив" in normalize_label(k):
            return k
    return None


def resolve_gruz_kg(excel_label: str, keys: Sequence[str]) -> Optional[str]:
    """«груз, кг» в Excel → масса лота или масса рулетки, в зависимости от категории."""
    if normalize_label(excel_label) != normalize_label("груз, кг"):
        return None
    if "Масса лота, кг" in keys:
        return "Масса лота, кг"
    if "Масса рулетки, кг" in keys:
        return "Масса рулетки, кг"
    return None


def resolve_pogreshnost_label(excel_label: str, keys: Sequence[str]) -> Optional[str]:
    """«погрешность, %» / «погрешность,%» в Excel → фактический ключ в категории."""
    ex = normalize_label(excel_label)
    if "погрешность" not in ex:
        return None
    for k in keys:
        if "погрешность" in normalize_label(k):
            return k
    return None


# Названия из Excel → как в каталоге (литры вместо дм³, чтобы совпадало с technical_specs).
EXCEL_PARAM_ALIASES_BY_NORM: Dict[str, str] = {
    "объем в дм3": "Объем, л",
    "объем в дм³": "Объем, л",
}


def display_label_for_filter(api_key: str, excel_label: str) -> str:
    """Подписи на витрине: объём только в литрах; погрешность — как в ключе API (пробел перед %)."""
    if normalize_label(api_key) == normalize_label("Объем, л"):
        return "Объем, л"
    if "погрешность" in normalize_label(api_key):
        return api_key
    return excel_label


def parse_filters_by_group(xlsx_path: Path, sheet: str = "Лист1") -> Dict[str, List[str]]:
    """
    Возвращает словарь: заголовок группы (строка 0) -> упорядоченный список уникальных
    «Название параметра» для этого блока (две колонки на группу).
    """
    df = pd.read_excel(xlsx_path, sheet_name=sheet, header=None)
    if df.shape[1] < 2:
        return {}

    out: Dict[str, List[str]] = {}
    for c in range(0, df.shape[1], 2):
        title = df.iloc[0, c]
        if pd.isna(title) or str(title).strip() == "":
            continue
        h1 = df.iloc[1, c] if df.shape[0] > 1 else None
        if pd.isna(h1) or "название" not in str(h1).lower():
            # не похоже на блок фильтров
            continue

        group_name = str(title).strip()
        ordered: List[str] = []
        seen: set[str] = set()
        for r in range(2, len(df)):
            cell = df.iloc[r, c]
            if pd.isna(cell):
                continue
            name = str(cell).strip()
            if not name:
                continue
            name = EXCEL_PARAM_ALIASES_BY_NORM.get(normalize_label(name), name)
            if name in seen:
                continue
            seen.add(name)
            ordered.append(name)
        if ordered:
            out[group_name] = ordered
    return out


def group_matches_category(group_title: str, category_name: str) -> bool:
    g = group_title.lower().strip()
    n = category_name.lower()
    rules: List[Tuple[bool, bool]] = [
        (g.startswith("мерник") or "мерники" in g, "мерник" in n),
        ("метрошток" in g, "метрошток" in n),
        ("рулет" in g, "рулет" in n),
        ("пробоотбор" in g, "пробоотбор" in n),
        ("ареометр" in g, "ареометр" in n),
    ]
    for cond, match in rules:
        if cond:
            return match
    return False


def discover_keys(client: SmokeClient, slug: str, aggregate: bool) -> List[str]:
    q = "aggregate=true" if aggregate else "aggregate=false"
    # Без Accept: application/json Api Platform отдаёт hydra:Collection без поля keys
    status, payload = client.call(
        "GET",
        f"/api/categories/{slug}/filters/discover?{q}",
        extra_headers={"Accept": "application/json"},
    )
    if status != 200:
        raise RuntimeError(f"discover failed for {slug!r}: {status} {payload}")
    keys = payload.get("keys") if isinstance(payload, dict) else None
    if not isinstance(keys, list):
        return []
    return [str(k) for k in keys if isinstance(k, str)]


def run(
    xlsx_path: Path,
    sheet: str,
    dry_run: bool,
    only_group: Optional[str],
    aggregate: bool,
) -> None:
    groups = parse_filters_by_group(xlsx_path, sheet=sheet)
    if not groups:
        print("В файле не найдено блоков с «Название параметра»", file=sys.stderr)
        sys.exit(1)

    if only_group:
        if only_group not in groups:
            print(f"Группа {only_group!r} не найдена. Есть: {list(groups.keys())}", file=sys.stderr)
            sys.exit(1)
        groups = {only_group: groups[only_group]}

    print(json.dumps({k: v for k, v in groups.items()}, ensure_ascii=False, indent=2))
    if dry_run:
        print("— dry-run: запросы к API не выполнялись")
        return

    client = env_client()
    client.login()
    slug_map = load_category_slug_map(client)

    report: List[Dict[str, Any]] = []
    for group_title, excel_labels in groups.items():
        matched_cats: List[Tuple[str, str]] = []
        for slug, (_cid, name) in slug_map.items():
            if group_matches_category(group_title, name):
                matched_cats.append((slug, name))
        matched_cats.sort(key=lambda x: x[1].lower())
        if not matched_cats:
            report.append(
                {
                    "group": group_title,
                    "status": "no_categories",
                    "message": "нет категорий по правилу сопоставления имени",
                }
            )
            continue

        for slug, cname in matched_cats:
            try:
                disc = discover_keys(client, slug, aggregate=aggregate)
            except RuntimeError as e:
                report.append({"group": group_title, "slug": slug, "name": cname, "error": str(e)})
                continue

            keys_out: List[str] = []
            labels: Dict[str, str] = {}
            unmatched: List[str] = []
            hints = GROUP_LABEL_HINTS.get(group_title, {})
            for lab in excel_labels:
                stripped = lab.strip()
                hint_key = hints.get(stripped)
                if hint_key is None:
                    hint_key = resolve_material_label(lab, disc)
                if hint_key is None:
                    hint_key = resolve_sliv_label(lab, disc)
                if hint_key is None:
                    hint_key = resolve_gruz_kg(lab, disc)
                if hint_key is None:
                    hint_key = resolve_pogreshnost_label(lab, disc)
                mk = best_match_excel_to_key(lab, disc, hint_key=hint_key)
                if mk is None:
                    unmatched.append(lab)
                    continue
                if mk not in keys_out:
                    keys_out.append(mk)
                    labels[mk] = display_label_for_filter(mk, lab)

            cid = slug_map[slug][0]
            if not keys_out and excel_labels:
                report.append(
                    {
                        "group": group_title,
                        "slug": slug,
                        "name": cname,
                        "status": "skipped",
                        "reason": "нет совпадений с discover или discover пустой — filter_config не менялся",
                        "unmatched_excel_labels": excel_labels,
                        "discover_keys_sample": disc[:25],
                    }
                )
                continue

            put_category_filter_config(client, cid, keys_out, labels)
            report.append(
                {
                    "group": group_title,
                    "slug": slug,
                    "name": cname,
                    "keys": keys_out,
                    "labels": labels,
                    "unmatched_excel_labels": unmatched,
                    "discover_keys_sample": disc[:15],
                }
            )

    print(json.dumps(report, ensure_ascii=False, indent=2))


def main() -> None:
    parser = argparse.ArgumentParser(description="Установка filter_config категорий из Excel")
    parser.add_argument(
        "--xlsx",
        type=Path,
        default=Path("/Users/admin/Downloads/Фильтр_с_характеристиками_по_группам_товаров.xlsx"),
        help="Путь к xlsx (по умолчанию — файл из задачи)",
    )
    parser.add_argument("--sheet", default="Лист1", help="Имя листа")
    parser.add_argument("--dry-run", action="store_true", help="Только разбор файла, без API")
    parser.add_argument("--only-group", default=None, help="Обработать только одну группу (как в строке 0)")
    parser.add_argument(
        "--no-aggregate",
        action="store_true",
        help="discover с aggregate=false (по умолчанию true, как в админке)",
    )
    args = parser.parse_args()
    if not args.xlsx.is_file():
        print(f"Файл не найден: {args.xlsx}", file=sys.stderr)
        sys.exit(1)

    run(
        args.xlsx,
        sheet=args.sheet,
        dry_run=args.dry_run,
        only_group=args.only_group,
        aggregate=not args.no_aggregate,
    )


if __name__ == "__main__":
    main()
