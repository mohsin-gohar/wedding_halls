# Business Module Completion Report — Riwaj Grand

**Branch:** `audit/riwaj-grand-repair-20261126` · **Base:** `df8de437` · **Date:** 2026-11-26

## 1. Method

Every present module was inspected in code and exercised by existing feature/API/browser tests. A page, route or button existing was **not** treated as proof of a working workflow. Financial policy authority: `CASH_ONLY_PAYMENTS.md`.

## 2. Module status

| Module | Status | Evidence / notes |
|---|---|---|
| SaaS administration | Implemented + tested | Super-admin console, plans, tenancy, audit log, platform settings; `ComprehensiveDashboardCrudAuditTest`, `PlatformHardeningTest` |
| Subscription lifecycle | Implemented + tested | Server-side pricing, row-locked approval/renewal, 3-day grace, scheduler; `SubscriptionLifecycleAndAutomationTest`, `CoordinatorHardeningRegressionTest` |
| Complimentary onboarding | Partial consistency risk | Console says free launch; self-registration grants 30 days; `provisionHallAccount()` currently grants 12 months — needs a single documented policy |
| Hall & marketplace | Implemented + tested | Multi-hall tenancy, media, `publicVisible()`/`marketplaceVisible()`, SEO/sitemap/robots, JSON-LD now XSS-safe |
| Booking management | Implemented + tested | Slot/buffer/full-day conflict engine, holds, status transitions, server-side pricing, idempotent cash receipts |
| Booking cancellation refunds | Cash refund API exists, no dedicated hall-refund UI | Outstanding refundable cash can sit in the ledger; flagged for product decision |
| Vendor & service marketplace | Implemented + tested | Category-driven onboarding, RFQ→bid→award→service booking, confidentiality locks, pricing validation, commission/wallet/payout |
| Vendor commission/payout | Corrected this pass | Refund reverses only net wallet credit, cancels reserved payout, records audit entry; `ServiceSettlementIntegrityTest` updated to assert correct cash-refund behavior |
| Customer workspace | Implemented + tested | Multi-function events, checklist, shortlist, budget, booking links, support; ownership re-checked on every action |
| Hall ERP (staff/attendance/payroll) | Implemented + tested | Role-scoped HR tools, paisa arithmetic, draft→approved→paid locking, atomic disbursement; `StaffPayrollMoneyTest`, `PayrollPaymentIntegrityTest` |
| Inventory / procurement / KOT | Merge damage repaired | Extra `@endif`/`@else` in `inventory-tracker.blade.php` caused 500s; fixed, tests re-run |
| Expenses | Corrected this pass | Void metadata migration, source-link protection, valid default category, idempotent void with global-scope bypass, atomic dashboard utility/expense writes |
| Finance/reporting | Implemented + tested | Cash-only KPIs, refunds netted, tenant isolation; some status-set inconsistency between dashboard/report KPIs remains |
| REST API | Implemented + tested | Bearer abilities, customer own-record scopes fixed this pass, server-derived money, negative tests |
| WhatsApp RSVP | Implemented + tested | Explicit YES/NO intent, clarification on ambiguous messages, HMAC verified webhook, replay protection, timeout on outbound calls |

## 3. Financial integrity (cash-only preserved)

- All new writes remain `CASH`; no gateway code/routes reintroduced. Historical labels preserved unchanged.
- Customers: read own receipts, submit `PENDING` service payments only.
- Staff: `payments.record`/`payments.refund`/`payments.verify` checked at route, component, controller and service layers; tenant owner refund permission aligned this pass.
- Row locks + idempotency: booking advance, service payment verification, payout processing, subscription approval, payroll disbursement, inventory settlement.
- Refund fix: cash refund no longer fails because the vendor wallet is empty; only the actually credited net amount is reversed, and the corresponding payout reservation is cancelled.
- Audit: append-only `AuditLog`; cancellation, verification, payout processing and source-linked expense voids audited.

## 4. Genuinely remaining product gaps (not "complete")

1. FK `restrictOnDelete()` hardening and unique indexes for booking/PO references (staging rehearsal required).
2. Platform-support API scope and upload-limit alignment (product/owner decisions).
3. Dashboard still labels HOLD-inclusive booked value as "gross" in one KPI; analytics GMV is corrected, dashboard display alignment is pending UI copy work.
4. Host-level production gates (deploy, restore drill, mail/WhatsApp, secrets) remain owner-dependent.

Completed with regression tests in this pass: hall cancellation cash-refund UI, append-only source-linked expense correction workflow, historical utility `expense_id` backfill, onboarding policy, GMV definition.
