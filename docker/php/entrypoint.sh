#!/usr/bin/env sh

set -eu

cd /var/www/html

umask 0002

mkdir -p \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

touch storage/logs/laravel.log

rm -rf public/storage
ln -s /var/www/html/storage/app/public public/storage

chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwX storage bootstrap/cache || true

if [ "${APP_RUN_PACKAGE_DISCOVER:-true}" = "true" ]; then
    php artisan package:discover --ansi
fi

if [ "${APP_RUN_STORAGE_LINK:-true}" = "true" ]; then
    php artisan storage:link --ansi || true
fi

if [ "${APP_RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force --ansi
fi

if [ "${APP_RUN_OPTIMIZE:-true}" = "true" ]; then
    php artisan optimize:clear --ansi || true
    php artisan optimize --ansi
fi

exec "$@"
