# Security and database

## 17 Nov 2026

- Additive migration on the local SQLite file only. No `migrate:fresh`, no column drops.
- Backup drill: copied `database/database.sqlite` to a temp file, opened that copy with PDO, read `users=12` and `halls=3`, confirmed `tenants.shift_buffer_minutes` exists, then deleted the copy so a database file would not sit untracked in the repo (`storage/` is not gitignored).
- MySQL integration: blocked. `mysql.exe -u root` returned `ERROR 2002 (HY000): Can't connect to MySQL server on 'localhost' (10061)`.
- Holds without `hold_expires_at` are not cancelled. Expiry is written only when the tenant sets `hold_expiry_hours` or the caller passes a timestamp. Command writes `CANCELLED` plus `BOOKING_HOLD_EXPIRED` audit. Regression test passed.
- `composer audit`: no advisories. `npm audit`: 0. User `role` / `tenant_id` remain fillable.

## Verified in code and tests

- Document downloads authorize by tenant or `customer_id`, not by email string alone.
- Dashboard chat and `HallChat::setSelectedCustomer()` are tenant/staff scoped.
- Unapproved halls are not public on the chat route.
- Attendance corrections are a separate table (`attendance_corrections`) so the live row is not the only history.
- Finalised payroll blocks attendance edits for that month.

## Removed data-model risk

Hall sale columns are no longer part of the application model. The untracked migration `2026_11_17_000002_agency_property_listings.php` now only creates `listing_inquiries` for booking inquiries. If a local database already applied the earlier sale columns, those unused columns can remain until a deliberate migration drops them. That drop was not run against a live database.

## Not verified this session

- Live IDOR across two tenants in the browser.
- `composer audit` / `npm audit`.
- MySQL versus SQLite query differences. PHPUnit still uses SQLite memory (`phpunit.xml`).
- 2FA, webhook replay, and payment gateway sandbox.
