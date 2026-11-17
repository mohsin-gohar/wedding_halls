# Vendor, customer, and admin

## Removed

The agency property marketplace was not a wedding-vendor portal. It listed halls for sale. Those routes and screens are gone. Existing vendor ERP (service vendors, bookings, wallets) from `main` / `ca39a9d` was not rewritten in this pass.

## Still in the product

- Super Admin console is role-gated (`EnsureSuperAdmin`). A hall owner received HTTP 403 on `/admin` in an earlier browser session.
- Customer documents require `customer_id`, not a matching email string.
- Hall inquiry on the public hall page stores `purpose = BOOKING` only.

## Not retested this session

Vendor RFQ award, payout approval, customer registration, and platform finance versus support permission splits were not exercised in the browser.
