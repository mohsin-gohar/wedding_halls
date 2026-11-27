# File-by-File Review Matrix — Riwaj Grand (independent re-audit)

**Branch:** `audit/riwaj-grand-repair-20261126` · **Base:** `df8de437` · **Date:** 2026-11-26  
**Tracked files after cleanup (git ls-files):** 559

## 1. Review depth legend

- **D** — Deep review: file opened, purpose/control flow understood, defect or risk assessed, change/test evidence recorded.
- **A** — Automated verification only: covered by PHP lint, Pint, `npm run build`, route:list, or executed tests; **not** a claim of individual manual review.
- **N** — Not individually reviewed: listed by directory with reason. Excluded from manual review but **not** excluded from automated gates.

Generated directories (`vendor/`, `node_modules/`, `public/build/`, compiled views, caches) are untracked/ignored. Dependency versions and advisories were audited via `composer.lock` and `composer audit` (final result: 0 advisories).

## 2. Directory summary

| Directory | Tracked | Review coverage |
|---|---:|---|
| `(root)` | 35 | D: Composer/npm manifests and locks, PHPUnit/Playwright config, env examples, Docker files, README and current authoritative reports. Historical snapshots are recoverable from Git history, not the active tree. |
| `.github` | 1 | D: `ci.yml` (quality, MySQL, browser and manual-only deploy gates). |
| `app` | 185 | D: security-sensitive controllers, middleware, policies, domain services, permission/tenant support and components tied to confirmed fixes. A/N: remaining Livewire components were syntax-checked and exercised through CI tests/browser coverage, not individually deep-read. |
| `bootstrap` | 3 | D: application bootstrap, providers and cache/exception/rate-limit wiring. |
| `config` | 18 | D: platform, services, filesystems, Livewire, database, app and rate-limit configs. A: remaining framework configs. |
| `database` | 84 | D: recent financial/booking/attendance migrations, seeders and schema changes involved in fixes. A/N: older migrations covered by clean MySQL migrations and tests, not all individually reviewed line-by-line. |
| `deploy` | 15 | D: Nginx/Caddy, supervisor, backup/restore scripts, production checklist and operations guidance. |
| `docker` | 1 | D: container entrypoint helpers. |
| `public` | 14 | A: public entrypoints and static assets; build integrity covered by CI. Binary assets not manually reviewed. |
| `resources` | 86 | D: key layouts and defect-related Blade views. A/N: remaining templates covered by CI Blade compilation/browser tests, not individually read. |
| `routes` | 3 | D: web, API and console routes; 109 routes registered in local inspection. |
| `storage` | 10 | N: framework placeholders only; runtime logs/uploads are ignored and not committed. |
| `tests` | 104 | D: added/changed security, finance, tenancy and workflow regression tests. Full suite executed in CI; not every test file individually read. |


## 3. Files personally opened and changed this re-audit

`app/Livewire/AdminDashboard.php`, `UtilityFuelTracker.php`, `InventoryTracker.php`, `StaffManager.php`; `app/Services/PlatformAnalyticsService.php`, `ServiceBookingService.php`; `app/Support/PermissionMatrix.php`; `resources/views/livewire/inventory-tracker.blade.php`; `composer.lock`; three new regression tests; plus the eight report documents. Each change is backed by a test, an audit result, or both.

## 4. Unreviewed files and reasons

- Historical `*.md` reports were removed from the active tree; their original content remains recoverable from Git history and is not current source of truth.
- Remaining Livewire/Blade files (~250): covered by syntax lint, Blade compilation through the full web route crawl, Playwright page loads, and feature tests; not individually manually reviewed. This is explicitly **NOT** a 100% manual file-review claim.
- Binary/static assets under `public/`: excluded from source review; build integrity verified by `npm run build` and browser asset loads.
- `vendor/`, `node_modules/`, compiled caches: generated, untracked; audited through lockfiles, `composer audit` and `npm run build`.

## 5. Verification evidence for this matrix

`composer audit --locked` → 0 advisories; `npm run build` → exit 0; `vendor/bin/pint --test` → exit 0; PHP lint over 386 tracked PHP files; `git ls-files` count 559; full MySQL suite artifact `full_suite_repair*.out`.

## 6. Continuation (2026-11-27)

- New migration `database/migrations/2026_12_23_000001_harden_financial_foreign_keys_and_unique_indexes.php` deep-reviewed and added (FK restrict + unique PO index with de-dupe).
- `config/livewire.php` and `deploy/nginx-riwaj-grand.conf` updated for upload consistency and rate limit (prior push corrected).
- No additional manual deep review of the remaining ~250 Livewire/Blade files; automated gates still pending local/CI re-run.

## 7. Final pass (2026-11-27, HEAD cb6ad80)

- Financial migration `2026_12_23_000001` deep-reviewed: de-dupe logic, unique indexes, restrictOnDelete, irreversibility documented with staging restore-drill commands.
- Production configs (Redis/queue/scheduler/mail/WhatsApp) reviewed; live verification blocked on owner credentials.
- CI on exact HEAD in progress (static gates largely green; MySQL/Playwright running). Prior green evidence retained.


## 8. Latest verification and cleanup

- GitHub CI run [45982335954](https://github.com/mohsin-gohar/wedding_halls/actions/runs/45982335954) on source HEAD `1b57773`: MySQL **609 passed / 3 skipped / 3,153 assertions**, Playwright **8 passed**, static/security/build/Docker gates **PASS**; deploy skipped intentionally.
- Uploaded ZIP inspection: 477 tracked PHP files pass `php -l`; Laravel registered 109 routes.
- Three tracked generated artifacts (`$null`, `migration_status.txt`, `routes.json`) were identified as shell/test/route-list outputs with no application references and removed.
- Sixty-four superseded root/audit Markdown reports were removed from the active tree after confirming `DOCS_STATUS.md` marked them historical; their original content remains recoverable from Git history.
- ZIP also contained ignored local dependency trees, environment files, logs and a nested duplicate project snapshot. These are not application source and are not committed.
- No application runtime code, database migration, route, dependency lockfile or production data was changed in this cleanup.
