# Riwaj Grand — Hostinger production deployment

This guide is for a deliberate, owner-controlled deployment of the Laravel 12 / Livewire application. Follow the provider-specific limits of your Hostinger plan; Shared, Cloud and VPS plans do not provide identical process, Redis, cron or storage capabilities.

> **Cash-only finance:** the application does not support online payment gateways. Do not add Stripe, JazzCash or EasyPaisa credentials.
>
> **Production seeding is disabled:** never use `--seed` with the production setup command. Demo seeders must not be run against a live database. Create the first Super Admin through the documented, reviewed admin-provisioning procedure.

## 1. Confirm the hosting plan

Before choosing a deployment shape, confirm that the plan provides:

- PHP 8.2+ with `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `curl`, `fileinfo`, `bcmath`, `gd` and `redis` only if Redis is selected.
- MySQL 8.0+ (the supported database).
- SSH/Composer access, writable Laravel `storage/` and `bootstrap/cache/`.
- A real cron facility for Laravel's scheduler.
- A supported way to run queue workers continuously, or scheduled short-lived workers.
- Sufficient upload limits for the configured media limit and enough storage for user uploads and backups.

If the plan cannot run the required queue worker/scheduler or provide a shared atomic lock store, use a VPS/Cloud plan or a managed service that can. Do not silently substitute `QUEUE_CONNECTION=sync`, `CACHE_STORE=file` or `BOOKING_LOCK_STORE=file` in production.

## 2. Prepare the domain and database

1. Point the domain DNS records to the chosen Hostinger service.
2. Enable the provider's SSL certificate and wait until HTTPS is active.
3. Create a dedicated MySQL 8 database and least-privilege application user in hPanel.
4. Keep database credentials in the server-side environment only. Never commit `.env`, paste secrets into tickets, or place backups under the public web root.

## 3. Upload a release

Use Hostinger Git deployment or a release ZIP. Deploy the reviewed release branch/commit that has passed CI; do not deploy an unreviewed working branch.

Keep the Laravel application outside the public document root where the hosting layout permits it. Configure the domain document root to the project's `public/` directory. If the provider forces the project into `public_html`, apply the repository's root/public rewrite and deny rules, and verify that `.env`, `.git`, `vendor/`, `storage/`, SQL dumps and logs cannot be fetched over HTTP.

Install dependencies and build assets during the release process:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
```

Do not copy a developer `.env` or a local database into production.

## 4. Configure production environment

Start from `.env.production.example` and set real values privately. At minimum:

```ini
APP_NAME="Riwaj Grand"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR_REAL_DOMAIN
APP_KEY=base64:GENERATE_ONCE_AND_KEEP_SECRET

DB_CONNECTION=mysql
DB_HOST=YOUR_HOST_DB_HOST
DB_PORT=3306
DB_DATABASE=YOUR_DATABASE
DB_USERNAME=YOUR_DATABASE_USER
DB_PASSWORD="YOUR_DATABASE_PASSWORD"

CACHE_STORE=database
BOOKING_LOCK_STORE=database
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
PROOFS_FILESYSTEM_DISK=local

LOG_CHANNEL=daily
LOG_LEVEL=warning
```

Use the actual database host supplied by Hostinger; `127.0.0.1` is only an example and is not universal. Keep the booking lock store on a shared, atomic backend. For multiple app instances, use shared Redis or another explicitly supported shared lock store; local file/array stores are not safe.

Configure SMTP with the provider-issued hostname, port, encryption, username and password. Configure Meta WhatsApp only after creating the Meta app and phone-number asset; set `WHATSAPP_VERIFY_TOKEN`, `WHATSAPP_APP_SECRET`, `WHATSAPP_ACCESS_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID` and the supported `WHATSAPP_API_VERSION`. Without real credentials, outbound messages remain in log/sandbox mode and are **not delivered**.

## 5. First deployment

Before any migration, take a database backup and a separate copy of uploaded files. Confirm that both can be read and that the backup is stored outside the public web root and off the application host.

Then, over SSH, from the release directory:

```bash
php artisan about
php artisan hostinger:setup
php artisan optimize
php artisan schedule:list
```

Check `php artisan hostinger:setup --help` on the deployed release before running it. Do not pass a seed option. Run migrations only after a verified pre-deploy backup and a reviewed release/rollback plan. Never run destructive migration or restore commands against production as an experiment.

## 6. Queue worker and scheduler

The application uses queued work and scheduled maintenance. A production operator must configure both:

- Preferred: a supervised, persistent worker using the provider's supported process manager.
- If the plan supports cron but not persistent processes: use a short-lived, bounded worker command according to the app's queue configuration, and monitor the queue/failed jobs. Confirm the exact command and overlap behavior before enabling it.
- Add the Laravel scheduler cron entry once per application:
  `* * * * * cd /ABSOLUTE/PATH/TO/RELEASE && php artisan schedule:run >> /dev/null 2>&1`

Do not assume that a cron entry alone processes queued jobs. Verify that a test job is actually handled and that the worker restarts after deployments.

## 7. Storage, backups and recovery

- Public hall/gallery media may use the configured public disk. Run `php artisan storage:link` only when the chosen disk and host support it.
- Confidential payment proofs and other private documents must remain on a private disk and must not be exposed through a public symlink.
- Back up both MySQL and uploaded files. Encrypt backups, checksum them, copy them off-site, apply retention, and alert on backup failure.
- Perform restore drills only against a separate staging database and isolated storage path. Verify record counts, representative file checksums, permissions and application reads before considering recovery proven.
- Never restore a staging or backup snapshot over the live production database as a test.

Use the repository's current scripts and `deploy/PRODUCTION_OPERATIONS.md` / `deploy/PRODUCTION_CHECKLIST.md` as the operational source of truth. If a script is unavailable on the selected release or hosting plan, stop and document the gap rather than improvising a destructive command.

## 8. Go-live verification

Run the read-only production smoke checks and verify:

- `/up` is healthy and `/ready` passes its dependency checks.
- HTTPS redirects correctly, the certificate is valid, security headers are present, and `/.env` returns 403/404 without disclosing content.
- Public pages, login, tenant isolation, admin RBAC, uploads, private document authorization and cash-payment workflows behave correctly.
- A real SMTP test reaches an owner-approved test inbox.
- A real WhatsApp test reaches an owner-approved test recipient.
- A queue job is processed, scheduled commands execute, and failed-job monitoring works.
- A fresh encrypted database + file backup is uploaded off-site and a restore drill succeeds on isolated staging.
- Monitoring receives a test event and the responsible operator acknowledges it.

CI success is not proof of live DNS, TLS, SMTP, WhatsApp, backups, monitoring or host configuration. Record the timestamp, target, test evidence and operator for each external check. Do not call the system production-ready while any mandatory gate remains unverified.
