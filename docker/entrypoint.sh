#!/bin/sh
################################################################
# Riwaj Grand container entrypoint.
#
# A persistent volume may be mounted over storage/. Recreate the expected
# Laravel directory tree and permissions before Supervisor starts the workers.
################################################################
set -eu

cd /var/www

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

exec "$@"
