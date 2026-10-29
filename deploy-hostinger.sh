#!/bin/bash
# ==============================================================================
# EVENTHUB — HOSTINGER ZERO-DOWNTIME DEPLOYMENT SCRIPT
# ==============================================================================

set -e

echo "1. Activating Maintenance Mode..."
php artisan down || true

echo "2. Installing / Updating Composer Dependencies for Production..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "3. Running Hostinger Setup & Optimization..."
php artisan hostinger:setup

echo "4. Deactivating Maintenance Mode..."
php artisan up

echo "===================================================="
echo "Deployment completed successfully on Hostinger!"
echo "===================================================="
