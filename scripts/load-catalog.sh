#!/usr/bin/env bash
# Полная загрузка каталога: цены из documents/ → JSON → API (товары, категории, фильтры, цены).
#
# API по умолчанию: http://127.0.0.1 (порт 80). Другой хост/порт/HTTPS:
#   export API_BASE_URL=https://ваш-домен.ru
#   export API_BASE_URL=http://127.0.0.1:8000   # локальный php -S :8000
# Пароль админа (если не дефолтный из smoke_common):
#   export API_ADMIN_USER=admin
#   export API_ADMIN_PASSWORD='...'
#
# Опционально положить в api/.env.catalog строки export API_BASE_URL=...
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ENV_CATALOG="$ROOT/api/.env.catalog"
if [ -f "$ENV_CATALOG" ]; then
	set -a
	# shellcheck disable=SC1090
	source "$ENV_CATALOG"
	set +a
fi
: "${API_BASE_URL:=http://127.0.0.1}"
export API_BASE_URL
cd "$ROOT/api"

python3 test/merge_prices_into_product_specs.py
python3 test/seed_product_specs_catalog.py
python3 test/seed_product_specs_catalog.py --sync-prices

echo "Готово: JSON обновлён, каталог и цены синхронизированы с API."
