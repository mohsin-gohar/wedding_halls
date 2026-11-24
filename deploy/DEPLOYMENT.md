# Riwaj Grand — Production Deployment Guide

This guide takes the Wedding Hall Booking & Management SaaS from this repo to a
live production server. It complements the automated pieces added in deploy/.

> **Current operations runbook:** See [PRODUCTION_OPERATIONS.md](PRODUCTION_OPERATIONS.md)
> for the supported Hostinger Shared versus VPS split, Caddy TLS, readiness
> monitoring, encrypted backups, staging restore drills, and live SMTP/WhatsApp
> evidence. Repository automation does not provision external provider accounts.

## 1. Server prerequisites

| Component | Version | Notes |
|-----------|---------|-------|
| OS | Ubuntu 22.04 LTS+ | |
| PHP | 8.2+ | Extensions: `bcmath ctype curl dom fileinfo json mbstring openssl pcre pdo tokenizer xml redis` |
| Web server | Nginx | + PHP-FPM 8.2 |
| Database | MySQL 8.0+ only | No SQLite/PostgreSQL fallback |
| Cache/Queue | Redis 7+ | Shared store required for booking locks |
| Node | 20 LTS | For `npm ci && npm run build` |
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

The scheduler runs `subscriptions:check-expiry` daily (subscription lifecycle).

## 7. Docker deployment (alternative)

`Dockerfile` starts Supervisor so PHP-FPM, the queue worker and the scheduler
all run inside the application container. `docker-compose.yml` builds a
separate Nginx image containing the same compiled release assets, serves
`/storage` from a dedicated public-media volume, and never mounts private
documents into the web container. MySQL and Redis stay on the private bridge
network; Caddy provides automatic apex/www HTTPS. Compose refuses to start without `APP_DOMAIN`, `DB_ROOT_PASSWORD`, and
`DB_PASSWORD`. The app container is not given the MySQL root password.

```bash
cp .env.production.example .env    # fill every secret and APP_DOMAIN
chmod 600 .env
docker compose config --quiet
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize
```

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
- **Backups**: use the encrypted, checksum-verified off-site workflow in
  [PRODUCTION_OPERATIONS.md](PRODUCTION_OPERATIONS.md); a dump is not a
  recoverable backup until it has passed a staging restore drill.
- **Uptime monitoring**: the optional Uptime Kuma service is available with
  the Docker monitoring profile and binds to localhost only. Configure alert
  channels and monitors after provisioning the host.
- **Error tracking**: no Sentry project/DSN is provisioned by this repository.
  Do not claim error tracking is active until a provider is configured and a
  test event is received.

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
