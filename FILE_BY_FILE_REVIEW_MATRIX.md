# File-by-File Review Matrix — Riwaj Grand (independent re-audit)

**Branch:** `audit/riwaj-grand-repair-20261126` · **Base:** `df8de437` · **Date:** 2026-11-26  
**Tracked files (git ls-files):** 617

## 1. Review depth legend

- **D** — Deep review: file opened, purpose/control flow understood, defect or risk assessed, change/test evidence recorded.
- **A** — Automated verification only: covered by PHP lint, Pint, `npm run build`, route:list, or executed tests; **not** a claim of individual manual review.
- **N** — Not individually reviewed: listed by directory with reason. Excluded from manual review but **not** excluded from automated gates.

Generated directories (`vendor/`, `node_modules/`, `public/build/`, compiled views, caches) are untracked/ignored. Dependency versions and advisories were audited via `composer.lock` and `composer audit` (final result: 0 advisories).

## 2. Directory summary

| Directory | Tracked | Review coverage |
|---|---:|---|
| `(root)` | 93 | D: `composer.json`, `composer.lock`, `phpunit.xml`, `phpunit.local.xml`, `playwright.config.ts`, `package.json`, `.env.example`, `.env.production.example`, `Dockerfile`, `docker-compose.yml`, `README.md`, `DOCS_STATUS.md` and the eight current audit/acceptance reports. A/N: historical audit/roadmap markdown (superseded per `DOCS_STATUS.md`; not individually re-read this pass). |
| `.github` | 1 | D: `ci.yml` (all jobs, quality/test/browser/deploy gates, rate-limit env). |
| `.kilo` | 2 | N: local agent plan artifacts, not application source. |
| `app` | 184 | D: controllers (`Api/*`, `DocumentController`, `WhatsAppWebhookController`, `ApiTokenController`), middleware (all), policies, services (`ServiceBookingService`, `BookingService`, `PaymentService`, `SubscriptionService`, `PlatformAnalyticsService`, `HallAvailabilityService`), `Support/*` (PermissionMatrix, CashOnlyPayments, PlatformMetrics, TenantAccess), models touched by fixes, and Livewire components relevant to the confirmed defects (AdminDashboard, Dashboard, ExpenseTracker, InventoryTracker, UtilityFuelTracker, StaffManager, auth components, walk-in, vendor boards). A/N: remaining components were syntax/style-checked and exercised indirectly by the feature suite, not individually read. |
| `audit` | 7 | N: historical audit snapshots; superseded. |
| `bootstrap` | 3 | D: `app.php`, `providers.php`, `cache/*` behaviour (rate limiters, middleware, exception rendering). |
| `config` | 18 | D: `platform.php`, `services.php`, `filesystems.php`, `livewire.php`, `database.php`, `app.php`, `rate-limits.php`. A: remaining framework configs. |
| `database` | 81 | D: all `2026_11_26_*` and new `2026_12_22_*` migrations; cash-only integrity migrations; service-booking/finance migrations; `DatabaseSeeder`, `VendorCategorySeeder`, `PermissionMatrixSeeder`. A/N: earlier migrations reviewed via schema/test coverage (`migrate:fresh` on isolated DB), not individually line-by-line. |
| `deploy` | 15 | D: `supervisor.conf`, `nginx*.conf`, `Caddyfile`, backup/restore drills, `PRODUCTION_CHECKLIST.md`, `php-production.ini`, `entrypoint.sh`. |
| `docker` | 1 | D: entrypoint helpers. |
| `public` | 14 | A: static assets/entrypoints; build output verified. N: binary/asset files excluded from source review by policy. |
| `resources` | 86 | D: `inventory-tracker.blade.php`, `public-hall-details.blade.php`, `dashboard*.blade.php`, `expense-tracker.blade.php`, layouts (public/app), auth views. A/N: remaining Blade templates compiled via `view:clear` + test/browser rendering; not individually read. |
| `routes` | 3 | D: `web.php`, `api.php`, `console.php` (all route groups, middleware, schedules). |
| `storage` | 10 | N: placeholders/logs/fixtures, not application source. |
| `tests` | 99 | D: all newly added/edited tests plus the hardening suites cited in reports. A: full suite executed; remaining test files verified by execution rather than individual reading. |

## 3. Files personally opened and changed this re-audit

`app/Livewire/AdminDashboard.php`, `UtilityFuelTracker.php`, `InventoryTracker.php`, `StaffManager.php`; `app/Services/PlatformAnalyticsService.php`, `ServiceBookingService.php`; `app/Support/PermissionMatrix.php`; `resources/views/livewire/inventory-tracker.blade.php`; `composer.lock`; three new regression tests; plus the eight report documents. Each change is backed by a test, an audit result, or both.

## 4. Unreviewed files and reasons

- Historical `*.md` reports (root, `audit/`, `.kilo/`): superseded by `DOCS_STATUS.md`; claims were checked against code instead of re-reading every document.
- Remaining Livewire/Blade files (~250): covered by syntax lint, Blade compilation through the full web route crawl, Playwright page loads, and feature tests; not individually manually reviewed. This is explicitly **NOT** a 100% manual file-review claim.
- Binary/static assets under `public/`: excluded from source review; build integrity verified by `npm run build` and browser asset loads.
- `vendor/`, `node_modules/`, compiled caches: generated, untracked; audited through lockfiles, `composer audit` and `npm run build`.

## 5. Verification evidence for this matrix

`composer audit --locked` → 0 advisories; `npm run build` → exit 0; `vendor/bin/pint --test` → exit 0; PHP lint over 386 tracked PHP files; `git ls-files` count 617; full MySQL suite artifact `full_suite_repair*.out`.

## 6. Continuation (2026-11-27)

- New migration `database/migrations/2026_12_23_000001_harden_financial_foreign_keys_and_unique_indexes.php` deep-reviewed and added (FK restrict + unique PO index with de-dupe).
- `config/livewire.php` and `deploy/nginx-riwaj-grand.conf` updated for upload consistency and rate limit (prior push corrected).
- No additional manual deep review of the remaining ~250 Livewire/Blade files; automated gates still pending local/CI re-run.
