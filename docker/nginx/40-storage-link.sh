#!/usr/bin/env sh

set -eu

mkdir -p /var/www/html/storage/app/public
rm -rf /var/www/html/public/storage
ln -s /var/www/html/storage/app/public /var/www/html/public/storage
