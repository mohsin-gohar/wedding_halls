> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# Missing features (honest)

Calendar week/day/agenda, configurable slot windows, buffer, and scheduled hold expiry are now in the app and covered by `CalendarViewsAndHoldExpiryTest`. They are no longer the next gap.

Still not built, and not claimed:

- Payroll reopen does not create a separate adjustment document.
- `shift_definitions` / `venue_shifts` are unused by the booking calendar.
- Email verification before customer booking visibility, required super-admin MFA, and removing `role` / `tenant_id` from mass assignment.
- Payment sandbox, MySQL CI, and a retained off-repo backup schedule.

Do not build these until the open booking and payroll gaps are closed.

1. Booking calendar week, day, and agenda views.
2. Configurable shift times and buffer minutes.
3. Scheduled hold expiry with an audit row.
4. Payroll adjustment workflow after a period is reopened (reopen exists; a formal adjustment document does not).
5. Email verification before a customer can see bookings.
6. MFA required for Super Admin.
7. Dispute, coupon, and dunning modules — not started; not required to remove the sale feature.

Localization, PWA, and push notifications were not added.
