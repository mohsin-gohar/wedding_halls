# Bugs and fixes

Evidence from local branch `enterprise-hardening` at `c5c0320`, plus uncommitted work. No production data was reset.

## Fixed this session

| Severity | Location | Root cause | Fix | Test |
| --- | --- | --- | --- | --- |
| High | `PublicVenues` city filter | Exact `city` match missed `karachi` / `karachi ` and homepage `Lahore` searches returned a misleading empty set or the wrong city list | `LOWER(TRIM(city))` match; city labels title-cased | Capacity filter regression re-run passed after the follow-up below |
| High | Property-sale module | Untracked agency desk, `/agencies`, `/agency/{slug}`, hall `SALE`/`SOLD`/asking price | Routes, nav, sale badges, and those classes removed. Hall inquiry kept as booking-only | `AgencyPropertyListingTest` deleted with the feature. Full suite then failed one unrelated assertion |
| Medium | `PublicVenues::$guests` | Removing the sale purpose filter also dropped `#[Url]` from `guests`, so `?guests=250` no longer hid small halls | Attribute restored | `PlatformHardeningTest::test_homepage_search_filters_capacity_and_books_the_requested_date` passed (8 assertions) |

## Already present in the working tree (verified by tests)

Attendance correction history, overnight checkout, manager attestation without invented 10:00–22:00 punches, weekly-off days on the tenant, and payroll finalization locking are implemented in uncommitted code. `AttendanceIntegrityFixesTest`, `PayrollAttendanceIntegrationTest`, and `StaffAttendanceUniqueConstraintRegressionTest`: **11 passed**.

## Not claimed fixed

- Week/day/agenda booking views, shift buffers, and automatic hold expiry are not implemented.
- Full suite after the `#[Url]` restore was not re-run. Last full run: **222 passed, 1 failed, 1 skipped, 1,300 assertions**. The single failure is the guests filter case above, which passed in isolation afterwards.
- Browser coverage of every persona and viewport was not completed in this session.
