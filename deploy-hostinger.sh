#!/usr/bin/env bash
# Riwaj Grand — Hostinger deployment (maintenance-mode release, not zero-downtime).
set -euo pipefail

maintenance_mode_attempted=0
setup_started=0

restore_application() {
    local deploy_exit_code=$?

    if [ "$maintenance_mode_attempted" -eq 1 ]; then
        if [ "$setup_started" -eq 0 ]; then
            echo "==> Restoring application availability after pre-setup failure"
            php artisan up || echo "WARNING: run 'php artisan up' manually if recovery failed." >&2
        else
            echo "WARNING: setup or migrations failed; maintenance mode remains enabled for safe manual recovery." >&2
        fi
    fi

    return "$deploy_exit_code"
}
trap restore_application EXIT

echo "1. Activating maintenance mode..."
if php artisan down; then
    maintenance_mode_attempted=1
else
    echo "WARNING: could not enable maintenance mode; continuing with deployment." >&2
fi

echo "2. Installing production Composer dependencies..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "3. Running Hostinger setup and optimization..."
setup_started=1
php artisan hostinger:setup

echo "4. Restoring application availability..."
php artisan up
maintenance_mode_attempted=0
setup_started=0
trap - EXIT

echo "Hostinger deployment completed. Verify HTTPS, health, login, and critical workflows."
