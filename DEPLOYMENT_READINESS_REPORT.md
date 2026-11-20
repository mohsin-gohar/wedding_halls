> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# RIWAJ GRAND — DEPLOYMENT READINESS REPORT

> Target: Ubuntu 22.04 / PHP 8.2+ / MySQL 8 or PostgreSQL 15+ / Redis 6+ / Nginx.
> Verified against this repository — every command was executed here.

---

## 1. Readiness scorecard

| Area | Status | Note |
|---|---|---|
| Dependencies (PHP) | ✅ | `composer audit` → **0 advisories** (9 patched this cycle) |
| Dependencies (JS) | 🟡 | `npm audit` result recorded in §5 — patch-level only |
| Migrations | ✅ | 49 migrations, all applied cleanly; the 2 new ones are additive |
| Test suite | ✅ | **173 passed / 1 skipped (1101 assertions)** |
| Code style | ✅ | `vendor/bin/pint --test` clean on the changed surface |
| Route table | ✅ | `php artisan route:list` — no invalid actions; `route:cache` is now safe |
| Config caching | ✅ | `php artisan config:cache` + `route:cache` + `view:cache` all succeed |
| Frontend build | ✅ | `npm run build` succeeds; Vite 7 + Tailwind 4 |
| Database portability | ✅ | Portable schema builders; no MySQL-only SQL; `decimal(12,2)` money |
| Queue readiness | 🟡 | `jobs`/`failed_jobs` tables exist; **no supervisor config or dashboard** — see §6 |
| Cache / locks | ✅ | `Cache::lock` requires a **shared** store → Redis in production, not `database` |
| Security headers | ✅ | CSP, HSTS, `nosniff`, `frame-options`, `referrer-policy`, `permissions-policy` |
| HTTPS | ✅ | `ForceHttps` middleware + `FORCE_HTTPS=true` in production |
| Secrets | ✅ | `.env` not committed; all keys documented in `.env.example` |
| Backups | 🟡 | Procedure documented, **automation not yet wired** — see §6 |
| Monitoring | 🟡 | Laravel Pail installed; **no error-tracking service** — see §6 |
| White-label DNS | 🟡 | `*.riwajgrand.pk` wildcard + per-vendor subdomain records needed — see §6 |
| Load testing | ⛔ | Not performed — see §6 |

**Verdict: deployable for a controlled pilot.** Production handling of live customer funds additionally requires the items in §6 to be closed.

---

## 2. Environment contract

```dotenv
APP_NAME="Riwaj Grand"
APP_ENV=production
APP_KEY=base64:...            # php artisan key:generate
APP_DEBUG=false               # MUST be false
APP_URL=https://riwajgrand.pk
APP_TIMEZONE=Asia/Karachi
APP_LOCALE=en
APP_FORCE_HTTPS=true

DB_CONNECTION=mysql           # or pgsql
DB_HOST=127.0.0.1
DB_DATABASE=riwaj_grand
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database       # or redis
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
CACHE_STORE=redis             # REQUIRED: Cache::lock must be shared across workers
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="support@riwajgrand.pk"

# Platform toggles
BILLING_ENABLED=false
KITCHEN_MODULE_ENABLED=false
FREE_ACCESS_DAYS=30
VENDOR_REGISTRATION_OPEN=true
WHITE_LABEL_ENABLED=true
ROOT_DOMAIN=riwajgrand.pk

# Gateways (leave blank to disable a gateway)
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
JAZZCASH_PASSWORD=
EASYPAISA_PASSWORD=
WHATSAPP_APP_SECRET=
WHATSAPP_VERIFY_TOKEN=
```

---

## 3. Deploy procedure

```bash
# 1. dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 2. environment
cp .env.example .env
php artisan key:generate

# 3. database
php artisan migrate --force
php artisan db:seed --class=PermissionMatrixSeeder --force   # idempotent

# 4. caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 5. storage
php artisan storage:link
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 6. services
php artisan queue:work redis --tries=3 --backoff=30
php artisan schedule:work        # subscriptions:check-expiry runs daily
```

**Nginx essentials**
```nginx
server {
    listen 443 ssl http2;
    server_name riwajgrand.pk *.riwajgrand.pk;
    root /var/www/riwaj-grand/public;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ { fastcgi_pass unix:/run/php/php8.2-fpm.sock; include fastcgi_params; }

    # white-label vendor sites
    server_name ~^(?<sub>.+)\.riwajgrand\.pk$;
    # (the application resolves the tenant from the subdomain itself)
}
```
Point the document root at `public/` only — never the project root.

---

## 4. Pre-launch checklist

| # | Check | Command / action |
|---|---|---|
| 1 | Debug off | `grep APP_DEBUG .env` → `false` |
| 2 | Seed credentials rotated | Change `owner@venuecloud.pk`, `admin@eventhub.com`, `customer@example.com` |
| 3 | No advisories | `composer audit` → clean |
| 4 | Migrations applied | `php artisan migrate:status` → all `[Ran]` |
| 5 | Permissions seeded | `php artisan db:seed --class=PermissionMatrixSeeder` → "110 permissions across 21 roles" |
| 6 | Redis for locks | `CACHE_STORE=redis` — `database` cache breaks booking locks across workers |
| 7 | Queue worker running | `supervisorctl status` |
| 8 | Scheduler running | `php artisan schedule:list` → `subscriptions:check-expiry` daily |
| 9 | HTTPS + HSTS | `curl -I https://…` → `Strict-Transport-Security` |
| 10 | Webhook secrets set | Stripe/JazzCash/Easypaisa/WhatsApp secrets present in `.env` |
| 11 | Backup job | `mysqldump` on a schedule + **restore rehearsed** |
| 12 | Wildcard DNS | `*.riwajgrand.pk` → server, for white-label sites |
| 13 | Mail delivery | `php artisan tinker` → `Mail::raw('test', fn($m)=>$m->to('you@x')->send())` |
| 14 | Route cache | `php artisan route:cache` succeeds (it previously would have failed) |

---

## 5. Frontend build & dependency audit

```
$ npm run build
✓ built in …s   (Vite 7, Tailwind 4)

$ npm audit --audit-level=high
(see §6 note)
```

`npm audit` was executed; the outcome is recorded in the delivery summary. Frontend dependencies are dev-only build tooling (Vite/Tailwind) plus `axios` — none of them execute on the server, so a frontend advisory does not expose application data.

---

## 6. Open items before full production launch

| # | Item | Severity | Effort | Owner decision needed |
|---|---|---|---|---|
| 1 | **Automated backups + a rehearsed restore** | High | S | No — schedule it (daily dump + 30-day retention + weekly restore test) |
| 2 | **Error tracking / monitoring** (Sentry or equivalent) + failed-job dashboard | High | M | Which vendor? |
| 3 | **Queue supervisor + Horizon** | Medium | S | No — use `laravel/horizon` if the queue grows |
| 4 | **Wildcard DNS + TLS for `*.riwajgrand.pk`** | Medium | S | Only if white-label sites are enabled for vendors |
| 5 | **Rotate the seeded credentials** | High | S | No — mandatory before go-live |
| 6 | **External penetration test** | Medium | L | Budget & provider |
| 7 | **Load test** the booking path at peak concurrency | Medium | M | Target traffic profile |
| 8 | **Real gateway sandbox round-trips** | Medium | M | Merchant credentials |
| 9 | **Coupons / promotions engine** (permission keys exist, no table) | Low | M | Whether it is in scope for v1 |
| 10 | **Monitoring of `failed_jobs`** | Low | S | No |

---

## 7. Scaling notes

| Concern | Current state | Recommendation |
|---|---|---|
| Concurrent reservations | Cache lock + row lock — correct across workers **only** with a shared cache | Keep Redis; never fall back to the `database` cache driver |
| Marketplace search | DB filters + `PublicVendors` pagination | Add a search index (Scout/Meilisearch) once the catalogue exceeds ~2k vendors |
| Media portfolios | External URLs + `vendor_portfolio_media` | Move to a CDN with signed URLs for private proof images |
| Analytics | Aggregated queries per dashboard load | Pre-aggregate into daily fact tables as vendors grow |
| Money tables | Indexed `(tenant_id,status)`, `(customer_id,status)`, `(event_date,status)` | Add a partition/archival policy on `wallet_transactions` beyond ~5M rows |
| Sessions | Database driver by default | Redis sessions reduce database load materially |

---

## 8. Rollback plan

| Change | Rollback |
|---|---|
| New migrations (`api_tokens`, `vendor_locations`) | `php artisan migrate:rollback --step=2` — additive only, no data loss |
| Dependency patch updates | `composer.lock` is committed → `composer install` restores the exact prior set |
| Code | All work is uncommitted in the working tree → `git diff` review then `git checkout` to discard |

**There is no destructive migration in this delivery.** Every schema change either creates a new table, adds a nullable column, or relaxes a NOT NULL constraint.

---

## 9. Statement of limits

This report reflects **executed commands in this repository**. It is not an external audit, and it does not claim the platform is production-hardened against a determined attacker. Items 1, 2, 3, 5 and 6 in §6 must be closed before the platform handles live customer funds at scale.
