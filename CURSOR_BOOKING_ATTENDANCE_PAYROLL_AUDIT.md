> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# Booking, attendance, and payroll

## 17 Nov 2026 — executed

- Salary source: employee `base_salary` if > 0, else policy `default_basic_salary` if > 0, else skip and show the names. Overnight attendance keeps the stored `overtime_hours` (test used 18:00–02:00, `overnight=true`, 2 hours → overtime pay 500 at the 250/hour policy). PAID rows are not regenerated.
- Calendar views: `CalendarShiftGrid` `viewMode` month/week/day/agenda. Shift windows and buffer live on the tenant and feed `BookingService` conflict checks. Default windows do not add new conflicts when buffer is 0. A 180-minute buffer blocks the default afternoon/evening gap. Test passed.
- `php artisan bookings:expire-holds` is scheduled hourly (`schedule:list`). A hold with no expiry stayed HOLD; an expired configured hold became CANCELLED with an audit row.
- Still open: a formal adjustment document after payroll reopen, and the global `shift_definitions` / `venue_shifts` tables are not wired into this calendar.

## Attendance and payroll — working tree

`StaffAttendance::saveEditedRecord()` writes `AttendanceCorrection` with old/new status, check-in, check-out, overtime, reason, and actor before updating the row. A reason is required for an existing record.

`markAllPresent()` sets status `PRESENT` and source `MANUAL_ATTESTATION`. It does not write 10:00 or 22:00. Save persists null punch times.

`checkOutSequenceError()` allows checkout earlier than check-in only when `overnight` is true.

`AttendanceCalendar::saveWeeklyOffs()` stores `tenants.weekly_off_days` (Carbon weekday numbers). A null config still defaults to Sunday so existing venues do not change. An explicit empty list means no weekly off.

`StaffPayroll` applies tenant `PayrollPolicy` late, absence, half-day, and unpaid-leave amounts. Approved or paid payroll rows are skipped on regenerate, and `periodLocked()` blocks attendance edits for that month.

Tests: 11 passed in the three attendance/payroll feature files.

## Booking calendar — still incomplete

`CalendarShiftGrid` remains a month shift matrix. These documented gaps are still open:

- Week, day, and agenda views.
- Configurable shift windows and buffer time.
- Automatic hold expiry with an audit log.

Conflict handling at booking-service level (lock plus `lockForUpdate`) already exists from earlier work. This session did not add a second calendar.
