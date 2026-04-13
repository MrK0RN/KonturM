#!/usr/bin/env bash
# Полная загрузка каталога: цены из documents/ → JSON → API (товары, категории, фильтры, цены).
# Нужны: запущенный API, переменные API_BASE_URL, API_ADMIN_USER, API_ADMIN_PASSWORD (см. api/test/smoke_common.py).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT/api"

python3 test/merge_prices_into_product_specs.py
python3 test/seed_product_specs_catalog.py
python3 test/seed_product_specs_catalog.py --sync-prices

echo "Готово: JSON обновлён, каталог и цены синхронизированы с API."
