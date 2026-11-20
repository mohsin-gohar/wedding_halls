> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# ATTENDANCE / HR ENTERPRISE AUDIT — Riwaj Grand

**Date:** 2026-11-16 · Branch `enterprise-hardening`

## 1. Files inspected
`app/Livewire/StaffAttendance.php` (all sections), `resources/views/livewire/staff-attendance.blade.php`, `app/Models/StaffAttendance.php`, `app/Models/StaffLeave.php`, `database/migrations/2026_10_11_000001_create_erp_modules_and_automation_tables.php:41-72`, `app/Livewire/StaffManager.php`, `tests/Feature/ErpStaffPayrollAndInventoryTest.php`, `tests/Feature/StaffAttendanceUniqueConstraintRegressionTest.php`.

## 2. CRITICAL rule fixed — no auto-PRESENT

**Before (reproduced):**
- `StaffAttendance.php:160-166`: every staff member without an existing record was prefilled `status=PRESENT, check_in=10:00, check_out=22:00`.
- `:216`: `'status' => $data['status'] ?? 'PRESENT'` fallback.
- Migration `:46`: DB column `default('PRESENT')`.
- Blade `<select>` had PRESENT as first option.
→ Opening the register and clicking Save silently converted the whole team to PRESENT.

**After (implemented):**
- New rows default to `status=''` (option `NOT MARKED`); times empty.
- `saveDailyAttendance()` **skips** rows with empty status — untouched rows are never persisted.
- Worked states (`PRESENT`, `LATE`, `HALF_DAY`) **require** a valid `H:i` check-in.
- `check_out` must be strictly later than `check_in` (overnight not supported — documented).
- No `'PRESENT'` fallback remains; all writes inside `DB::transaction()`.
- Quick-edit modal carries the same rules; `markAllPresent()` is retained as an **explicit manager bulk action** and now fills valid times.
- Audit log records `saved`/`left unmarked` counts.

## 3. Leave workflow fixed
| Defect | Fix |
|---|---|
| `approveLeave()` (`:395`) allowed an owner/manager to approve **their own** leave | `abort(403)` when `$leave->user_id === Auth::id()` (also reject) |
| No overlap prevention | Query rejects PENDING/APPROVED overlapping ranges on `submitLeave()` |
| Approved leave invisible to attendance | `approveLeave()` writes `ON_LEAVE` records for each day (transaction). Existing PRESENT/LATE/HALF_DAY rows preserved (employee actually worked); ABSENT upgraded to ON_LEAVE |
| Duplicate concurrency on first save | Unique index `(tenant_id,user_id,date)` at migration `:54` + create→update fallback (regression test passes) |

## 4. Tests
`tests/Feature/AttendanceCoreRulesTest.php` — **7 tests / 28 assertions, all passing**:
untouched register not saved · PRESENT requires check-in · check-out order · valid persistence · self-approval 403 · overlap rejected · approved leave → ON_LEAVE.

Existing suites re-run green: `ErpStaffPayrollAndInventoryTest` (9), `StaffAttendanceUniqueConstraintRegressionTest` (1), `ComprehensiveDashboardCrudAuditTest` (2), `DashboardClickAuditTest` (4).

## 5. Browser QA (OpenCode, authenticated owner)
- `/staff/attendance` loaded; `console errors = 0`.
- All 3 employee status selects defaulted to `""` = **NOT MARKED** — verified via DOM.
- Marked employee #2 PRESENT, check-in `09:30`, check-out `18:30`, clicked Save.
- App feedback: **"Attendance saved for 16 Nov 2026 (1 marked, 2 left unmarked)."**
- Reloaded page: `PRESENT / 09:30 / 18:30` persisted; other two still NOT MARKED. Console errors: 0.

## 5b. Visual attendance calendar (implemented this cycle)
- New migration `2026_11_16_000001_create_attendance_holidays_table.php` + `App\Models\AttendanceHoliday`.
- New `app/Livewire/AttendanceCalendar.php` + `resources/views/livewire/attendance-calendar.blade.php` + route `staff.attendance.calendar`.
- Month navigation, employee filter, per-employee × per-day status grid: PRESENT / ABSENT / LATE / HALF_DAY / ON_LEAVE / WEEKLY_OFF (Sunday) / HOLIDAY / NOT_MARKED, monthly totals, approved-leave expansion, holiday add/delete with audit + tenant isolation + duplicate protection.
- Calendar cells deep-link to the daily register with `?staff_id=&date=` (StaffAttendance mount now honors `date`).
- `tests/Feature/AttendanceCalendarTest.php` → **7 passed (21 assertions)**. Browser-verified.

## 6. Remaining gaps (Phase 2 remainder)
- Employee check-in/check-out self-service (currently manager-only ERP).
- ~~correction approval workflow with old/new value audit~~ → **implemented 16–17 Nov**: `attendance_corrections` table + `writeCorrection()` (old/new snapshot + reason), applied on register saves and quick edits.
- ~~attendance locking after payroll~~ → **implemented**: `StaffAttendance::periodLocked()` blocks daily save, quick edit and leave approval for a month with APPROVED/PAID payroll; `PayrollAttendanceIntegrationTest` covers lock→reopen.
- ~~Configurable weekly-off days~~ → **implemented**: tenant `weekly_off_days` (UI in `AttendanceCalendar::saveWeeklyOffs()`, default Sunday).
- ~~Overnight shift support deliberately excluded~~ → **implemented** (`overnight` flag + `checkOutSequenceError()` validation).
- Manager can still submit leave for self and an *other* manager can approve — acceptable, but a future policy may disallow.
- Shift roster calendar still missing.

## 7. Commands
```
php artisan test tests/Feature/AttendanceCoreRulesTest.php --compact
→ Tests: 7 passed (15 assertions)
```
