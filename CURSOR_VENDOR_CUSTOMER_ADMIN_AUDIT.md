> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# Vendor, customer, and admin

## 17 Nov 2026

Browser session confirmed the hall-owner shell: dashboard, calendar, and payroll. Vendor, customer, super admin, and the other staff roles were not signed in during this pass. Route count from `route:list --json` is 114. Scheduler shows `subscriptions:check-expiry` daily and `bookings:expire-holds` hourly.

## Removed

The agency property marketplace was not a wedding-vendor portal. It listed halls for sale. Those routes and screens are gone. Existing vendor ERP (service vendors, bookings, wallets) from `main` / `ca39a9d` was not rewritten in this pass.

## Still in the product

- Super Admin console is role-gated (`EnsureSuperAdmin`). A hall owner received HTTP 403 on `/admin` in an earlier browser session.
- Customer documents require `customer_id`, not a matching email string.
- Hall inquiry on the public hall page stores `purpose = BOOKING` only.

## Not retested this session

Vendor RFQ award, payout approval, customer registration, and platform finance versus support permission splits were not exercised in the browser.
