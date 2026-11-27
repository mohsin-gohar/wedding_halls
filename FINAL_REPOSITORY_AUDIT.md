# Final Repository Audit (authoritative)

> This is the authoritative audit ledger for the current repository state.
> Historical audit/roadmap/summary markdown files are preserved as context but
> are superseded; see `DOCS_STATUS.md`.



## Current verification snapshot (2026-11-27)

The latest exact-head GitHub CI run is [46011895564](https://github.com/mohsin-gohar/wedding_halls/actions/runs/46011895564) on source HEAD
`846ac407f098d641add8cfeabdc9784fc8b87c8b`. MySQL: **611 passed, 3 skipped, 0 failed (3,184 assertions)**.
Playwright: **8/8 passed**. Static quality, PHP lint/Pint, Composer audit,
NPM audit, Vite build, Docker config/build/runtime smoke: **PASS**.
Deployment was intentionally skipped; production hosting and database were
not changed.

## 1. Scope and authority

| Document | Authority |
|---|---|
| `CASH_ONLY_PAYMENTS.md` | Money-handling policy: **CASH only** for every new financial write |
| `API_DOCUMENTATION.md` | Public `/api/v1` contract |
| `DOCS_STATUS.md` | Which documents are authoritative vs historical |
| `FINAL_REPOSITORY_AUDIT.md` | This ledger: architecture, workflows, evidence, residual risk |
| `IMPLEMENTATION_COMPLETION_REPORT.md` | What was actually implemented and verified |
| `PRODUCTION_READINESS_REPORT.md` | Go-live gates and infrastructure limits |

No production deployment and no production database change was performed or
authorized. Financial deletion protections now cover hall payments/bookings, subscription payments, expenses, payroll, purchase orders, service bookings/payments, vendor payouts, wallet transactions and audit logs. Duplicate PO/booking references stop the migration for manual review; the migration never renames them. All work below is in the repository and local test environments.

## 2. Architecture map

- **Runtime:** PHP 8.2+, Laravel 12, Livewire 4, MySQL 8 (only supported DB),
  Vite 6, Tailwind CSS 4, Alpine.js.
- **Multi-tenancy:** `tenant_id` scoping in every domain query, central
  `Hall::publicVisible()` / `Tenant::marketplaceVisible()` scopes, tenant lock
  enforcement in `App\Support\TenantAccess`, and role/permission checks via
  `App\Support\PermissionMatrix` and Spatie roles.
- **Money:** `App\Support\CashOnlyPayments` +
  `App\Concerns\GuardsCashOnlyMethod` enforce CASH at validation, service and
  model boundaries. Historical labels remain untouched for audit continuity.
- **API:** bearer tokens with explicit abilities
  (`bookings.read`, `payments.read`, `payments.record`); omitted abilities
  preserve legacy unrestricted first-party tokens. Stateless requests pass
  `AuthenticateApiToken` + subscription/account guards. API money mutation via
  `PATCH /api/v1/bookings/{id}` is prohibited; cash receipts go through
  `/api/v1/payments/record`.
- **Domain services:** `BookingService`, `HallAvailabilityService`,
  `ServiceBookingService`, `KitchenOrderService`, `SubscriptionService`,
  with row locks and transactional settlement where money or inventory moves.
- **UI:** tenant ERP, vendor marketplace/ERP, customer event workspace,
  support centre, public site, printable documents, responsive public pages.

## 3. Workflow map

| Workflow | Authoritative path | Key safeguards |
|---|---|---|
| Public venue discovery & booking | `Hall::publicVisible()`, `WalkInBookingModal`, `BookingService` | Marketplace opt-in, shared conflict engine, server-side pricing, guest submissions stay `PENDING`/unconfirmed |
| RFQ → quote → award → service booking | `VendorQuotesBoard`, `CustomerDashboard::acceptQuote`, `ServiceBookingService` | Cross-tenant decline, locked award, single audit entry, payout reservation |
| Cash receipt → verification → wallet/payout | `/api/v1/payments/record`, `VendorFinanceBoard`, `verifyServicePayment()` | Customer submits `PENDING`; only authorized staff verify; row locks + idempotency + atomic ledger |
| Venue ERP | Calendar, floor plans, inventory, kitchen/KOT, payroll, attendance | Legacy plan lock, hall-scoped booking checks, negative-stock rejection, one KOT per booking when clean |
| Platform / SaaS | Admin dashboard, subscription lifecycle, verification/moderation | Tenant lock in web + API, Super Admin-only activation/suspension, permission-matrix moderation |
| SEO / discovery | Homepage, sitemap, dynamic `robots.txt`, JSON-LD | Marketplace visibility filter, unique `EventVenue` JSON-LD, filter-free canonical URLs |

## 4. Phase 1–9 audit ledger

The phase names below follow preserved legacy execution notes available in Git history; the earlier five-phase stabilise/SEO/UI/advanced/QA sequence is also covered by the same current code and test evidence.

| Phase | Scope | Status | Evidence |
|---|---|---|---|
| 1 | Security & tenant isolation | Implemented + regression-tested | `SecurityHardeningRegressionTest`, bearer abilities, tenant locks, cash guard |
| 2 | Category & generic onboarding | Implemented | DB-driven vendor categories, seeded marketplace, tenant scopes |
| 3 | Marketplace | Implemented + regression-tested | `Tenant::marketplaceVisible()`, `PublicMarketplace`, `PublicVendors` |
| 4 | Universal service & booking engine | Implemented + regression-tested | RFQ lifecycle, `ServiceBookingService`, `RfqAwardConcurrencyTest` |
| 5 | Customer event workspace | Implemented + regression-tested | `CustomerEventWorkspace`, `CustomerSaasHardeningRegressionTest` |
| 6 | Vendor ERP completion | Implemented + regression-tested | `VendorFinanceBoard`, `VendorQuotesBoard`, `VendorWorkflowHardeningRegressionTest` |
| 7 | CRM / Finance / Inventory / Procurement / HR | Implemented + regression-tested | Inventory/KOT/payroll fixes, `ErpIntegrityHardeningRegressionTest` |
| 8 | Super Admin & SaaS | Implemented + regression-tested | Subscription lock, moderation via `PermissionMatrix`, admin/support routing |
| 9 | UI/UX & responsive | Implemented + browser evidence | `tests/browser/responsive-proof.spec.ts` across seven mandated viewports |

## 5. Security and integrity controls verified

- CASH-only enforcement across API, Livewire, services, models, UI, docs, tests.
- Customer financial mutation blocked; staff-only verification with permissions.
- Bearer ability enforcement and tenant suspension lock on API + web.
- Tenant isolation on packages, bookings, RFQs, payouts, inventory, floor plans.
- Idempotent settlement, payout reservation, row locking, single audit entry.
- Additive, conditional unique KOT index; historical duplicates never rewritten.
- Dynamic `robots.txt`; no closure route middleware (FQCN string middleware used).
- Secret scan, Pint, PHP lint, `git diff --check`, route/view cache checks.

## 6. Verification ledger

See `IMPLEMENTATION_COMPLETION_REPORT.md` and
`PRODUCTION_READINESS_REPORT.md` for the final commands and their recorded
results. No result is claimed unless its output file from this pass exists.
Latest exact-head CI gates: full MySQL suite **611 passed, 3 skipped / 3,184 assertions** and Playwright **8/8 passed** on `1b57773` (run 46011895564).

## 7. Residual risk / non-goals

- Online payment gateways are intentionally absent; reconciliation is manual.
- Lighthouse and synthetic load tooling are not installed in this environment;
  absence is reported honestly rather than simulated.
- Production deployment, production DB access, Redis/queue/scheduler
  verification on a live host and backup/restore drills are out of scope here.
