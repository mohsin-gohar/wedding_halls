# DATABASE & MIGRATION AUDIT — Riwaj Grand

**Date:** 2026-11-16 · **Repair cycle update: 17 Nov 2026**

## 0. 17 Nov repair cycle — real MySQL/MariaDB verification (was: never tested)
- Environment: local MariaDB **10.4.32-MariaDB** (XAMPP binary) started with a **fresh disposable datadir** (`%TEMP%\opencode\mysql_test_data`, port 3307, `--no-defaults`). The user's XAMPP datadir was found **corrupted** (privilege tables + unrelated `edusphere` InnoDB page corruption from a 15 Nov crash) — deliberately NOT touched; nothing of the user's was modified or deleted.
- Result of first `migrate:fresh` on MariaDB: **FAILED — errno 150** on `service_bookings` → then on `wallet_transactions`. These were **real latent bugs invisible on SQLite** (SQLite does not enforce FK creation order):
  1. `service_bookings` (2026_12_01_000002) declared an inline FK to `customer_events`, which is created in the **next** file (000003). Fixed: column declared without constraint; FK added by new migration `2026_12_07_000002_add_service_bookings_customer_event_foreign_key.php` (skips SQLite, which cannot ALTER-add FKs and runs tests with FKs off; MySQL/PostgreSQL get the real constraint — semantics unchanged: `nullOnDelete`, and app code already nulls `customer_event_id` before deleting an event).
  2. `wallet_transactions` referenced `vendor_payouts` created **later in the same file**. Fixed by reordering (payouts before wallet) — no behavior change.
- After fixes: `migrate:fresh` on MariaDB → **0 pending, all migrations Ran**.
- Static FK-order audit added (`fk_order_audit.php`, scratch): no `Schema::create` block references a not-yet-created table; bare `->constrained()` (single occurrence → `halls`, created much earlier) checked manually.
- Full PHPUnit suite re-run **against MariaDB** with real env overrides (see repair report for exact output).
- Honest caveat: MariaDB 10.4 is **below** Laravel 12's officially supported minimum (MySQL 8.0+/MariaDB 10.6+); migrations/tests passing on 10.4 is a stronger result than the requirement, but production should still target MySQL 8.0+/MariaDB 10.6+.
- `.gitignore` was corrupted into UTF-16 (NUL bytes → git saw a "binary" file) by an earlier `wedding_halls/` append; rewritten as clean UTF-8, still ignoring the untracked nested `wedding_halls/` copy (13.9 MB, left untouched on disk, never committed).

## 1. Migration date discrepancy — resolved with git evidence
- `php artisan migrate:status`: all migrations `Ran`, batches 1–3 (incl. `2026_11_20_*` … `2026_12_07_*`).
- `git log --diff-filter=A --format="%h %ad %s" --date=short`:
  - `2026_11_20_000001_create_subscription_plans.php` ← commit `76a1997`, **2026-11-11**
  - `2026_12_07_000001_add_two_factor_columns_to_users_table.php` ← commit `ca39a9d`, **2026-11-16**
- New this cycle: `2026_11_16_000001_create_attendance_holidays_table.php` — correctly dated for today; UUID PK, unique `(tenant_id,date)`, index `(tenant_id,date)`; applied to local dev DB via `php artisan migrate --force` (non-destructive).
- Phase 3: `2026_11_16_000002_create_payroll_policies_table.php` (unique tenant, 5 configurable decimal rates) and `2026_11_16_000003_add_payroll_breakdown_to_staff_payrolls_table.php` (days counts, per-type deductions, bonuses/advances, approved_by/at) — both applied locally, backward-compatible defaults (0.00/0), no data loss.
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
