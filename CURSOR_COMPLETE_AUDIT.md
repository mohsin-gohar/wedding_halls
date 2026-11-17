# Complete audit snapshot

Branch: `enterprise-hardening`  
HEAD: `c5c0320` (`feat: attendance calendar, holiday management and enterprise booking/attendance integrity`)  
`main` is `ca39a9d`. This branch is two commits ahead of `main` (`15f8063`, `c5c0320`).  
A pointer branch `backup/pre-attendance-integrity-2026-11-17` exists at the same commit. Working tree changes were not reset.

Stack observed earlier: PHP 8.2.12, Laravel 12.69.3, Livewire, Vite, Tailwind, Alpine. Dependencies were not downgraded.

## What this pass did

1. Confirmed uncommitted attendance, payroll, and weekly-off work already covers the gaps that the committed branch still documented.
2. Removed the property-sale agency listing (for-sale halls, sold status, agency marketplace). Booking inquiries on a hall page remain.
3. Restored case-insensitive city search and the `guests` query parameter after the sale-filter removal broke it.
4. Ran the full PHPUnit suite, then re-ran the one failing test after the fix.

## Test evidence

- Full suite before the final `#[Url]` restore: **222 passed, 1 failed, 1 skipped, 1,300 assertions** (472.63s).
- Isolated retest of `test_homepage_search_filters_capacity_and_books_the_requested_date`: **passed**.
- Attendance/payroll trio: **11 passed**.

Older reports that say 195 or 213 passed are historical. Do not treat them as the current total. The current full-suite number above is before the one-line guests fix; that case was rechecked alone.

## Not complete

Every page, button, persona, and viewport was not browser-tested in this pass. Week/day/agenda calendar, hold expiry, backup restore, and payment sandbox remain open.
