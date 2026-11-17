# BOOKING CONFLICT & AVAILABILITY AUDIT — Riwaj Grand

**Date:** 2026-11-16

## Creation path (server-side)
`app/Services/BookingService.php::createReservation()`:
1. `Cache::lock("lock:hall:{id}:date:{date}", 600)` with `block(5)` → `SlotAlreadyBookedException(423)` on timeout (`:46-53`).
2. `DB::transaction()` (`:63`) + `slotConflictQuery()->lockForUpdate()->first()` (`:65-69`) → exception 409.
3. Overlap sets (`slotConflictQuery`, `:179-186`): FULL_DAY → all four slots; single slot → itself + FULL_DAY; statuses `HOLD,CONFIRMED`.
4. `UniqueConstraintViolationException` catch (`:156-164`) is a fallback, but the unique index was dropped in `2026_10_18_000001:20-22` — so layers 1–2 are authoritative.

`WalkInBookingModal::checkAvailability()` (`:223-227`) uses the same `HOLD,CONFIRMED` set; tenant isolation re-checked in `confirmBooking()` (`:421-423`).

## Verified behavior
- `BookingConcurrencyTest`: double-booking throws; FULL_DAY ↔ EVENING both directions blocked. **3 concurrency tests + 2 integration tests pass.**
- `INQUIRY` deliberately does **not** block (it's a lead, not a reservation); calendar now labels it as non-blocking.

## Defect fixed this cycle
Legacy/parallel writes can create two `CONFIRMED`/`HOLD` rows (no DB unique constraint). Previously the calendar **silently hid** the second (`??=` at `CalendarShiftGrid.php:167`). Now:
- All rows grouped per date+slot with deterministic priority.
- ≥2 blocking rows → red "⚠ N conflicting bookings — review" button.
- `viewSlotConflicts()` opens read-only alertdialog listing all conflicting bookings. **No records deleted/changed automatically.**

## Remaining
- A DB-level partial unique constraint per (tenant,hall,date,slot) for blocking statuses would add defense-in-depth, but must be introduced via a safe migration only after auditing existing duplicates (and MySQL/SQLite portability). Not applied this cycle to avoid data loss.
- Cancelled booking rebooking and failed-payment release tested at service layer; expired holds do not exist.

## Commands
```
php artisan test tests/Feature/BookingConcurrencyTest.php tests/Feature/CalendarIntegrityTest.php --compact
→ 9 passed
```
