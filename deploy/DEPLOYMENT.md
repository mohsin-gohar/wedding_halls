# Riwaj Grand — Production Deployment Guide

This guide takes the Wedding Hall Booking & Management SaaS from this repo to a
live production server. It complements the automated pieces added in `deploy/`.

## 1. Server prerequisites

| Component | Version | Notes |
|-----------|---------|-------|
| OS | Ubuntu 22.04 LTS+ | |
| PHP | 8.2+ | Extensions: `bcmath ctype curl dom fileinfo json mbstring openssl pcre pdo tokenizer xml redis` |
| Web server | Nginx | + PHP-FPM 8.2 |
| Database | MySQL 8.0+ only | No SQLite/PostgreSQL fallback |
| Cache/Queue | Redis 7+ | Shared store required for booking locks |
| Node | 24 LTS | For `npm ci && npm run build` |
| Composer | 2.x | |
| Supervisor | latest | For PHP-FPM, queue worker and scheduler |

Install the PHP Redis extension for the `redis` session/cache/queue drivers used
in `.env.production`:

```bash
sudo apt install -y php8.2-redis redis-server
```

## 2. Code & environment

```bash
cd /var/www
sudo git clone https://github.com/mohsin-gohar/wedding_halls.git riwaj-grand
cd riwaj-grand

# Start from the tracked environment template, then apply production values.
cp .env.production.example .env
php artisan key:generate        # fills APP_KEY
# Set APP_ENV=production, APP_DEBUG=false, and configure DB_*, Redis, mail, storage, APP_URL, and SESSION_*.
# Set WHATSAPP_APP_SECRET when enabling the signed WhatsApp webhook.
```

Create the MySQL database and user:

```sql
CREATE DATABASE riwaj_grand CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'riwaj'@'127.0.0.1' IDENTIFIED BY 'STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON riwaj_grand.* TO 'riwaj'@'127.0.0.1';
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
sudo cp deploy/nginx-riwaj-grand.conf /etc/nginx/sites-available/riwaj-grand
sudo ln -s /etc/nginx/sites-available/riwaj-grand /etc/nginx/sites-enabled/riwaj-grand
sudo nginx -t
sudo systemctl reload nginx

# TLS certificate (HTTP->HTTPS redirect already in the config)
sudo certbot --nginx -d YOUR_DOMAIN -d www.YOUR_DOMAIN
```

> Replace `YOUR_DOMAIN` in the Nginx config and certbot command with the real
> production host. The app trusts the proxy scheme. If you terminate TLS at
> Nginx, Laravel's `Request::secure()` works out of the box. If using
> Cloudflare/LB, set `App\Http\Middleware\TrustProxies` trusted proxies
> accordingly.

## 5. Queue worker (Supervisor)

```bash
sudo cp deploy/supervisor-riwaj-grand-worker.conf /etc/supervisor/conf.d/riwaj-grand-worker.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start riwaj-grand-worker:*
```

## 6. Scheduler (cron)

```bash
sudo crontab -e   # for www-data, or edit /etc/cron.d/riwaj-grand
```

```cron
* * * * * cd /var/www/riwaj-grand && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler runs `subscriptions:check-expiry` daily (subscription lifecycle) and `bookings:expire-holds` hourly (expired hall-slot holds).

## 7. Docker deployment (alternative)

`Dockerfile` starts Supervisor so PHP-FPM, the queue worker and the scheduler
all run inside the application container. `docker-compose.yml` keeps MySQL and
Redis on the private bridge network and refuses to start without
`DB_ROOT_PASSWORD` and `DB_PASSWORD`.

The Compose Nginx service serves the host-mounted `./public` directory.
Build those assets on the host before bringing the stack up, then create the
storage link from the application container:

```bash
cp .env.production.example .env    # fill every secret
npm ci
npm run build
docker compose up -d --build
docker compose exec app php artisan storage:link --force
docker compose exec app php artisan migrate --force
```

The Compose Nginx service listens on HTTP port 80 only. Put it behind a
TLS-terminating reverse proxy or load balancer before exposing the application
to the public internet, and configure Laravel trusted proxies/HTTPS settings
for that topology. Do not assume the Compose Nginx service provides TLS.

Do not publish MySQL/Redis ports to the public internet. If you need a one-off
debug session, add a host mapping temporarily and remove it afterwards.

## 8. WhatsApp Cloud API webhook

In the Meta developer console, point the webhook to:

```
https://YOUR_DOMAIN/webhooks/whatsapp
```

- **Verify token**: must equal `WHATSAPP_VERIFY_TOKEN` in `.env`
- **App secret**: set `WHATSAPP_APP_SECRET` to the Meta app secret; unsigned or invalidly signed callbacks are rejected.
- **Subscribe to**: `messages`
- Leave `WHATSAPP_ACCESS_TOKEN` / `WHATSAPP_PHONE_NUMBER_ID` empty to run in
  sandbox mode (outbound messages are logged instead of sent).

## 9. Post-deploy verification

```bash
curl -I https://YOUR_DOMAIN/up          # 200, no debug info
curl -I https://YOUR_DOMAIN/            # check security headers present
php artisan about                       # environment = production
```

- Confirm headers: `Strict-Transport-Security`, `X-Content-Type-Options`,
  `X-Frame-Options`, `Content-Security-Policy`.
- Confirm no `.env` is web-accessible: `curl -I https://YOUR_DOMAIN/.env` → 403.

## 10. Ongoing operations

- **Logs**: `storage/logs/laravel-*.log` (daily rotation, 14 days).
- **Backups**: schedule daily MySQL dumps + S3 sync, e.g.

  ```cron
  0 2 * * * mysqldump -u riwaj -p riwaj_grand | gzip > /backups/riwaj-grand-$(date +\%F).sql.gz
  ```

- **Optional monitoring**: set `SENTRY_LARAVEL_DSN` and install
  `sentry/sentry-laravel` for error tracking.

- **Scaling**: see [`SCALING.md`](SCALING.md) for Redis/session/queue requirements,
  scale-out readiness, database query workflow, and load-test gates. Do not use
  per-node file/array caches for booking locks on multiple app instances.

## 11. Security features already wired in code

- **SecurityHeaders middleware** (`app/Http/Middleware/SecurityHeaders.php`):
  CSP (tuned for Livewire + Alpine + Google Fonts), HSTS (over HTTPS only),
  nosniff, Referrer-Policy, Permissions-Policy, X-Frame-Options.
- **ForceHttps middleware** (`app/Http/Middleware/ForceHttps.php`): HTTP→HTTPS
  redirect, active only when `FORCE_HTTPS=true`; `/up` is exempt.
- **Login rate limiting**: both vendor and customer login throttle to
  5 attempts/minute per email+IP via `RateLimiter`.
- **WhatsApp config** (`config/services.php`) is now properly registered.
- **POST-only logout**: a GET `/logout` returns 405.
- **API tokens**: new tokens default to read-only abilities and a 90-day expiry;
  wildcard access must be explicitly requested.

Toggle CSP off (`SECURITY_HEADERS_CSP=false`) if you serve it from Nginx/Cloudflare.
