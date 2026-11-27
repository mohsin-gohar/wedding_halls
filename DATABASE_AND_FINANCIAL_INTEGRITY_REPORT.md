# Database and Financial Integrity Report — Riwaj Grand

**Branch:** `audit/riwaj-grand-repair-20261126` · **Base:** `df8de437` · **Date:** 2026-11-26

## 1. Environment and scope

All database verification used the isolated MariaDB 10.4 / MySQL-8-compatible database `wedding_hall_mysql_test` on `127.0.0.1:3307`. No production or shared data was read, migrated or modified. `RefreshDatabase` applies the full migration set from a clean schema for each suite run.

## 2. Migration audit

- New migrations this pass: `2026_11_26_000001` (expense void metadata), `000002` (utility→expense link), `2026_12_22_000001` (payout→booking link). All are additive, guarded with `Schema::hasColumn`/`hasIndex`, and have deterministic `down()` methods.
- Migration ordering matters: the payout link must sort **after** `2026_12_01_000002` (creates `vendor_payouts`); the earlier date was renamed accordingly.
- Critical finding repaired: a merge-broken Blade view (`inventory-tracker.blade.php`) caused inventory/procurement tests to 500; fixed.
- Financial FK deletion hardening and unique reference indexes are implemented by `2026_12_23_000001_harden_financial_foreign_keys_and_unique_indexes.php`. The migration uses a fail-closed duplicate preflight: it does not rename or rewrite existing PO/booking references; it stops with a sample if duplicates are present. Staging duplicate/orphan audit and migration rehearsal remain mandatory before production.
- `Tenant::$fillable` contains legacy/phantom columns with no matching migrations in some historical paths; active free-access configuration uses `config('platform.*')`.

## 3. Financial definitions (authoritative)

| Term | Definition | Implemented as |
|---|---|---|
| INQUIRY / HOLD | Pipeline value only — no cash received | Excluded from GMV in `PlatformAnalyticsService::chartData()` and `waveData()` (this pass); retained in calendar/shift counts |
| CONFIRMED / COMPLETED | Booked (GMV) value; cash only if a verified cash receipt exists | `['CONFIRMED','COMPLETED']` used for GMV sums |
| CANCELLED / REFUNDED | Excluded from GMV; refunds netted from inflow | Service refund rows + hall refund API |
| Collected cash | Verified cash payments minus refunds | `PaymentService` ledger; service wallet ledger |
| Outstanding | Booked value minus collected cash | Vendor finance/board views |
| Commission | Snapshot at booking creation; never recomputed after settlement | `commission_amount` on service bookings |

Regression: `tests/Feature/FinancialReportingIntegrityTest.php` asserts a HOLD booking's `grand_total` is not counted in weekly GMV.

## 4. Integrity controls verified by tests

- `ServiceSettlementIntegrityTest`: gross credit + real commission debit + net payout; insufficient-balance payout rejected; cash refund recorded even when wallet balance is zero; commission-debit failure rolls back the entire settlement.
- `ServicePaymentOverpaymentHardeningTest`, `PaymentIdempotencyConflictTest`, `CashOnlyPaymentTest`: cash-only validation, per-tenant reference uniqueness, replay with mismatched amount/type rejected, customer self-credit blocked.
- `PayrollPaymentIntegrityTest`, `StaffPayrollMoneyTest`: integer-paisa arithmetic, draft→approved→paid locking, atomic disbursement with unique payroll-linked expense.
- `InventorySettlementPermissionTest`, `ErpIntegrityHardeningRegressionTest`: PO settlement requires inventory view + finance manage; negative stock blocked; invalid movement types rejected; movement idempotency marker under row lock.
- `ExpenseVoidIntegrityTest`, `ExpenseAuthorizationTest`: void metadata, source-linked expense protection, append-only audit.
- Concurrency: booking creation locks tenant→hall→booking rows; service cancellation now re-locks the booking and rejects an existing REFUND row; wallet debits use conditional atomic `decrement()`; subscription approval and payout processing use row locks.

## 5. Defects fixed in this pass

1. Refund reversal debited gross from a wallet that only held net → refund failed/rolled back; now reverses at most the actually credited net, never blocks the customer cash refund, and cancels the reserved payout for the cancelled booking (new `vendor_payouts.service_booking_id` FK).
2. Concurrent cancellation could create two refunds → row lock + idempotency guard.
3. Wallet `balance_after` stale under concurrency → re-read after atomic increment.
4. HOLD value counted as GMV → excluded from analytics GMV/inflow series.
5. Utility tracker created `utility_logs` without a linked expense → atomic expense creation/update with `expense_id`, idempotent per booking, audit entry.
6. Admin-provisioned tenants received 12 months free while self-registration received 30 days → both now use `config('platform.free_access_days')`.

## 6. Remaining gaps (NOT verified / product decisions)

- Unique indexes and FK `restrictOnDelete()` are implemented and pass clean-schema MySQL CI. A real-data duplicate/orphan audit plus staging rehearsal remains mandatory before production; duplicate references cause migration failure without rewriting them.
- Binary floating-point is still used in some legacy decimal calculations (`float` casts); critical monetary paths use integer paisa or `round(..., 2)` and are covered by tests, but a full decimal migration is a larger refactor.
- Completed in this pass: utility historical `expense_id` backfill migration (additive, null-safe), append-only corrections via `ExpenseCorrectionService` (no original rewrite, signed delta rows, audit), refund voucher branch, HOLD excluded from analytics GMV.

No financial defect with an executed failing test remains unaddressed on the repair branch.


## 7. Latest verified schema evidence (2026-11-27)

On HEAD `846ac407f098d641add8cfeabdc9784fc8b87c8b`, GitHub CI run [46011895564](https://github.com/mohsin-gohar/wedding_halls/actions/runs/46011895564) applied the complete migration set to clean MySQL successfully and passed **611 tests / 3,184 assertions**. Regression coverage asserts RESTRICT delete rules for hall payments, subscription payments, expenses, payroll, purchase orders, service bookings/payments, vendor payouts, wallet transactions and audit logs.

The migration's unique-index preflight is intentionally non-destructive: duplicate tenant-scoped PO numbers or booking references stop migration and emit a small sample for manual review. It never automatically renames those identifiers. CI's clean database does not replace the required production-like staging duplicate audit or restore drill.
