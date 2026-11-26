# Database and Financial Integrity Report — Riwaj Grand

**Branch:** `audit/riwaj-grand-repair-20261126` · **Base:** `df8de437` · **Date:** 2026-11-26

## 1. Environment and scope

All database verification used the isolated MariaDB 10.4 / MySQL-8-compatible database `wedding_hall_mysql_test` on `127.0.0.1:3307`. No production or shared data was read, migrated or modified. `RefreshDatabase` applies the full migration set from a clean schema for each suite run.

## 2. Migration audit

- New migrations this pass: `2026_11_26_000001` (expense void metadata), `000002` (utility→expense link), `2026_12_22_000001` (payout→booking link). All are additive, guarded with `Schema::hasColumn`/`hasIndex`, and have deterministic `down()` methods.
- Migration ordering matters: the payout link must sort **after** `2026_12_01_000002` (creates `vendor_payouts`); the earlier date was renamed accordingly.
- Critical finding repaired: a merge-broken Blade view (`inventory-tracker.blade.php`) caused inventory/procurement tests to 500; fixed.
- Remaining schema risks (documented, not silently changed):
  - Several financial tables (`service_payments`, `vendor_payouts`, `wallet_transactions`, `expenses`, `subscription_payments`) still use `cascadeOnDelete()` on tenant/booking FKs. Application paths suspend instead of delete, so the risk is latent; DB-level immutability is incomplete.
  - `bookings.booking_reference` and `purchase_orders.po_number` lack unique constraints; generation uses `uniqid()`/random suffixes. Additive dedup migrations exist for payment references; these two need a duplicate audit before indexing.
  - `Tenant::$fillable` contains phantom columns (`billing_enabled`, `timezone`, `free_access_days`, etc.) with no migrations; live code uses `config('platform.*')`.

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

- No authorized staff UI for hall-cancellation cash refunds (API exists).
- `expense_id` backfill for historical `utility_logs` rows not applied to existing installs (new writes are linked correctly).
- Source-linked expense correction UI (payroll/PO/utility) is guard-only, not a dedicated workflow.
- Unique indexes for booking references / PO numbers require a duplicate-data audit first.
- FK `restrictOnDelete()` hardening requires a staging migration rehearsal before production.
- Binary floating-point is still used in some legacy decimal calculations (`float` casts); critical monetary paths use integer paisa or `round(..., 2)` and are covered by tests, but a full decimal migration is a larger refactor.

No financial defect with an executed failing test remains unaddressed on the repair branch.
