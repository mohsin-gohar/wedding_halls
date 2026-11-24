# Riwaj Grand — Production Operations Runbook

This runbook separates repository configuration from checks that must be
provisioned and proven in the actual hosting account. Never mark a production
gate complete from a successful CI run alone.

## 1. Choose the hosting model

### Hostinger Shared / Cloud hosting (no Docker)

Use the Hostinger PHP + MySQL deployment path. It is a single application host:
- CACHE_STORE=database and BOOKING_LOCK_STORE=database use the shared MySQL
  cache-lock table and are suitable for one host.
- SESSION_DRIVER=database.
- QUEUE_CONNECTION=database; do not use sync in production because queued
  notifications would execute in the web request.
- Configure Hostinger hPanel SSL and use its cron interface for Laravel's
  scheduler and a short-lived queue worker.
- Shared hosting may not provide Redis, Supervisor, Docker, or persistent
  workers. Do not copy the VPS configuration below onto a shared plan.

Example cron entries (replace the directory and PHP binary with the values
shown by Hostinger; keep the queue timeout below the host's process limit):

~~~cron
* * * * * cd /home/ACCOUNT/domains/DOMAIN/public_html && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /home/ACCOUNT/domains/DOMAIN/public_html && /usr/bin/php artisan queue:work database --stop-when-empty --tries=3 --timeout=45 --max-time=50 >> storage/logs/queue-cron.log 2>&1
~~~

If the host supports flock, wrap the queue command with
flock -n /tmp/riwaj-queue.lock to prevent overlapping cron workers. Verify
that the cron account can write to storage/ and bootstrap/cache/. Check
Hostinger process execution limits before enabling this queue schedule.

### Hostinger VPS / another Linux VPS (Docker Compose)

Requirements: Docker Engine + Compose plugin, a domain you control, inbound
TCP 80/443, and enough disk/RAM for the app, MySQL, Redis, and backups.

1. Point the domain's apex and www A/AAAA records at the VPS. Remove stale
   AAAA records if the VPS has no working IPv6 listener.
2. Copy .env.production.example to .env; generate APP_KEY once and keep it
   backed up securely. Set APP_DOMAIN, APP_URL, strong unique DB_PASSWORD and
   DB_ROOT_PASSWORD, SMTP, and the remaining secrets.
3. Keep .env private (mode 600); never commit or send it in chat.
4. Build and start the stack:

~~~bash
chmod 600 .env
docker compose config --quiet
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize
~~~

The PHP image loads `deploy/php-production.ini`: 64 MB per uploaded file,
70 MB POST body, 120-second execution/input limits, and 256 MB memory. Nginx
allows 64 MB request bodies and mounts only the dedicated public-media volume
at `/storage`; private documents remain in the app-only storage volume.
Livewire temporary uploads are capped at 50 MB,
matching the largest vendor media validation rule. Keep the PHP, proxy, and
Livewire limits aligned if you change this contract.

5. Caddy terminates HTTPS and renews apex + www certificates automatically.
   DNS must already point to the VPS and ports 80/443 must be reachable. The
   app's PHP-FPM port, MySQL, and Redis are not published to the internet.
6. Docker Compose runs PHP-FPM, a Redis queue worker, and Laravel's scheduler
   under Supervisor. Check docker compose ps and
   docker compose logs --tail=200 app caddy.
7. The Docker stack uses Redis for cache, sessions, booking locks, and queues.
   The app container does not receive the MySQL root password.

The checked-in Caddyfile covers only the apex and www host. White-label
wildcard subdomains require a DNS-01 capable Caddy build and credentials for
the chosen DNS provider; do not assume an ordinary HTTP certificate covers
*.DOMAIN.

## 2. Health and release smoke checks

- /up is the lightweight Laravel liveness endpoint.
- /ready checks MySQL connectivity, the configured cache store, and the
  private filesystem. It returns only {"status":"ready"} or a generic 503
  response; it never returns connection strings or exception details.
- After a release, run the read-only external checks:

~~~bash
APP_URL=https://your-domain.example bash deploy/production-smoke.sh
~~~

The script checks HTTPS, the home page, /up, hidden-file protection, and
baseline security headers. It does not log in or mutate customer data.

## 3. Encrypted backups and restore drills (Docker VPS)

Install age and rclone on the VPS. Create an age key pair; keep the private
identity offline or in a separate secret manager. Configure an off-site rclone
remote (object storage with versioning/lifecycle retention). Do not store the
only backup on the same VPS or disk as MySQL.

Run a backup manually once, then schedule it daily:

~~~bash
AGE_RECIPIENT='age1YOUR_PUBLIC_RECIPIENT' \
RCLONE_REMOTE='remote:riwaj-grand-backups' \
bash deploy/backup-docker.sh
~~~

Store the age recipient and rclone remote in a root-readable, mode-600
environment file (for example /etc/riwaj-backup.env), not in the repository.
A daily root cron entry can load that file and run the script:

~~~cron
0 2 * * * root cd /var/www/riwaj-grand && set -a && . /etc/riwaj-backup.env && set +a && bash deploy/backup-docker.sh >> /var/log/riwaj-backup.log 2>&1
~~~

The script takes a transaction-consistent MySQL dump and a separate archive
of the persistent storage/app tree (public uploads and private documents),
compresses and encrypts both before writing final archives, calculates SHA-256
checksums, copies all encrypted archives and checksums off-site, and prunes
local archives older than 30 days. Configure the remote bucket's versioning
and lifecycle retention separately. Alert if the scheduled script exits
non-zero or either fresh archive is missing within 26 hours.

For each scheduled restore drill, provision a separate, empty staging MySQL
database whose name ends in _restore_drill (prefer a separate staging DB
server). Download an encrypted archive and run:

~~~bash
AGE_IDENTITY=/secure/path/age-identity.txt \
DB_DATABASE=riwaj_grand \
RESTORE_DB_HOST=staging-db.internal \
RESTORE_DB_DATABASE=riwaj_grand_restore_drill \
RESTORE_DB_USERNAME=restore_user \
RESTORE_DB_PASSWORD='STAGING_ONLY_PASSWORD' \
RESTORE_CONFIRM=riwaj_grand_restore_drill \
bash deploy/restore-drill.sh /secure/path/riwaj-grand-TIMESTAMP.sql.gz.age
~~~

The restore script refuses the configured production database, requires an
explicit matching confirmation, requires a pre-created empty staging database,
and leaves the restored data in staging for integrity and application smoke
checks. Restore the matching uploaded-file archive to a new, isolated staging
directory with deploy/restore-storage-drill.sh; it refuses to overwrite an
existing directory and requires a matching _restore_drill confirmation.
Never point the restore credentials at production. Validate row counts,
critical tenant/booking/payment relationships, media file counts, private-file
access controls, and a staging application boot before recording a successful
recovery exercise.

### Hostinger Shared/Cloud backup path

The Docker backup scripts do not run on a shared plan. In hPanel, enable the
plan's scheduled website and database backups if available, and take a fresh
manual backup before every migration. Also export the MySQL database through
phpMyAdmin and archive the complete application storage directory (including
private files) to a separate off-site location. Restore both into a separate
staging database and staging file tree; do not use the provider's in-place
production restore as a drill. Retain the dated backup IDs, checksums where
available, and the successful staging verification evidence. If the plan does
not provide scheduled backups or enough access to perform a restore drill,
that is a production blocker, not a completed checklist item.

## 4. Uptime monitoring and alerts (Docker VPS)

Start the optional Uptime Kuma service:

~~~bash
docker compose --profile monitoring up -d uptime-kuma
~~~

The console is bound to host loopback only. Reach it through an SSH tunnel:

~~~bash
ssh -L 3001:127.0.0.1:3001 USER@YOUR_SERVER
~~~

Open http://127.0.0.1:3001, create a unique admin account, then configure:
- HTTPS monitor for https://YOUR_DOMAIN/up (liveness).
- HTTPS monitor for https://YOUR_DOMAIN/ready (database/cache/private storage).
- HTTPS monitor for the public home page.
- Alert delivery to an independently managed email or messaging channel.
- Disk capacity and backup freshness checks on the VPS; Uptime Kuma's HTTP
  checks alone do not monitor database disk, queue depth, or failed-job counts.

Keep the monitoring console private; do not publish port 3001 or put it behind
an unauthenticated public route. Configure alert credentials in the Kuma UI,
not in Git.

## 5. SMTP and WhatsApp live delivery

### SMTP
Set real provider-issued MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD,
MAIL_ENCRYPTION, and verified MAIL_FROM_ADDRESS. Send a test message to an
address you control from the deployed application, confirm receipt and
spam-folder placement, and verify a queue-backed message completes with no
failed job. A configured SMTP hostname is not proof of delivery.

### WhatsApp Cloud API
Set WHATSAPP_VERIFY_TOKEN, WHATSAPP_APP_SECRET, WHATSAPP_ACCESS_TOKEN,
WHATSAPP_PHONE_NUMBER_ID, and the supported WHATSAPP_API_VERSION. Register
https://YOUR_DOMAIN/webhooks/whatsapp in the Meta developer console, enable
the required webhook subscription, and verify Meta's challenge succeeds.
Send a test message only to an explicitly approved test recipient; verify the
provider accepts it and the inbound signed webhook is validated. Keep secrets
out of Git and logs. The app intentionally remains in log/sandbox mode when
outbound credentials are absent.

## 6. DNS, TLS, and account security

- Confirm domain ownership and DNS records in the registrar/Hostinger panel.
- Confirm apex and www HTTPS certificates, automatic renewal, and HTTP to
  HTTPS redirect using an external TLS checker.
- If Cloudflare or another proxy is in front, configure only its official
  current proxy CIDRs in TRUSTED_PROXIES; never blindly trust arbitrary
  forwarded headers on a publicly reachable PHP app.
- Rotate demo/seed credentials, create the first Super Admin with a unique
  password, enable MFA, and store APP_KEY plus recovery credentials safely.
- Keep MySQL and Redis private; firewall all ports except the required web
  ports and tightly restricted administrative access.
- Record backup RPO/RTO, on-call owner, incident channel, and a rollback
  release before announcing public launch.

## 7. Evidence record

A production gate is complete only when the release ticket contains dated
evidence: DNS/TLS result, /up and /ready response, SMTP receipt, WhatsApp
test delivery and webhook verification, queue/scheduler process status, backup
archive plus off-site checksum, successful staging restore, monitoring alert
test, and authenticated staging smoke-test results.

Repository CI proves the code/build/test contract. It cannot manufacture
provider accounts, domain ownership, secret values, external delivery, or a
successful recovery exercise.
