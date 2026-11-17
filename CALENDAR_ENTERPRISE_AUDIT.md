# CALENDAR ENTERPRISE AUDIT — Riwaj Grand

> Historical note, 17 Nov 2026. This file describes the calendar before week/day/agenda, shift windows, buffer, and `bookings:expire-holds`. Those are now in `CalendarShiftGrid`, `ShiftWindows`, and `ExpireBookingHoldsCommand`. Do not treat the gaps below as the current source. Current evidence is in `CURSOR_BOOKING_ATTENDANCE_PAYROLL_AUDIT.md`.

**Date:** 2026-11-16 · Branch `enterprise-hardening`

## 1. Files inspected
- `app/Livewire/CalendarShiftGrid.php` (all 189 lines pre-change)
- `resources/views/livewire/calendar-shift-grid.blade.php` (252 lines pre-change)
- `app/Services/BookingService.php` — `createReservation()`, `slotConflictQuery()`
- `app/Livewire/WalkInBookingModal.php` — `confirmBooking()`, `checkAvailability()`
- `app/Http/Controllers/Api/BookingController.php:160-164`
- `database/migrations/2026_10_09_000003_create_bookings_table.php`, `2026_10_18_000001_relax_unique_constraint_on_bookings_table.php`, `2026_11_20_000005_create_shift_scheduling.php`, `2026_11_20_mixed_shift_definitions.php`
- `app/Models/ShiftDefinition.php`, `app/Models/VenueShift.php`, `tests/Feature/BookingConcurrencyTest.php`

## 2. Defects found → root cause → fix

| # | Defect (evidence) | Root cause | Fix |
|---|---|---|---|
| 1 | Redundant `whereRaw('date(event_date) between ? and ?')` at `CalendarShiftGrid.php:158` | Copy of `whereBetween` on a DATE column; `date()` is driver-coupled | Removed; single portable `whereBetween` retained |
| 2 | Overlapping bookings silently hidden: `??=` at `:167` | First row wins, no grouping, no ordering | Group all bookings per date+slot with deterministic status priority; store blocking (>=2 CONFIRMED/HOLD) groups in `$slotConflicts` |
| 3 | Every booked card styled "Confirmed"; modal hardcoded "Confirmed Event Reservation" (blade `:180`) | No status-aware rendering | CONFIRMED/HOLD/INQUIRY get distinct badge colors + labels (`CNF`/`HOLD`/`INQ`); modal label dynamic |
| 4 | Legend advertised "On Hold (24H)" but no expiry field/scheduler exists | UI claim not backed by code | Replaced with "On Hold (awaiting confirmation)"; added Inquiry + Conflict-warning legend entries |
| 5 | `INQUIRY` shown as occupied but ignored by conflict detection (`BookingService.php:186`, `WalkInBookingModal.php:226`) | Display/conflict sets differed | Kept visible but explicitly labelled non-blocking (`INQ`) |
| 6 | API accepted `REFUNDED` status not in DB enum (`Api/BookingController.php:161`) | Validation drift | Validation now mirrors enum exactly |
| 7 | `shift_definitions`/`venue_shifts` tables duplicated across 2 migrations, unreferenced by booking path, slugs (`lunch/dinner/late_night/full_day`) mismatch enum (`AFTERNOON/EVENING/NIGHT/FULL_DAY`) | Abandoned schema | Documented as dead schema; not removed (no data loss). Configurable shifts remain **OPEN** |
| 8 | No DB unique constraint (dropped at `2026_10_18_000001:20-22`) | Concurrency delegated to lock | Verified cache lock (600s TTL, 5s block) + `lockForUpdate()` + status checks; `BookingConcurrencyTest` passes — no silent double-booking on the creation path |

## 3. Conflict reconciliation workflow (new)
- Warning button appears on any slot with ≥2 blocking bookings: `⚠ N conflicting bookings — review`.
- `viewSlotConflicts(date, slot)` opens a read-only `role="alertdialog"` listing each customer/event/status.
- **No automatic deletion/modification** of conflicting records (per master prompt).
- `php artisan test tests/Feature/CalendarIntegrityTest.php` → 4 passed.

## 4. Browser verification (OpenCode, authenticated owner)
`/calendar`: rendered real seeded booking (13 Nov, "CNF", PKR 1,470,000), 3 shift rows/day, month nav Nov→Dec→Today, booked slot opened details modal with host/contact/financials. **Console errors: 0.** Network failures: 0 observed.

## 5. Remaining gaps (honest)
- Week / day / agenda views, date picker, customer/reference search, print/export — not implemented.
- Configurable venue-specific shifts with buffer/capacity/pricing — DB tables exist but disconnected.
- Hold expiry field + scheduled release (legend no longer claims 24H).
- PostgreSQL compatibility of the legacy schema overall (the redundant `date()` call was the one found in this component).

## 6. Commands
```
php artisan test tests/Feature/CalendarIntegrityTest.php --compact
→ Tests: 4 passed
```
