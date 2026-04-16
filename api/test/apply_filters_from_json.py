#!/usr/bin/env python3
"""
Применяет итоговые filter_config из JSON (keys + labels на категорию).

Файл по умолчанию: api/test/final-category-filters.json (рядом со скриптом).
Переменные окружения: API_BASE_URL, API_ADMIN_USER, API_ADMIN_PASSWORD

Пример на сервере (из корня репозитория или из api/):

  python3 api/test/apply_filters_from_json.py

  FILTERS_JSON=/path/to/final-category-filters.json python3 api/test/apply_filters_from_json.py

  python3 api/test/apply_filters_from_json.py --dry-run
"""
from __future__ import annotations

import argparse
import json
import os
import sys
from pathlib import Path
from typing import Any, Dict, List, Tuple

_SCRIPT_DIR = Path(__file__).resolve().parent
if str(_SCRIPT_DIR) not in sys.path:
    sys.path.insert(0, str(_SCRIPT_DIR))

from seed_product_specs_catalog import load_category_slug_map, put_category_filter_config  # noqa: E402
from smoke_common import env_client  # noqa: E402


def default_json_path() -> Path:
    env = os.getenv("FILTERS_JSON")
    if env:
        return Path(env)
    return _SCRIPT_DIR / "final-category-filters.json"


def load_config(path: Path) -> Dict[str, Any]:
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)


def iter_categories(data: Dict[str, Any]) -> List[Tuple[str, Dict[str, Any]]]:
    out: List[Tuple[str, Dict[str, Any]]] = []
    for slug, payload in data.items():
        if slug.startswith("_"):
            continue
        if not isinstance(payload, dict):
            continue
        keys = payload.get("keys")
        labels = payload.get("labels")
        if not isinstance(keys, list) or not isinstance(labels, dict):
            continue
        out.append(
            (
                slug,
                {
                    "keys": [str(k) for k in keys if isinstance(k, str)],
                    "labels": {str(k): str(v) for k, v in labels.items() if isinstance(k, str)},
                },
            )
        )
    return out


def main() -> None:
    parser = argparse.ArgumentParser(description="PUT filter_config категорий из JSON")
    parser.add_argument(
        "--json",
        type=Path,
        default=None,
        help="Путь к JSON (по умолчанию FILTERS_JSON или api/test/final-category-filters.json)",
    )
    parser.add_argument("--dry-run", action="store_true", help="Только список slug, без API")
    args = parser.parse_args()
    path = args.json or default_json_path()
    if not path.is_file():
        print(f"Файл не найден: {path}", file=sys.stderr)
        sys.exit(1)

    data = load_config(path)
    items = iter_categories(data)
    if not items:
        print("В JSON нет категорий с keys/labels", file=sys.stderr)
        sys.exit(1)

    if args.dry_run:
        for slug, cfg in items:
            print(slug, len(cfg["keys"]), "keys")
        return

    client = env_client()
    client.login()
    slug_map = load_category_slug_map(client)

    report: List[Dict[str, Any]] = []
    for slug, cfg in items:
        if slug not in slug_map:
            report.append({"slug": slug, "status": "skipped", "reason": "нет категории с таким slug"})
            continue
        cid = slug_map[slug][0]
        put_category_filter_config(client, cid, cfg["keys"], cfg["labels"])
        report.append({"slug": slug, "status": "ok", "keys": len(cfg["keys"])})

    print(json.dumps(report, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
