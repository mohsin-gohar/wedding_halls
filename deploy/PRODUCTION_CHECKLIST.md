# Riwaj Grand Production Go-Live Checklist

Use this checklist for every production release. It complements
[`DEPLOYMENT.md`](DEPLOYMENT.md) and [`SCALING.md`](SCALING.md); it is deliberately
short enough to copy into a release ticket.

---

## 1. Pre-flight (run locally / CI)

- [ ] `php artisan test` passes.
- [ ] `vendor/bin/pint --test` passes (or `vendor/bin/pint` has been committed).
- [ ] `npm ci && npm run build` completes without errors.
- [ ] `php artisan route:list --except-vendor` has no unexpected public routes.
- [ ] No secrets, `.env`, SQL dumps, or `storage/logs/*.log` are committed.
- [ ] Changelog / release notes list migrations, env changes, and rollback notes.

## 2. Server and PHP runtime

- [ ] PHP 8.2+ with `bcmath ctype curl dom fileinfo json mbstring openssl pcre pdo tokenizer xml redis`.
- [ ] Composer 2.x, Node 24 LTS (build can run in CI), Nginx, Redis 7+, MySQL 8+.
- [ ] `storage/` and `bootstrap/cache/` are writable by the web user (`www-data`).
- [ ] `php artisan storage:link` has been run **if** `FILESYSTEM_DISK=public`.
      With S3/R2 the link is not required, but the bucket policy must allow the
      public/CDN reads used by hall galleries.
- [ ] Supervisor queue worker and cron scheduler are installed (see §5–6 of
      `DEPLOYMENT.md`).
- [ ] Nginx client body size, timeouts, and TLS settings match
      `deploy/nginx-riwaj-grand.conf`.
- [ ] Trusted proxies are configured if TLS terminates at Cloudflare, a load
      balancer, or another reverse proxy; `FORCE_HTTPS=true` only when
      `Request::secure()` can be trusted.

## 3. Environment and secrets

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`.
- [ ] `APP_KEY` generated **once** and backed up in the secret manager.
- [ ] Database, Redis, S3, mail, and WhatsApp credentials are real production
      values (no `CHANGE_ME`, no sandbox tokens).
- [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, and
      `SESSION_DOMAIN` matches the production host.
- [ ] `CACHE_STORE`, `QUEUE_CONNECTION`, and `BOOKING_LOCK_STORE` are **not**
      `array`/`sync` in production. The dashboard cache, rate limiters and
      booking reservation locks depend on a shared store.
- [ ] `LOG_CHANNEL=daily` (or a centralized driver) and `LOG_LEVEL=warning`.
- [ ] `WHATSAPP_APP_SECRET` and `WHATSAPP_VERIFY_TOKEN` are set before enabling
      the webhook.
- [ ] `SECURITY_HEADERS_CSP=true` unless Nginx/CDN is already injecting an
      equivalent policy.

## 4. Database and files

- [ ] Take a verified database backup **before** running migrations.
- [ ] `php artisan migrate --force` completes; no pending migrations remain.
- [ ] Seed/reference data (subscription plans, platform settings) exists.
- [ ] Object storage bucket exists, has the expected region, and lifecycle/
      versioning rules are intentional.
- [ ] Restore drill: a recent backup has been restored to a staging database.

## 5. Cache, queue, and scheduler

- [ ] `php artisan optimize:clear` then `php artisan optimize` (config, routes,
      views, events).
- [ ] `php artisan queue:restart` so workers load the new code.
- [ ] Queue worker logs show successful jobs after deploy.
- [ ] `php artisan schedule:list` shows `subscriptions:check-expiry` and any
      other expected tasks.
- [ ] Cron entry is present for the deploy user:
      `* * * * * cd /var/www/riwaj-grand && php artisan schedule:run >> /dev/null 2>&1`.
- [ ] Redis eviction policy is appropriate for cache + sessions + queues
      (do not use `allkeys-lru` for a queue-bearing Redis without review).

## 6. Security verification

- [ ] `https://<host>/.env` returns 403/404, never file contents.
- [ ] `https://<host>/up` returns 200 and leaks no debug information.
- [ ] Response headers include `Strict-Transport-Security`,
      `X-Content-Type-Options`, `X-Frame-Options`/`frame-ancestors`, and CSP.
- [ ] `/admin` returns **403** for non-super-admin accounts (not a redirect that
      confirms the route).
- [ ] Login throttling works (5 failed attempts per email/IP per minute).
- [ ] Public page throttles are active (`public-pages`, `public-availability`)
      and contact/RSVP actions have per-component limits.
- [ ] The Super Admin account uses a unique password and MFA at the edge/
      identity provider where available.
- [ ] Only `ROLE_SUPER_ADMIN` can reach the platform console; tenant staff
      cannot cross tenant boundaries (spot-check a tenant URL with another
      tenant's account).
- [ ] A GET request to `/logout` returns 405; the POST form still signs out.
- [ ] Newly created API tokens are read-only and expire in ~90 days unless
      wildcard access was explicitly requested.

## 7. Smoke tests (production URL)

- [ ] Home, `/venues`, a hall showcase, `/gallery`, `/packages`, `/services`,
      `/about`, `/contact`, `/faqs`, and `/check-availability` return 200.
- [ ] Public calendar shows the current year through current year + 5 and
      refuses past dates / past months.
- [ ] A test availability lookup returns correct booked/available shifts.
- [ ] Contact form and RSVP forms submit successfully and are rate-limited.
- [ ] Super Admin can log in, open every console tab, and see cached counters.
- [ ] A privileged action (e.g. suspend a test user or create a task) writes an
      audit entry and the counters refresh.
- [ ] Queue-backed mail/WhatsApp notifications are delivered or logged as
      configured.
- [ ] PDF/document downloads work from the configured filesystem disk.
- [ ] A cash service payment submission stays `PENDING` until staff verify it,
      and verification never pushes `paid_amount` above the booking total.

## 8. Observability and rollback

- [ ] Uptime monitor points at `/up` and the public home page.
- [ ] Error tracking (Sentry or equivalent) receives a test event.
- [ ] Alerts exist for 5xx rate, queue backlog, failed jobs, disk usage, and
      database/Redis availability.
- [ ] Log rotation is configured and logs are shipped off-box.
- [ ] Rollback plan is written: previous release tag, `php artisan down`
      behaviour, migration rollback (or forward-fix) decision, and cache clear.
- [ ] On-call owner and escalation channel are recorded in the release ticket.

---

### Fast rollback

```bash
php artisan down --retry=60
git checkout <previous-release-tag>
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force        # only if the release is forward-compatible
php artisan optimize
php artisan queue:restart
php artisan up
```

If a migration is destructive or irreversible, restore the pre-deploy backup
instead of rolling back blindly.
