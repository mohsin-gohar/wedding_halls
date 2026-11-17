# BROWSER PERSONA QA REPORT — Riwaj Grand

**Date:** 2026-11-16 · Tool: OpenCode in-app Chromium · App: `php artisan serve --host=127.0.0.1 --port=9200`

## Persona exercised this cycle
**Venue Owner** (seeded demo account `owner@venuecloud.pk`, local isolated SQLite data — no production accounts/data).

## Flows executed (real browser, not HTTP-only)

### Authentication
1. `/login` → snapshot located email/password/SIGN IN fields; filled via browser tools; submitted.
2. Redirect observed → page title became "Hall Admin Dashboard". ✅

### Booking calendar `/calendar`
| Step | Result |
|---|---|
| Page load | ✅ "Shift Concurrency Matrix", real seeded booking (13 Nov, `CNF`, PKR 1,470,000) |
| Legend | ✅ "Confirmed Shift / On Hold (awaiting confirmation) / Inquiry (non-blocking) / Conflict warning / Available"; no "On Hold (24H)" |
| Month nav | ✅ Next → "December 2026"; Today → "November 2026" |
| Slot details | ✅ Clicked booked DINNER slot → modal with host, phone, date/shift, financials, agreement/receipt links |
| Console errors | **0** |

### Attendance Calendar `/staff/attendance/calendar` (implemented & verified this cycle)
| Step | Result |
|---|---|
| Page load (after applying new migration to local dev DB) | ✅ 3 staff rows, 30-day grid, full legend, monthly totals, **0 console errors** |
| Month nav | ✅ Next → December 2026; Today → November 2026 |
| Add holiday via UI modal | ✅ "Eid Milad un Nabi" on 25 Nov → appeared in holiday list + calendar cell, feedback shown, **0 console errors** |
| Cell deep-link | ✅ Clicking 1 Nov cell → `/staff/attendance?staff_id=2&date=2026-11-01` with "01 November 2026" displayed, **0 console errors** |
| Note | Initial load returned 500 because the new migration had not been applied to the local dev DB; `php artisan migrate` fixed it. Tests used in-memory RefreshDatabase and were already green. |

### Payroll `/staff/payroll` (Phase 3, authenticated owner)
| Step | Result |
|---|---|
| Page load | ✅ Calculation Policy + Salary Disbursement Registry, 0 console errors |
| Compile monthly payroll via UI | ✅ DRAFT rows created with attendance-derived breakdown columns |
| Approve (confirm dialog accepted) | ✅ Status badge became "APPROVED (LOCKED)", REOPEN action appeared, 0 console errors |
| Salary slip `/documents/salary-slip/{id}` | ✅ Opened with new attendance/deduction breakdown, 0 console errors |

### Earlier attendance register flow
- All status selects defaulted to `""` = **NOT MARKED**; marked one employee PRESENT 09:30→18:30, saved → "1 marked, 2 left unmarked"; reload → persisted. 0 console errors.

### Earlier cycle (public surfaces)
`/`, `/vendors`, `/site/nawab-caterers`, `/customer/events` (redirects to `/login`) — all loaded, 0 console errors; homepage screenshot captured.

## Personas NOT yet driven through the browser
SUPER_ADMIN, PLATFORM_FINANCE, PLATFORM_SUPPORT, MANAGER, ACCOUNTANT, FRONT_DESK, CHEF, HR, CUSTOMER, PHOTOGRAPHER, CATERER, DECORATOR, PLANNER. Their flows are covered by automated feature tests only. Honest gap — Phase 5 continuation.

## Mobile/tablet widths
Not exercised in this cycle. Calendar uses `overflow-x-auto` + `min-w-[980px]`; attendance table is wide — mobile overflow risk remains to be verified.

## Console/network
Console error arrays empty on all 6 URLs visited. No failed Livewire requests observed during the flows above.

## Independent server-log evidence (php artisan serve, 2026-11-16)
The dev server log recorded the actual browser session, corroborating the interactions:
```
19:20:53 /staff/attendance/calendar   (first load → 500, missing local migration)
19:21:24 /staff/attendance/calendar   (after php artisan migrate → 200)
19:21:36-19:22:02 /livewire-8f9155ee/update  (month nav, holiday modal/add)
19:22:26 /staff/attendance?staff_id=2&date=2026-11-01  (cell deep-link)
```
No 4xx/5xx after the migration was applied. New calendar server-log evidence:

```
19:20:53 /staff/attendance/calendar ................................. ~ 13s
19:21:24 /staff/attendance/calendar ................................. ~ 1s
19:21:36 /livewire-8f9155ee/update ................................. ~ 500ms
19:21:37 /livewire-8f9155ee/update ................................. ~ 0.12ms
19:22:02 /livewire-8f9155ee/update ................................. ~ 0.14ms
19:22:26 /staff/attendance ......................................... ~ 1s
```
No 4xx/5xx were logged after the migration fix. Payroll browser QA (same server, later session):
```
19:45:33 /staff/payroll
19:45:47 /livewire-8f9155ee/update     (compile payroll)
19:46:35 /livewire-8f9155ee/update     (approve via confirm dialog)
19:47:07 /livewire-8f9155ee/update     (state verification)
19:48:38 /documents/salary-slip/01a1e4d2-...  (payslip with breakdown)
```
No 4xx/5xx for these paths. Both server processes exited on the harness 1-hour timeout (SIGTERM) — restart with `php artisan serve --host=127.0.0.1 --port=9200` before further browser QA.
