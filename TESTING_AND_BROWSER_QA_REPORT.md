# Testing and Browser QA Report — Riwaj Grand

**Branch:** `audit/riwaj-grand-repair-20261126` · **Base:** `df8de437` · **Date:** 2026-11-26

## 1. How to reproduce

Local isolated test DB: MariaDB 10.4 at `127.0.0.1:3307`, database `wedding_hall_mysql_test`.  
Runner: `F:\opencode-scratch-archive\run-mysql-tests.ps1` (sets DB env, then `php artisan test`).  
Artifacts: `F:\opencode-scratch-archive\*.out`. Browser: `php artisan serve --host=127.0.0.1 --port=8000` + `npx playwright test` (Chromium, `playwright.config.ts`).

## 2. Static / build gates (this pass)

| Gate | Command | Result |
|---|---|---|
| PHP syntax | `php -l` over 386 PHP files | Initially 3 failures (duplicate import, doubled backslashes, duplicate method) — all fixed; final pass 0 failures |
| Pint | `vendor\bin\pint --test` | Initially failed on 13 inherited files; `pint` applied style fixes; final `--test` exit 0 |
| Blade integrity | compiled-view error on `/inventory` | Extra `@endif`/`@else` removed; `view:clear`; route/ready tests pass |
| `git diff --check` | `git diff --check` | exit 0 |
| Secret scan | source-only pattern scan (`app/routes/tests/config/database/resources`) | 0 possible secrets; no `.env` tracked |
| Route cache | `php artisan route:clear` then `route:list` | stale cache removed; `/ready` resolves |
| Frontend build | `npm run build` | Passed (previous verification round); unchanged frontend deps this pass |

## 3. Test runs actually executed

| Run | Result | Artifact |
|---|---|---|
| Targeted inherited-merge batch (12 files) | **11 failed, 2 skipped, 77 passed (272 assertions)** — merge syntax blockers, API verb/fixture errors, rate-limit state | `merge_targeted.out` |
| Post-repair targeted batch (11 files) | **3 failed, 1 skipped, 78 passed (283 assertions)** — remaining fixture/migration-order issues | `repair_round1.out` |
| Refund/API/vendor re-run | **42 passed (176 assertions)** | `repair_round2.out` |
| Full suite (first complete run after repair commit `9b7c387`) | **24 failed, 3 skipped, 578 passed (2,956 assertions)** — dominated by merge-broken Blade markup and RBAC drift | `full_suite_repair.out` |
| Focused re-run of 15 failing classes | **17 failed, 78 passed (489 assertions)**; root causes isolated (Blade, cache, role fixtures, API customer scope, limiter state) | `repair_failures_batch.out` |
| Full suite (final run on `f837283`-equivalent code) | **605 passed, 3 skipped, 0 failed (3,129 assertions)** | `full_suite_final.out` |
| CI on final head `f837283` | **SUCCESS — all jobs green** | https://github.com/mohsin-gohar/wedding_halls/actions/runs/45880136572 |
| CI on `3b819d0` | FAILURE at Composer audit (3 Symfony advisories) + MySQL tests; Playwright PASS; deploy skipped | https://github.com/mohsin-gohar/wedding_halls/actions/runs/45868534419 |

Failures were fixed at the root (app code, merge artifacts, or invalid test fixtures). No assertion was deleted or weakened to force a green result; the few expectation changes are documented in `COMPLETE_REPOSITORY_AUDIT.md` and were policy alignments (e.g., hidden-hall non-disclosure 404, source-linked expense protection).

## 4. Browser QA (Playwright, real Chromium)

Earlier verified rounds on this repository (artifacts in `F:\opencode-scratch-archive\playwright_round*.out`):

- `website.spec.ts` homepage/discovery scan, overflow widths (320/360/375/390/430/768/1024/1280/1440/1920), protected-route rejection, customer/tenant-route denial — passed in the rounds where rate-limit windows were clear.
- Super Admin / Tenant Owner / Customer login + authorized dashboards — **3/3 passed** when run after limiter window reset (`playwright_round4_login.out`).
- `responsive-proof.spec.ts` — **passed** when run after limiter window reset (`playwright_round6_responsive.out`).
- Observed flakiness traced to the app's `throttle:reload-protection` (5 GETs/min/IP+path on `/login` and `/rsvp`) and a transient `ERR_CONNECTION_CLOSED` from the dev server; all affected tests passed when run outside the limiter window. This is app behavior, not a hidden failure.

Live checks this mission: `GET /logout` → **405** (POST-only logout), real logout form click returns to `/login` with login form visible; seeded demo credentials verified via `password_verify` (never printed).

## 5. Personas and routes exercised

Guest/public pages; Super Admin (`/admin`, `/dashboard`); Tenant Owner (`/dashboard`, `/staff`, `/staff/payroll`, `/calendar`); Customer (`/customer/dashboard`, `/customer/events`); negative RBAC (customer → tenant routes, front desk → cancel API, floor staff → service boundary). Earlier sessions additionally exercised Manager, Accountant, Front Desk, Chef, Vendor and support personas through feature tests.

## 6. Blockers / not run

- Docker daemon unavailable locally → compose/Docker changes code-reviewed only.
- `composer audit` blocked (no network to packagist).
- Lighthouse/load testing not installed.
- Production/staging deployment not authorized or configured.
