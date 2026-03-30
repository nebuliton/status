#!/usr/bin/env sh

set -eu

mkdir -p /var/www/html/storage/app/public
chown -R 82:82 /var/www/html/storage || true
chmod -R ug+rwX /var/www/html/storage || true
rm -rf /var/www/html/public/storage
ln -s /var/www/html/storage/app/public /var/www/html/public/storage
