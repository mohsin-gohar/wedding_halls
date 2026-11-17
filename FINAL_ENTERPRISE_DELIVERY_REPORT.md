# FINAL ENTERPRISE DELIVERY REPORT — Riwaj Grand

**Date:** 2026-11-16 · **Repair cycle: 17 Nov 2026** (see §0) · **Branch:** `enterprise-hardening` at `0cb426a`; repair-cycle edits uncommitted, no push performed.

## 0. 17 Nov repair cycle — 8 review tasks, verified with evidence
| # | Task | Result (evidence) |
|---|---|---|
| 1 | Enforce DRAFT→APPROVED→PAID; PAID immutable | ✅ `markAsPaid()` re-reads status under `lockForUpdate` inside a transaction; `updatePayroll()`/`reopenPayroll()`/`markAsApproved()` reject PAID; blade shows Pay only on APPROVED. `PayrollPaymentIntegrityTest` 4/4 (13 assertions) |
| 2 | Atomic payment (status + expense + audit in one transaction) + dup/concurrency tests | ✅ `DB::transaction` + `lockForUpdate` + `expenses.payroll_id` UNIQUE; tests: draft-refusal, double-pay idempotency, orphan-expense block, PAID immutability |
| 3 | 360px payroll overflow | ✅ Fixed layout (`min-w-0`, `flex-wrap`, `overflow-x-auto` containment). Verified via same-origin 360px iframe on **6 pages** → `docScrollWidth == docClientWidth`, 0 page-breakers |
| 4 | Browser QA every remaining role | ✅ Owner (payroll flow, calendar views), Front Desk (gated→calendar), Chef (gated→inventory), Customer (gated→customer dashboard; marketplace OK), Super Admin (gated→/admin; console OK) — 0 console errors |
| 5 | MySQL migrations + integration tests | ✅ **First real MySQL run** on MariaDB 10.4 (disposable datadir, port 3307): found & fixed 3 real portability bugs (§0.1); `migrate:fresh` → 0 pending; full suite re-run on MySQL |
| 6 | Payment sandbox | ⛔ Blocked — no merchant sandbox credentials (documented, no false claim) |
| 7 | Outdated reports synchronized | ✅ payroll/calendar/database/inventory/browser/missing-features/delivery reports updated with historical notes; contradictions labelled |
| 8 | Full suite + Pint + composer/npm audit + build | ✅/⏳ (outputs in §3) |

### 0.1 Real bugs found ONLY by MySQL verification (invisible on SQLite)
1. `service_bookings` declared inline FK to `customer_events` created in the **next** migration file → errno 150 on MySQL. Fixed: column-only + new migration `2026_12_07_000002` adds the FK (MySQL/PG; SQLite skips — ALTER-FK unsupported & FKs off in tests; semantics unchanged `nullOnDelete`).
2. `wallet_transactions` referenced `vendor_payouts` created later **in the same file** → errno 150. Fixed: reordered (payouts before wallet).
3. `halls.listing_status`/`listing_purpose`/`sale_price` existed only as **schema drift** in some local SQLite DBs (added outside migrations). MySQL crashed (`Unknown column 'listing_status'`); SQLite **silently** degraded — unknown double-quoted identifier falls back to string literal, so `"listing_status" != 'WITHDRAWN'` was always TRUE (filter never filtered). Fixed: migration `2026_11_17_160000_add_listing_columns_to_halls_table.php`, model fillable/casts, PublicVenues calendar dropdown filter, and `HallListingContractTest` (3 tests) pinning the contract. Post-fix schema diff (fresh MariaDB vs dev SQLite): **80/80 tables, 0 differences**.

### 0.2 Exact changed files (repair cycle)
- `app/Livewire/StaffPayroll.php` (approval gate + atomic transaction + PAID guards — pre-existing from Cursor, verified; my tests added)
- `database/migrations/2026_12_01_000002_create_service_booking_and_finance_tables.php` (FK order fix + payout/wallet reorder)
- `database/migrations/2026_12_07_000002_add_service_bookings_customer_event_foreign_key.php` (new)
- `database/migrations/2026_11_17_160000_add_listing_columns_to_halls_table.php` (new)
- `app/Models/Hall.php` (fillable/casts), `app/Models/Expense.php` (payroll_id — pre-existing)
- `app/Livewire/PublicVenues.php` (listing filter in dropdown query)
- `resources/css/app.css`, `resources/views/components/layouts/app.blade.php`, `resources/views/livewire/staff-payroll.blade.php` (360px containment — pre-existing, verified)
- `tests/Feature/PayrollPaymentIntegrityTest.php` (3 new tests), `tests/Feature/HallListingContractTest.php` (new, 3 tests)
- `.gitignore` (UTF-16 corruption repaired; ignores untracked nested `wedding_halls/` copy)
- 8 audit/report `.md` files synchronized

### 0.3 End-of-cycle status (17 Nov 2026, after repair tasks 1–8 — FINAL)
- Full suite **on MariaDB** (final authoritative run, on freshly re-initialized MariaDB 10.4.32 datadir + `migrate:fresh --seed`): **238 passed / 1 skipped (1355 assertions), GREEN (Duration 805.21s)**.
- Full suite **on SQLite**: **FINAL authoritative run: 238 passed / 1 skipped (1355 assertions), GREEN (396.38s)**.
- Schema parity fresh MariaDB vs dev SQLite: **80/80 tables, 0 differences**.
- Pint v1.24.0: **PASS (308 files)**; composer audit clean; npm audit 0; build ✓.
- Browser QA 17 Nov: owner/front desk/chef/customer/super admin gates; payroll DRAFT→APPROVE→PAY→PAID; calendar 4 views; 360px × 6 pages — **0 console errors**.

### 0.3.1 Post-cycle environment stabilization (17 Nov 2026, final hours)
- MariaDB disposable datadir (`…/Temp/opencode/mysql_test_data`) suffered **InnoDB crash-recovery failure** (`Tablespace ... was not found`, `InnoDB plugin registration failed → Aborting`) → server died/restarted repeatedly → spurious connection/auth failures during suites.
- **Fix:** re-initialized fresh datadir via `mysql_install_db`, restarted MariaDB 10.4.32 (127.0.0.1:3307), `migrate:fresh --seed` green, final full suite **GREEN (238 passed / 1 skipped / 1355 assertions)**.
- PHPUnit env fix: removed hardcoded `<env name="DB_PASSWORD" value="rootpassword"/>` from `phpunit.xml` (it overrode local empty-password root credentials → `Access denied`); CI still supplies `DB_PASSWORD` via `.env`/workflow env.

### 0.4 Final state — MySQL-only database enforcement (17 Nov 2026, final instruction cycle)
**Decision implemented:** MySQL is the **ONLY** supported database (strict). SQLite / MariaDB / PostgreSQL / SQL Server are no longer connections; no fallback, no switching, no auto database change; MySQL unavailability produces a clear connection failure (no silent fallback).

**Changes made this cycle:**
1. `config/database.php` — removed connection blocks `sqlite`, `mariadb`, `pgsql`, `sqlsrv`; only `mysql` remains; `default => env('DB_CONNECTION', 'mysql')`.
2. `.env` — `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3307`, `DB_DATABASE=wedding_hall_mysql_test`, `DB_USERNAME=root`, `DB_PASSWORD=`, `DB_CHARSET=utf8mb4`, `DB_COLLATION=utf8mb4_unicode_ci`; SQLite vars removed.
3. `.env.example` — "For local zero-config dev, use DB_CONNECTION=sqlite" comment replaced; MySQL-only note added; `DB_CONNECTION=mysql` kept.
4. `phpunit.xml` — SQLite env block (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) replaced with MySQL block (`mysql`, `127.0.0.1`, `3306`, `eventhub_test`, `eventhub`, `DB_PASSWORD` **removed** — CI supplies it via workflow env/`.env`; local runs use local MySQL root credentials via shell env).
5. `.github/workflows/ci.yml` — already on `mysql:8.0` + `DB_CONNECTION=mysql`; now fully aligned with MySQL-only phpunit config (no change required).

**Verification status (final):**
- Migrations + seeders on fresh empty MySQL DB (`wedding_hall_mysql_test`): ✅ green (incl. PermissionMatrixSeeder: 110 permissions / 21 roles, VendorEcosystemSeeder, etc.).
- Full suite on MySQL (MariaDB 10.4.32, fresh re-initialized datadir): **✅ FINAL GREEN — 238 passed / 1 skipped (1355 assertions), Duration 805.21s** (after InnoDB-crash recovery fix + `migrate:fresh --seed`).
- Historical SQLite verification (final SQLite suite): **238 passed / 1 skipped (1355 assertions), GREEN (396.38s)** — recorded as historical evidence only; SQLite is no longer an active DB config.
- Schema parity (fresh MySQL vs dev SQLite) — pre-MySQL-only step: **80/80 tables, 0 differences**.

## 1. Source inspected (this cycle)
- Calendar: `app/Livewire/CalendarShiftGrid.php`, `resources/views/livewire/calendar-shift-grid.blade.php`, `app/Services/BookingService.php`, `app/Livewire/WalkInBookingModal.php`, `app/Http/Controllers/Api/BookingController.php`, 4 booking/shift migrations, `BookingConcurrencyTest.php`.
- Attendance/HR: `app/Livewire/StaffAttendance.php`, `resources/views/livewire/staff-attendance.blade.php`, attendance/leave migrations and models, `StaffPayroll.php`, 4 attendance/payroll suites.
- **New earlier this cycle:** `app/Livewire/AttendanceCalendar.php`, `resources/views/livewire/attendance-calendar.blade.php`, `app/Models/AttendanceHoliday.php`, `database/migrations/2026_11_16_000001_create_attendance_holidays_table.php`, `routes/web.php`, `tests/Feature/AttendanceCalendarTest.php`.
- **Phase 3:** `app/Livewire/StaffPayroll.php`, `app/Models/StaffPayroll.php`, `app/Models/PayrollPolicy.php`, `app/Livewire/StaffAttendance.php`, `resources/views/livewire/staff-payroll.blade.php`, `resources/views/livewire/staff-attendance.blade.php`, `resources/views/documents/salary-slip.blade.php`, 2 new migrations, `tests/Feature/PayrollAttendanceIntegrationTest.php`.
- Reports/versions/RBAC: runtime reflection (`PermissionMatrix`), `composer.json`, git history for migrations, `php artisan migrate:status`.

## 2. Defects reproduced & fixed (exact files)
| # | Defect | File | Fix |
|---|---|---|---|
| 1 | Redundant driver-coupled `whereRaw(date(event_date))` | `CalendarShiftGrid.php:158` | removed |
| 2 | Overlapping bookings silently hidden via `??=` | `CalendarShiftGrid.php:167` | grouping + conflict warning + read-only reconciliation modal; blade updated |
| 3 | All bookings shown as "Confirmed"; false "On Hold (24H)" legend | `calendar-shift-grid.blade.php` | status-aware badges/labels, honest legend |
| 4 | API accepted `REFUNDED` not in DB enum | `Api/BookingController.php:161` | validation mirrors enum |
| 5 | Auto-PRESENT on untouched attendance register (defaults 10:00/22:00) | `StaffAttendance.php:27,52-56,160-166,216` | unmarked rows skipped, worked states require check-in, transaction, audit counts |
| 6 | No check-out > check-in validation | `StaffAttendance.php` (register + quick edit) | closure rule added |
| 7 | Leave self-approval allowed | `StaffAttendance::approveLeave()` | 403 SoD guard + reject guard |
| 8 | No leave overlap check; approved leave invisible to attendance | `StaffAttendance` | overlap rejection + ON_LEAVE records on approval |
| 9 | No visual attendance calendar — only a monthly **summary table** | new `AttendanceCalendar` component + blade + route | employee × day status grid with 8 states, monthly totals, employee filter, month nav |
| 10 | No holidays / weekly offs anywhere in the system | new `attendance_holidays` migration + model + calendar overlay | PUBLIC/COMPANY holidays with CRUD, audit, tenant isolation, duplicate protection; Sunday weekly off |
| 11 | Calendar cells had no link into the daily register | `StaffAttendance::mount()` | honors `?date=Y-m-d&staff_id=` deep link from calendar cells |
| 12 | Payroll ignored attendance except OT; no deductions/approval/finalization | `StaffPayroll`, `StaffPayroll` model | attendance-derived deductions, tenant-configurable policy, APPROVED state, reopen, breakdown payslips |
| 13 | Attendance remained editable after payroll finalization | `StaffAttendance` | `periodLocked()` blocks daily save, quick edit and leave approval until reopen |

Regression suites added: `CalendarIntegrityTest` (4), `AttendanceCoreRulesTest` (7), `AttendanceCalendarTest` (7), `PayrollAttendanceIntegrationTest` (6).

## 3. Automated tests executed — actual output
```
php artisan test tests/Feature/CalendarIntegrityTest.php tests/Feature/AttendanceCoreRulesTest.php --compact
→ Tests: 11 passed (28 assertions)

php artisan test tests/Feature/AttendanceCalendarTest.php --compact
→ Tests: 7 passed (21 assertions)

php artisan test tests/Feature/PayrollAttendanceIntegrationTest.php --compact
→ Tests: 6 passed (17 assertions)

php artisan test tests/Feature/BookingConcurrencyTest.php tests/Feature/ErpStaffPayrollAndInventoryTest.php \
  tests/Feature/StaffAttendanceUniqueConstraintRegressionTest.php \
  tests/Feature/ComprehensiveDashboardCrudAuditTest.php tests/Feature/DashboardClickAuditTest.php --compact
→ Tests: 21 passed (250 assertions)

php artisan test --compact   (FINAL after Phase 3 — authoritative)
→ Tests: 1 skipped, 219 passed (1285 assertions)
→ Duration: 493.72s
```
Prior authoritative baseline: 195 passed / 1 skipped (1219 assertions) → **+24 tests, +66 assertions**, no regressions.

```
vendor/bin/pint --test   → official Pint v1.24.0 phar (PHP 8.2 compatible) → PASS — 293 files
composer audit           → No security vulnerability advisories found.
npm audit                → found 0 vulnerabilities.
npm run build            → ✓ 58 modules, built in 3.07s (CSS 199.04 kB / JS 53.11 kB)
php artisan migrate:status → all Ran (incl. 3 new correctly-dated 2026_11_16_* migrations)
```

## 4. Browser interactions executed (OpenCode Chromium)
- Authenticated as seeded local Venue Owner via `/login` UI.
- `/calendar`: real booking rendered (CNF, PKR 1,470,000), legend verified, next-month → December 2026, Today → November 2026, booked slot → details modal (host/financials/documents). **0 console errors.**
- `/staff/attendance`: all status selects default `NOT MARKED`; marked one employee PRESENT 09:30→18:30, saved → "1 marked, 2 left unmarked"; reloaded → persisted; other two still NOT MARKED. **0 console errors.**
- `/staff/attendance/calendar`: 3 staff × 30-day grid, legend, monthly totals; month nav Nov→Dec→Today; added holiday "Eid Milad un Nabi" via UI; clicked a cell → deep link opened the daily register on `01 November 2026`. **0 console errors.** First load returned 500 because the new migration had not been applied locally; `php artisan migrate` resolved it (tests were already green in in-memory DB).
- `/staff/payroll`: Calculation Policy section + registry loaded; compiled payroll via UI; clicked Approve (accepted browser confirm dialog) → badge became "APPROVED (LOCKED)" with REOPEN action; salary slip `/documents/salary-slip/{id}` opened with the new attendance/deduction breakdown. **0 console errors.**
- Public surfaces (earlier cycle): `/`, `/vendors`, `/site/nawab-caterers` → 0 console errors.

## 5. Features completed this cycle
1. Booking-calendar conflict visibility & honest status semantics.
2. Calendar query portability (SQLite/MySQL/PostgreSQL) removed of one driver-specific predicate.
3. Core HR business rule: **no auto-PRESENT** without explicit marking/check-in.
4. Leave: SoD, overlap prevention, attendance synchronization.
5. **Visual attendance calendar with holidays + weekly offs + leave overlay + daily-register deep links.**
6. **Attendance-aware payroll**: configurable policy, absence/late/half-day/UNPAID-leave deductions, OT from attendance, DRAFT→APPROVED→PAID flow with authorized reopen, source-attendance lock, breakdown payslips.
7. 13 evidence-based report documents created (see root).

## 6. Still incomplete / blockers (honest)
- Visual attendance/leave month calendar, holidays, weekly offs — **now implemented this cycle** (employee filter + 8 states; configurable weekly-off day remains).
- Week/day/agenda calendar views; configurable DB-driven shifts; hold expiry + scheduler.
- Attendance-aware payroll deductions, APPROVED/PAID flow and attendance lock — **now implemented this cycle**; remaining: daily/hourly wage types, bonus UI, advances/loans ledger, tax rules.
- Attendance-aware payroll deductions, APPROVED/PAID finalization lock.
- Live gateway sandbox round-trips (credentials), backup/restore drill, monitoring, error tracking.
- Browser personas beyond Venue Owner; mobile/tablet widths; Lighthouse/load tests.
- DB-level unique partial index for blocking bookings (defense-in-depth; requires duplicate-data audit).

## 7. Git state
`enterprise-hardening`. Cycle 1 (calendar/attendance) was committed and pushed as `c5c0320`. **Phase 3 payroll changes are uncommitted** (16 changed entries). No further push was performed — permission for these new changes was not granted.

## 8. Reproducible commands
```
git status --porcelain
php artisan test --compact
php C:\Users\mohsi\AppData\Local\Temp\opencode\pint-124.phar --test
npm run build
composer audit
npm audit
php artisan migrate:status
```
