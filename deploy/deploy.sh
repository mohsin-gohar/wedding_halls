#!/usr/bin/env bash
################################################################
# Riwaj Grand — one-shot production deploy script (run on the server)
#
# Usage:
#   cd /var/www/riwaj-grand
#   sudo bash deploy/deploy.sh
#
# Assumes: PHP 8.2+, Composer, Node 20+, Redis, MySQL, Nginx already installed
# and .env already configured (start from .env.production.example; see
# deploy/DEPLOYMENT.md).
#
# This script is safe to run on every deploy (idempotent).
################################################################

set -euo pipefail

APP_DIR="/var/www/riwaj-grand"
cd "$APP_DIR"

# Once maintenance mode has been requested, always attempt to bring the app
# back up on every exit path. A failed deploy must never strand the site in 503.
maintenance_mode_attempted=0
migration_started=0
restore_application() {
    local deploy_exit_code=$?

    if [ "$maintenance_mode_attempted" -eq 1 ]; then
        if [ "$migration_started" -eq 0 ]; then
            echo "==> Restoring application availability (deployment failed before migrations)"
            php artisan up || echo "WARNING: automatic recovery failed; run 'php artisan up' manually." >&2
        else
            echo "WARNING: migration/deployment failed after migrations started; leaving maintenance mode enabled for safe manual recovery." >&2
        fi
    fi

    return "$deploy_exit_code"
}
trap restore_application EXIT

echo "==> [1/8] Enabling maintenance mode"
if php artisan down --render="errors::503"; then
    maintenance_mode_attempted=1
else
    echo "WARNING: could not enable maintenance mode; continuing because the app may already be down." >&2
fi

echo "==> [2/8] Pulling latest code (git)"
git pull --ff-only

echo "==> [3/8] Installing PHP dependencies (prod, optimized)"
composer install --optimize-autoloader --no-dev --no-interaction --prefer-dist

echo "==> [4/8] Building frontend assets (Vite + Tailwind)"
npm ci
npm run build

echo "==> [5/8] Running database migrations"
migration_started=1
php artisan migrate --force

echo "==> [6/8] Refreshing Laravel caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> [7/8] Ensuring storage link + permissions"
php artisan storage:link || true
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

echo "==> [8/8] Restarting queue workers + bringing app up"
php artisan queue:restart
php artisan up
maintenance_mode_attempted=0
migration_started=0
trap - EXIT

echo "==> Deploy complete. Verify: curl -I https://YOUR_DOMAIN/up"
