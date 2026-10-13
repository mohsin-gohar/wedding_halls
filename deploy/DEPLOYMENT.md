# VenueCloud — Production Deployment Guide

This guide takes the Wedding Hall Booking & Management SaaS from this repo to a
live production server. It complements the automated pieces added in `deploy/`.

## 1. Server prerequisites

| Component | Version | Notes |
|-----------|---------|-------|
| OS | Ubuntu 22.04 LTS+ | |
| PHP | 8.2+ | Extensions: `bcmath ctype curl dom fileinfo json mbstring openssl pcre pdo tokenizer xml redis` |
| Web server | Nginx | + PHP-FPM 8.2 |
| Database | MySQL 8.0+ (or PostgreSQL 15+) | SQLite is dev-only |
| Cache/Queue | Redis 7+ | |
| Node | 20 LTS | For `npm ci && npm run build` |
| Composer | 2.x | |
| Supervisor | latest | For the queue worker |

Install PHP Redis extension for the `redis` session/cache/queue drivers used in
`.env.production`:

```bash
sudo apt install -y php8.2-redis redis-server
```

## 2. Code & environment

```bash
cd /var/www
sudo git clone https://github.com/mohsin-gohar/wedding_halls.git venuecloud
cd venuecloud

# Start from the tracked environment template, then apply production values.
cp .env.example .env
php artisan key:generate        # fills APP_KEY
# Set APP_ENV=production, APP_DEBUG=false, and configure DB_*, Redis, mail, storage, APP_URL, and SESSION_*.
# Set WHATSAPP_APP_SECRET when enabling the signed WhatsApp webhook.
```

Create the MySQL database and user:

```sql
CREATE DATABASE venuecloud CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'venuecloud'@'127.0.0.1' IDENTIFIED BY 'STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON venuecloud.* TO 'venuecloud'@'127.0.0.1';
FLUSH PRIVILEGES;
```

## 3. Deploy (automated)

```bash
sudo bash deploy/deploy.sh
```

This installs deps, builds assets, migrates, caches config/routes/views/events,
fixes permissions, and restarts the queue.

## 4. Web server (Nginx) + SSL

```bash
sudo cp deploy/nginx-venuecloud.conf /etc/nginx/sites-available/venuecloud
sudo ln -s /etc/nginx/sites-available/venuecloud /etc/nginx/sites-enabled/venuecloud
sudo nginx -t
sudo systemctl reload nginx

# TLS certificate (HTTP->HTTPS redirect already in the config)
sudo certbot --nginx -d venuecloud.pk -d www.venuecloud.pk
```

> The app trusts the proxy scheme. If you terminate TLS at Nginx, Laravel's
> `Request::secure()` works out of the box. If using Cloudflare/LB, set
> `App\Http\Middleware\TrustProxies` trusted proxies accordingly.

## 5. Queue worker (Supervisor)

```bash
sudo cp deploy/supervisor-venuecloud-worker.conf /etc/supervisor/conf.d/venuecloud-worker.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start venuecloud-worker:*
```

## 6. Scheduler (cron)

```bash
sudo crontab -e   # for www-data, or edit /etc/cron.d/venuecloud
```

```cron
* * * * * cd /var/www/venuecloud && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler runs `subscriptions:check-expiry` daily (subscription lifecycle).

## 7. WhatsApp Cloud API webhook

In the Meta developer console, point the webhook to:

```
https://venuecloud.pk/webhooks/whatsapp
```

- **Verify token**: must equal `WHATSAPP_VERIFY_TOKEN` in `.env`
- **App secret**: set `WHATSAPP_APP_SECRET` to the Meta app secret; unsigned or invalidly signed callbacks are rejected.
- **Subscribe to**: `messages`
- Leave `WHATSAPP_ACCESS_TOKEN` / `WHATSAPP_PHONE_NUMBER_ID` empty to run in
  sandbox mode (outbound messages are logged instead of sent).

## 8. Post-deploy verification

```bash
curl -I https://venuecloud.pk/up          # 200, no debug info
curl -I https://venuecloud.pk/            # check security headers present
php artisan about                          # environment = production
```

- Confirm headers: `Strict-Transport-Security`, `X-Content-Type-Options`,
  `X-Frame-Options`, `Content-Security-Policy`.
- Confirm no `.env` is web-accessible: `curl -I https://venuecloud.pk/.env` → 403.

## 9. Ongoing operations

- **Logs**: `storage/logs/laravel-*.log` (daily rotation, 14 days).
- **Backups**: schedule daily MySQL dumps + S3 sync, e.g.

  ```cron
  0 2 * * * mysqldump -u venuecloud -p venuecloud | gzip > /backups/venuecloud-$(date +\%F).sql.gz
  ```

- **Optional monitoring**: set `SENTRY_LARAVEL_DSN` and install
  `sentry/sentry-laravel` for error tracking.

## 10. Security features already wired in code

- **SecurityHeaders middleware** (`app/Http/Middleware/SecurityHeaders.php`):
  CSP (tuned for Livewire + Alpine + Google Fonts), HSTS (over HTTPS only),
  nosniff, Referrer-Policy, Permissions-Policy, X-Frame-Options.
- **ForceHttps middleware** (`app/Http/Middleware/ForceHttps.php`): HTTP→HTTPS
  redirect, active only when `FORCE_HTTPS=true`; `/up` is exempt.
- **Login rate limiting**: both vendor and customer login throttle to
  5 attempts/minute per email+IP via `RateLimiter`.
- **WhatsApp config** (`config/services.php`) is now properly registered.

Toggle CSP off (`SECURITY_HEADERS_CSP=false`) if you serve it from Nginx/Cloudflare.
