# FINAL ENTERPRISE DELIVERY REPORT — Riwaj Grand

**Date:** 2026-11-16 · **Branch:** `enterprise-hardening` (working tree contains this cycle's edits — NOT committed, no push performed)

## 1. Source inspected (this cycle)
- Calendar: `app/Livewire/CalendarShiftGrid.php`, `resources/views/livewire/calendar-shift-grid.blade.php`, `app/Services/BookingService.php`, `app/Livewire/WalkInBookingModal.php`, `app/Http/Controllers/Api/BookingController.php`, 4 booking/shift migrations, `BookingConcurrencyTest.php`.
- Attendance/HR: `app/Livewire/StaffAttendance.php`, `resources/views/livewire/staff-attendance.blade.php`, attendance/leave migrations and models, `StaffPayroll.php`, 4 attendance/payroll suites.
- **New this cycle:** `app/Livewire/AttendanceCalendar.php`, `resources/views/livewire/attendance-calendar.blade.php`, `app/Models/AttendanceHoliday.php`, `database/migrations/2026_11_16_000001_create_attendance_holidays_table.php`, `routes/web.php`, `tests/Feature/AttendanceCalendarTest.php`.
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

Regression suites added: `tests/Feature/CalendarIntegrityTest.php` (4), `tests/Feature/AttendanceCoreRulesTest.php` (7), `tests/Feature/AttendanceCalendarTest.php` (7).

## 3. Automated tests executed — actual output
```
php artisan test tests/Feature/CalendarIntegrityTest.php tests/Feature/AttendanceCoreRulesTest.php --compact
→ Tests: 11 passed (28 assertions)

php artisan test tests/Feature/AttendanceCalendarTest.php --compact
→ Tests: 7 passed (21 assertions)

php artisan test tests/Feature/BookingConcurrencyTest.php tests/Feature/ErpStaffPayrollAndInventoryTest.php \
  tests/Feature/StaffAttendanceUniqueConstraintRegressionTest.php \
  tests/Feature/ComprehensiveDashboardCrudAuditTest.php tests/Feature/DashboardClickAuditTest.php --compact
→ Tests: 21 passed (250 assertions)

php artisan test --compact   (FINAL, after ALL edits — 2nd run; the 1st run was interrupted
                             by a transient duplicate import while routes/web.php was being edited)
→ Tests: 1 skipped, 213 passed (1268 assertions)
→ Duration: 443.27s
```
Prior authoritative baseline: 195 passed / 1 skipped (1219 assertions) → **+18 tests, +49 assertions**, no regressions.

```
vendor/bin/pint --test   → repaired with official Pint v1.24.0 phar (PHP 8.2 compatible)
                         → PASS — 289 files (local vendor phar was stale from another machine)
composer audit           → No security vulnerability advisories found.
npm audit                → found 0 vulnerabilities.
npm run build            → ✓ 58 modules, built in 3.74s (CSS 199.04 kB / JS 53.11 kB)
php artisan migrate:status → all Ran (incl. new 2026_11_16_000001_... [4])
```

## 4. Browser interactions executed (OpenCode Chromium)
- Authenticated as seeded local Venue Owner via `/login` UI.
- `/calendar`: real booking rendered (CNF, PKR 1,470,000), legend verified, next-month → December 2026, Today → November 2026, booked slot → details modal (host/financials/documents). **0 console errors.**
- `/staff/attendance`: all status selects default `NOT MARKED`; marked one employee PRESENT 09:30→18:30, saved → "1 marked, 2 left unmarked"; reloaded → persisted; other two still NOT MARKED. **0 console errors.**
- `/staff/attendance/calendar`: 3 staff × 30-day grid, legend, monthly totals; month nav Nov→Dec→Today; added holiday "Eid Milad un Nabi" via UI; clicked a cell → deep link opened the daily register on `01 November 2026`. **0 console errors.** First load returned 500 because the new migration had not been applied locally; `php artisan migrate` resolved it (tests were already green in in-memory DB).
- Public surfaces (earlier cycle): `/`, `/vendors`, `/site/nawab-caterers` → 0 console errors.

## 5. Features completed this cycle
1. Booking-calendar conflict visibility & honest status semantics.
2. Calendar query portability (SQLite/MySQL/PostgreSQL) removed of one driver-specific predicate.
3. Core HR business rule: **no auto-PRESENT** without explicit marking/check-in.
4. Leave: SoD, overlap prevention, attendance synchronization.
5. **Visual attendance calendar with holidays + weekly offs + leave overlay + daily-register deep links.**
6. 13 evidence-based report documents created (see root).

## 6. Still incomplete / blockers (honest)
- Visual attendance/leave month calendar, holidays, weekly offs — **now implemented this cycle** (employee filter + 8 states; configurable weekly-off day remains).
- Week/day/agenda calendar views; configurable DB-driven shifts; hold expiry + scheduler.
- Attendance-aware payroll deductions, APPROVED/PAID finalization lock.
- Live gateway sandbox round-trips (credentials), backup/restore drill, monitoring, error tracking.
- Browser personas beyond Venue Owner; mobile/tablet widths; Lighthouse/load tests.
- DB-level unique partial index for blocking bookings (defense-in-depth; requires duplicate-data audit).

## 7. Git state
`enterprise-hardening`, 1 commit ahead of `main` (`15f8063` from prior cycle). **This cycle's edits are uncommitted** (7 modified files + 13 reports + 5 new source/migration/test files; `git status --porcelain` → 27 entries). No commit/push performed — permission was not granted in this prompt.

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
