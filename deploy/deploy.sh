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

echo "==> [1/8] Enabling maintenance mode"
php artisan down --render="errors::503" || true

echo "==> [2/8] Pulling latest code (git)"
git pull --ff-only

echo "==> [3/8] Installing PHP dependencies (prod, optimized)"
composer install --optimize-autoloader --no-dev --no-interaction --prefer-dist

echo "==> [4/8] Building frontend assets (Vite + Tailwind)"
npm ci
npm run build

echo "==> [5/8] Running database migrations"
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

echo "==> Deploy complete. Verify: curl -I https://YOUR_DOMAIN/up"
