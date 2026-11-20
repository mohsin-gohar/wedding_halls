> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# Page and interaction audit

## Browser evidence, 17 Nov 2026, `http://127.0.0.1:9000`

- Hall owner `owner@venuecloud.pk` signed in through the Livewire form and landed on `/dashboard` (Hall Admin Dashboard, three halls in the filter).
- `/calendar` rendered Month, Week, Day, and Agenda tabs, shift start/end inputs (12:00–16:00, 18:00–22:00, 22:00–02:00, 12:00–02:00), buffer, hold-expiry hours, and Save shift policy. Week view heading became `16 Nov – 22 Nov 2026` and the Week tab `aria-selected` was true. A confirmed evening booking for Hamza Ali was visible in the month grid.
- `/staff/payroll` rendered Calculation Policy, including the optional policy salary field, Compile Monthly Payroll, and existing slip/pay actions. Compile was not clicked, so no live salary rows were rewritten.
- Not executed this pass: customer, super admin, finance, support, moderator, manager, accountant, staff, HR, and each vendor login; password recovery; uploads; every button. Login-page demo buttons were covered by a non-interactive overlay at the desktop layout, so they were not activated.

This session did not click every control. Earlier browser sessions on `http://127.0.0.1:8010` opened the homepage, venues, a hall page, hall-owner dashboard, calendar, staff, expenses, media, inventory, and the Super Admin console. Hall owner hitting `/admin` returned 403.

## This session

| Surface | Result |
| --- | --- |
| Property sale UI (`/agencies`, `/agency/{slug}`, Agency Listings nav, For sale filter) | Removed from routes and views |
| Hall page reserve button | No longer hidden when a hall was marked for sale |
| Venue cards | Show base shift rent, not an asking price |
| Guest-count query `?guests=250` | Restored; small halls stay out of the result |

## Not clicked this session

Customer portal, payroll save, attendance calendar weekly-off form, vendor portals, password reset, file upload, and mobile widths 360–1920. Those stay unverified in the browser until a dedicated QA pass.
