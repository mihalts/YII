#!/usr/bin/env bash
set -e

cd /var/www/html

# vendor
if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist
fi

# ініціалізація Yii (одноразово)
if [ ! -f backend/config/main-local.php ]; then
  php /var/www/html/init --env="${YII_ENV:-Development}" --overwrite=All
fi

# службові директорії + права (для дев)
mkdir -p storage/databases backend/web/exports
chmod -R 777 backend/runtime backend/web/assets frontend/runtime frontend/web/assets console/runtime storage backend/web/exports || true

# чекаємо БД
if [ -n "${DB_HOST}" ]; then
  echo "Waiting for DB ${DB_HOST}:${DB_PORT:-3306}..."
  until php -r "exit((int)!@fsockopen(getenv('DB_HOST'), getenv('DB_PORT') ?: 3306));"; do sleep 1; done
fi

# міграції (якщо є)
php yii migrate --interactive=0 || true

exec apache2-foreground
