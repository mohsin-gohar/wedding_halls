# Page and interaction audit

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
