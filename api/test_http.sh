#!/usr/bin/env bash
# Проверка доступности страниц и API. Запускай, когда поднят сервер с роутером Symfony:
#   php -S 127.0.0.1:8000 -t public public/router.php
# (только -t public без index.php — маршруты Symfony не работают, будет 404.)

set -euo pipefail
BASE="${1:-http://127.0.0.1:8000}"
BASE="${BASE%/}"

fail=0
check() {
  local url="$1" expect="${2:-200}"
  local code
  code=$(curl -sS -o /dev/null -w "%{http_code}" "$url" || echo "000")
  if [[ "$code" == "$expect" ]]; then
    echo "OK $code $url"
  else
    echo "FAIL $code (ожидалось $expect) $url"
    fail=1
  fi
}

echo "Checking $BASE ..."
check "$BASE/" 200
check "$BASE/api/categories/tree" 200
check "$BASE/design/catalog.php" 200
check "$BASE/design/css/header.css" 200
check "$BASE/design/assets/logo-mark.svg" 200
check "$BASE/api-tester.html" 200
check "$BASE/api/docs.jsonopenapi" 200
code=$(curl -sS -o /dev/null -w "%{http_code}" "$BASE/catalog" || echo "000")
if [[ "$code" == "302" ]]; then
  echo "OK $code $BASE/catalog (редирект на /design/catalog.php)"
else
  echo "FAIL $code (ожидался 302) $BASE/catalog"
  fail=1
fi

exit $fail
