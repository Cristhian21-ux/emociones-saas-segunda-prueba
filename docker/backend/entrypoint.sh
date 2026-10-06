#!/bin/sh
set -e
cd /var/www/html
touch .env
# Genera la APP_KEY una sola vez y la conserva en storage.
if [ -z "$APP_KEY" ]; then
  [ -f storage/app/.app_key ] || php artisan key:generate --show > storage/app/.app_key
  export APP_KEY="$(cat storage/app/.app_key)"
fi
php artisan migrate --force
php artisan config:cache && php artisan route:cache
exec apache2-foreground
