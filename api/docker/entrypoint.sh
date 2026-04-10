#!/bin/sh
set -e
cd /app

if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist
fi

mkdir -p config/jwt var/cache var/log
chmod -R 777 var 2>/dev/null || true

# Ключи JWT (в .gitignore) — создаём при первом запуске
if [ ! -f config/jwt/private.pem ]; then
  php bin/console lexik:jwt:generate-keypair --no-interaction --skip-if-exists
fi

# Миграции после готовности БД (depends_on + healthcheck)
if [ "${RUN_MIGRATIONS:-1}" -eq 1 ]; then
  php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
fi

exec "$@"
