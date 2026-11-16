# DATABASE & MIGRATION AUDIT — Riwaj Grand

**Date:** 2026-11-16

## 1. Migration date discrepancy — resolved with git evidence
- `php artisan migrate:status`: all migrations `Ran`, batches 1–3 (incl. `2026_11_20_*` … `2026_12_07_*`).
- `git log --diff-filter=A --format="%h %ad %s" --date=short`:
  - `2026_11_20_000001_create_subscription_plans.php` ← commit `76a1997`, **2026-11-11**
  - `2026_12_07_000001_add_two_factor_columns_to_users_table.php` ← commit `ca39a9d`, **2026-11-16**
- New this cycle: `2026_11_16_000001_create_attendance_holidays_table.php` — correctly dated for today; UUID PK, unique `(tenant_id,date)`, index `(tenant_id,date)`; applied to local dev DB via `php artisan migrate --force` (non-destructive).
- Verdict: files are real and applied; **filenames are future-dated**. No fabricated migration. Hygiene issue: a migration created today (2026-11-16) with a correct timestamp sorts *before* them. New migrations should avoid this pattern.

## 2. Schema facts verified
- `bookings`: UUID PK, `event_date` DATE, `slot_type` enum (AFTERNOON/EVENING/NIGHT/FULL_DAY), `status` enum (INQUIRY/HOLD/CONFIRMED/COMPLETED/CANCELLED), indexes `idx_hall_event_slot`, `tenant_id+event_date`.
- Unique index on `(hall,event_date,slot)` was deliberately dropped (`2026_10_18_000001:20-22`); concurrency guaranteed by cache lock + `lockForUpdate()`.
- `staff_attendances`: UUID PK, unique `(tenant_id,user_id,date)` (`2026_10_11_000001:54`) — verified by regression test; `status string(30) default('PRESENT')` — **application no longer relies on this default** (unmarked rows skipped); consider a follow-up migration to change the DB default safely.
- `staff_leaves`: no uniqueness on date ranges — overlap now enforced in `StaffAttendance::submitLeave()`.
- `shift_definitions`/`venue_shifts`: created twice (identical migrations with/without datetime suffix, guarded by `Schema::hasTable`) — second is a no-op; unused by booking path.
- Money columns decimal(12,2); no floats persisted.

## 3. Changes this cycle
No schema changes were required. All fixes were application-layer (validation, transactions, conflict surfacing). No destructive DB command was run; no `migrate:fresh` against real data.

## 4. Commands
```
php artisan migrate:status | Select-Object -Last 25
git log --diff-filter=A --format="%h %ad %s" --date=short -- <migration>
```
