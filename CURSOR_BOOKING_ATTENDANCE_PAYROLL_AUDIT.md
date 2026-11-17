# Booking, attendance, and payroll

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
