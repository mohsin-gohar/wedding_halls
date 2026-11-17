# Security and database

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
