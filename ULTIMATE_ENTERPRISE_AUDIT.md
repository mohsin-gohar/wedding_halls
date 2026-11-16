# ULTIMATE ENTERPRISE AUDIT — Riwaj Grand

**Date:** 2026-11-16 · **Branch:** `enterprise-hardening` (HEAD `15f8063` before this cycle's edits)

## 1. Phase Zero — verified baseline (source of truth)

| Item | Evidence | Result |
|---|---|---|
| Git state | `git branch --show-current`, `git log`, `git status` | `enterprise-hardening`, 1 commit ahead of `main`, working tree was clean |
| Stack | `php -v`, `php artisan --version`, `composer.json`, `package.json`, `node -v`, `npm -v` | PHP 8.2.12, Laravel 12.72.0, Livewire ^4.4, Tailwind ^4, Vite ^7, PHPUnit 11.5.50, Node v26.6.0, npm 11.18.0 |
| RBAC numbers | `PermissionMatrix::allPermissions()` / `allRoles()` via runtime PHP | **110 permissions / 21 roles** — claim CONFIRMED |
| Dependencies | `composer.json` | `pragmarx/google2fa ^9.1`, `endroid/qr-code ^6.0`, `spatie/laravel-permission ^6.25`, `laravel/pint ^1.24` |
| Tests on disk | `glob(tests/{Feature,Unit}/*.php)` | 35 Feature + 1 Unit = 36 files |
| Payment-proof route | `routes/web.php:420` + `DocumentController.php:229` | present & consistent |
| Commission logic | `ServiceBookingService.php:303,311,327` | `tenants.commission_rate` → `platform_settings.marketplace.commission.default_rate` (0.05) confirmed |
| Concurrency | `BookingService.php:47` `Cache::lock`, `:63` `DB::transaction`, row `lockForUpdate()` at `:66` | confirmed |

### Migration date discrepancy — RESOLVED (not fabricated)
- Migrations `2026_11_20_*` … `2026_12_07_*` **exist on disk** and `php artisan migrate:status` shows all `Ran` (batches 1–3).
- `git log --diff-filter=A -- database/migrations/2026_11_20_000001_create_subscription_plans.php` → commit `76a1997` dated **2026-11-11**; `2026_12_07_000001_add_two_factor_columns_to_users_table.php` → commit `ca39a9d` dated **2026-11-16**.
- Conclusion: the **filenames are forward-dated** relative to their commit dates — a naming-hygiene issue, not fabricated code. Future migrations created today would sort before them; this must be documented and avoided going forward.

## 2. Highest-risk defects reproduced (this cycle)

### Booking calendar
1. `CalendarShiftGrid.php:158` redundant `whereRaw('date(event_date) between ? and ?')` — duplicate of `whereBetween` on a DATE column; driver-coupled (not PostgreSQL-safe). **FIXED (removed).**
2. `CalendarShiftGrid.php:167` `$bookings[$key] ??= $b;` — **overlapping bookings silently hidden**; first row wins by DB order. **FIXED** — grouped reconciliation view + conflict warning + read-only modal.
3. Calendar displayed `INQUIRY` as occupied (`:157`) while conflict checks ignore it (`BookingService.php:186`) — inconsistent. **FIXED** — explicit non-blocking label `INQ`.
4. Legend claimed `On Hold (24H)` while **no expiry field or scheduler exists** (`routes/console.php` only schedules `subscriptions:check-expiry`). **FIXED** — honest label "On Hold (awaiting confirmation)".
5. `Api\BookingController.php:161` accepted `REFUNDED`, absent from the DB enum → MySQL strict-enum failure. **FIXED** — validation mirrors DB enum.
6. DB unique index on `(hall,event_date,slot)` was **dropped** (`2026_10_18_000001_relax_unique_constraint...:20-22`); safety rests on cache lock + `lockForUpdate()` (verified by `BookingConcurrencyTest` — 2 tests pass).

### Staff attendance / leave
7. `StaffAttendance.php:160-166` prefilled every unmarked employee as `PRESENT` 10:00→22:00; `:216` fallback `'PRESENT'`; DB column default `PRESENT` (`2026_10_11_000001...:46`). Saving the untouched register silently marked everyone present. **FIXED** — unmarked rows skipped; worked states require check-in; transaction added.
8. No check-out > check-in validation. **FIXED** (closure rule, both register and quick-edit).
9. `approveLeave():395` allowed self-approval, no overlap check, no attendance side-effect. **FIXED** — SoD 403, overlap rejection, ON_LEAVE records created on approval.
10. No visual attendance/leave month calendar — only a monthly **summary table**. **OPEN** (Phase 2 remainder).
11. Payroll reads **only overtime** from attendance (`StaffPayroll.php:102-105`); no absence/late/half-day/leave deductions; PAID rows editable (`:198-235` no status guard). **OPEN** (Phase 3).
12. No holidays / weekly-offs anywhere (grep: zero matches). **OPEN** (Phase 2 remainder).

## 3. Verification executed

```
php artisan test tests/Feature/CalendarIntegrityTest.php tests/Feature/AttendanceCoreRulesTest.php
→ 11 passed (28 assertions)

php artisan test tests/Feature/BookingConcurrencyTest.php tests/Feature/ErpStaffPayrollAndInventoryTest.php
  tests/Feature/StaffAttendanceUniqueConstraintRegressionTest.php
  tests/Feature/ComprehensiveDashboardCrudAuditTest.php tests/Feature/DashboardClickAuditTest.php
→ 21 passed (250 assertions)

OpenCode browser (authenticated owner@venuecloud.pk):
  /calendar → renders real booking, CNF status badge, month nav (Nov→Dec→Today), slot details modal, 0 console errors
  /staff/attendance → all status selects default to "NOT MARKED"; marked 1 employee 09:30→18:30, saved
    ("1 marked, 2 left unmarked"), reloaded → persisted, 0 console errors
```

## 4. Honest classification

| Area | Verdict |
|---|---|
| Hall booking ERP + concurrency engine | Implemented & verified |
| Booking calendar conflict **visibility** (this cycle) | Implemented & verified |
| Booking calendar views (month only) | Partial — week/day/agenda pending |
| Shift definitions (DB tables exist but unused, duplicated migration, slug mismatch) | Backend-only/dead schema |
| Hold expiry + scheduler | Missing (legend claim removed) |
| Attendance daily register + leave | Implemented, hardened this cycle |
| Visual attendance/leave calendar, holidays, weekly offs | Implemented this cycle (AttendanceCalendar, 7 tests, browser verified) |
| Payroll attendance-aware deductions, finalization lock | Partial |
| Live gateways, backups, monitoring | Requires credentials/infrastructure |

*Full-suite result, build and audits are recorded in `FINAL_ENTERPRISE_DELIVERY_REPORT.md`.*
