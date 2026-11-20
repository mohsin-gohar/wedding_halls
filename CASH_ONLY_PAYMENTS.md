# Riwaj Grand — Cash-Only / Manual Payment System (Authoritative)

This document is the single source of truth for money movement in the
application. Historical audit/roadmap markdown files may describe an older
online-gateway design; they are snapshots and are superseded by this document.

## Product decision

Online payment (Stripe, JazzCash, EasyPaisa, credit/debit card, POS, wallet
checkout, gateway initiation, webhooks, callbacks and automatic verification)
is **removed**. Real money is never accepted, authorized or verified by code
in this repository. All value is handled as physical cash (or an offline bank
transfer confirmed by a human), recorded by authorized staff.

## What is supported

| Flow | How it works |
|---|---|
| Venue token advance / cash advance | Staff count the cash at the front desk and record it. The booking ledger is updated in a locked transaction. |
| Installments | Each physical installment is a separate cash record with its own receipt. |
| Final settlement | Recorded as cash only after the balance is physically settled. |
| Cash refunds | Recorded by authorized staff only; reverse the ledger and reduce received totals. |
| Receipts & balances | Server-generated receipt numbers; balances derive from the cash ledger. Refunds are netted out. |
| Income / expense reports | Aggregate verified cash records. Refunds are subtracted, never counted as inflow. |
| Audit history | Every record/refund writes an append-only audit entry. Historical rows are never deleted. |
| Service (vendor) payments | Customers may **submit** an unverified manual record; a staff member verifies it before anything changes. |
| SaaS subscriptions | Manual/offline records. Only a Super Admin activates, renews or suspends a tenant. |

## Trust boundary (non-negotiable)

- **Customers cannot mutate financial state.** They can read their own receipts
  and submit a `PENDING` manual service payment only. There is no request,
  Livewire action, API call or direct-ID manipulation that lets a customer
  change a balance, mark a payment received, issue a refund or control a total.
- **Only authorized staff** (active account, tenant ownership, `payments.record`
  / `payments.refund` permission) may confirm physical cash. The service also
  re-checks the user is not a customer and re-locks the booking inside a DB
  transaction before touching any balance.
- **Server-side pricing:** rates, add-on prices, discounts, taxes and advance
  caps are recomputed from the catalog on the server. Client-supplied money
  fields are never trusted.
- **Tenant isolation:** every read and write is scoped to the principal's tenant;
  cross-tenant package/booking/reference IDs are rejected.

## What was removed

- `POST /api/v1/payments/initiate` and all `/api/v1/webhooks/{gateway}` routes.
- `PaymentService` gateway branches (`processStripePayment`,
  `processJazzCashPayment`, `processEasyPaisaPayment`, `verifyWebhook`,
  `verifyGatewayHash`, `generateGatewayReference`) and all `Stripe\…` imports.
- Gateway credential blocks in `config/services.php` and `.env.example`.
- `PaymentGateway` model + its migration and the `payment_gateway` tenant field.
- Gateway webhook feature tests; replaced by `CashOnlyPaymentTest`.
- Online-branded options in walk-in, expense, vendor-payout and service UIs.
- Tracked artifacts containing PII/CSRF data (SQLite DB, diagnostic script,
  rendered dashboard HTML, screenshots/logs).

## What was deliberately kept (for history)

- Historical `payments` / `service_payments` rows and legacy method labels
  (`STRIPE`, `JAZZCASH`, `EASYPAISA`, `CREDIT_CARD`, `ONLINE`, `WALLET`, …).
  No data migration rewrites or deletes them.
- `webhooks/*` CSRF exemption, WhatsApp webhook, SMS "gateway" and internal
  wallet/ledger code — these are unrelated to online customer payments.
- Subscription payment records (manual, Super-Admin-verified).

## Database safeguards

- Payment references are unique **per tenant** — the same voucher number may be
  reused by another venue, but never twice in one tenant.
- Payments and audit logs are protected from cascade deletion; bookings with
  payment history are archived (cancelled) instead of hard-deleted; tenants
  with financial history are suspended instead of deleted.
- Audit log rows are append-only at the model layer.
- Subscription approval is transactional and row-locked (no double renewal).
- Wallet credits use atomic increments; full service settlement is driven by
  actual summed amounts, not a caller-supplied `FULL` label.

## Verification

Run on MySQL/MariaDB (no SQLite fallback):

```bash
php artisan test --testsuite=Feature --filter=CashOnlyPaymentTest
php artisan test --testsuite=Feature --filter='ServiceSettlementIntegrityTest|ApiBookingFinancialIntegrityTest|PlatformHardeningTest'
php artisan migrate:fresh --seed   # verifies every migration on a clean DB
```

CI (`.github/workflows/ci.yml`) runs the full suite on MySQL; deployment remains
disabled (`if: false`). No deployment is performed by this change.
