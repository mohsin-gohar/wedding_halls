> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# Riwaj Grand — Event Industry Ecosystem: Architecture & Gap Analysis

**Date:** 2026-11-19  
**Branch:** `enterprise-hardening` (HEAD `742313a`, working tree was clean at analysis start)  
**Scope:** Evidence-based codebase audit against the “Complete Event Industry Ecosystem & Enterprise Multi-Vendor SaaS” prompt. This document identifies **what exists, what is partial, and what must change/add** to satisfy the target vision without breaking current functionality.

> **Honesty note:** This is a static code/config/schema audit plus previously verified gates (MySQL suite 238 passed / 1 skipped, Pint/build/audit clean, public smoke 0 console errors on sampled pages). It is **not** a claim of full prompt completion, full browser E2E across all roles/routes, production load/scalability proof, or live payment-gateway certification.

---

## 1. Current Architecture Map (as-is)

### 1.1 Stack & runtime
- **Framework:** Laravel 12, PHP 8.2+, Livewire 4, Spatie Permission v6, Tailwind 4, Vite 7 (`composer.json`, `package.json`).
- **Database:** MySQL-only enforced in `config/database.php` (single `mysql` connection, no sqlite/pgsql fallbacks). `.env`/`.env.example`/`phpunit.xml` MySQL-aligned; CI targets MySQL 8.
- **Auth/RBAC:** `users.role` string + Spatie roles/permissions via `App\Support\PermissionMatrix` (110 permissions, 21 roles, aliases, 16 assignable vendor roles) and `database/seeders/PermissionMatrixSeeder.php`. Middleware aliases in `bootstrap/app.php` (`tenant.staff`, `portal.role`, `super.admin`, `auth.api`, `2fa`).
- **Tenancy model:** `App\Models\Tenant` as the business/vendor aggregate (UUID PK, `vendor_category_id`, verification/subscription/marketplace/onboarding/branding/finance fields). Staff users carry `tenant_id`; customers have no tenant scope. Isolation enforced in middleware/services/policies/queries in places (needs systematic verification per module).

### 1.2 Domain foundations already present
| Area | Evidence (models/migrations/services/routes) | Status |
|---|---|---|
| **Vendor categories (dynamic, DB-driven)** | `vendor_categories` (`2026_12_01_000001_create_vendor_ecosystem_tables.php`), `VendorCategory`, `VendorCategorySeeder` (9 categories), used in marketplace/vendor flows | **Present, partial** (no subcategories/parent hierarchy, limited category set) |
| **Vendor business profile & onboarding** | `Tenant` marketplace/onboarding/verification fields, `vendor_documents`, `vendor_availability`, `vendor_locations` (branch/location model exists), vendor registration (`Auth\Register` creates hall-centric tenant+venue+hall) | **Partial** (onboarding is hall-centric; multi-branch/location UI/flows incomplete) |
| **Service catalog** | `vendor_services`, `vendor_packages`, `VendorService`, `VendorPackage`, `VendorServicesManager` | **Present** |
| **Portfolio/media** | `vendor_portfolio_media`, `VendorPortfolioMedia`, portfolio in `PublicVendorProfile`/`VendorPortfolioManager` | **Present** |
| **Marketplace & storefronts** | `PublicMarketplace` (halls), `PublicVendors`, `PublicVendorProfile` (`/vendor/{slug}`), `PublicHallDetails`, `WhiteLabelSite` (`/site/{slug}`), quote request route | **Partial** (not all requested sub-routes like `/vendors/{slug}/services|packages|gallery|reviews`; discovery is split hall/vendor) |
| **Universal service booking engine** | `service_bookings`, `service_payments`, `ServiceBookingService` (award RFQ→booking, confirm/start/complete/cancel, payments/wallet hooks), `ServiceBookingController` API, `ServiceBookingPolicy` | **Partial** (core lifecycle exists; not all booking types/policies/rescheduling/refund-dispute flows fully modeled) |
| **Legacy hall booking engine** | `bookings` table/model, `BookingService` (locking/concurrency/idempotency patterns per prior audits), `BookingController` API, calendar/availability flows | **Present (must preserve)** |
| **RFQ / quotations** | `rfqs`, `rfq_bids`, `rfq_targets`, `VendorQuotesBoard`, `VendorQuoteRequest`, RFQ models/policies | **Present, partial** (revision/approval/contract linkage limited) |
| **Customer event workspace** | `customer_events`, `customer_event_vendors`, `customer_event_tasks`, `CustomerEvent`, `CustomerEventWorkspace`, `ServiceBooking.customer_event_id` linkage | **Present, partial** (multi-vendor coordination privacy/communication/contracts/budget depth incomplete) |
| **Vendor ERP boards (shared/category)** | `VendorBookingsBoard`, `VendorServicesManager`, `VendorPortfolioManager`, `VendorLocationsManager`, `VendorFinanceBoard`, category dashboards (photography/catering/decoration/beauty/planner), venue ops (calendar, KOT, inventory, staff, payroll, expenses, packages, floor plan, RSVPs) | **Partial** (strong venue/catering ops; many category-specific modules from prompt are shallow/missing) |
| **Finance/wallet/payouts** | `wallet_transactions`, `vendor_payouts`, `service_payments`, `VendorWallet`, `WalletTransaction`, `VendorPayout`, platform commission settings in `platform_settings` | **Partial** (ledger primitives exist; full invoicing/receivables/refunds/tax/reconciliation/profitability/audit immutability not complete) |
| **Reviews/support** | polymorphic extensions on `reviews` (migration adds `reviewable_*`), `support_tickets`, `ticket_messages` | **Partial** |
| **Admin/SaaS** | `AdminDashboard`, `PlatformTask`, subscription plans/payments/models, `SubscriptionService`, platform settings/config | **Partial** (entitlements/limits/plan enforcement & full admin controls incomplete) |
| **Public pages** | Home, venues, marketplace, vendors, gallery, packages/menus, services, about, contact, FAQs, availability, policies, RSVP, sitemap, concierge/chat routes | **Present** (content/UX/completeness varies) |

### 1.3 Data/schema highlights (from migrations)
- `tenants`: extended with verification, vendor_category, marketplace/branding/onboarding/rating/budget fields, etc.
- `vendor_categories`: `name, slug, tagline, icon, hero_color, booking_mode, features(json), description, is_active, sort` — **no `parent_id` / subcategory hierarchy**.
- `vendor_services`, `vendor_packages`, `vendor_portfolio_media`, `vendor_documents`, `vendor_availability` present.
- `vendor_locations` exists (branch types incl. HEAD_OFFICE/BRANCH/STUDIO/WAREHOUSE/MOBILE).
- `service_bookings` polymorphic + financial fields + status/payment/delivery/cancellation columns.
- `service_payments`, `wallet_transactions`, `vendor_payouts`, `platform_settings` present.
- `customer_events` (+ vendors/tasks), RFQ targets/extensions, support tickets/messages, review polymorphism present.
- Legacy hall domain tables (venues/halls/bookings/payments/kitchen/RSVP/floor plans/expenses/inventory/staff/payroll/attendance etc.) preserved.

---

## 2. Gap Analysis vs Prompt (section-by-section)

Legend: **[PRESENT]** largely exists, **[PARTIAL]** exists but incomplete/inconsistent, **[MISSING]** not implemented, **[RISK]** needs verification/hardening.

| Prompt area | Status | Key gaps / required changes & additions |
|---|---|---|
| **1. Complete vendor ecosystem & dynamic categories** | [PARTIAL] | Only 9 categories seeded (`VendorCategorySeeder`); prompt lists 14+ with richer sub-types. **No subcategories** (`parent_id`, hierarchy, scoped slugs/ordering). Super Admin cannot fully CRUD/reorder/disable categories & subcategories from UI without code in all cases. Booking modes/features per (sub)category not fully extensible. **Add:** `parent_id`/hierarchy (or `vendor_subcategories`), migrations+models, admin category management UI, seed expansion, category-feature/booking-mode mapping, validation and backward-compatible defaults. |
| **2. Multi-tenant architecture** | [PARTIAL] | `Tenant`/`vendor_locations` exist but onboarding/registration is hall-optimized (`Auth\Register` always creates venue+hall). Branch/location CRUD & per-branch operations limited. Tenant-specific branding/settings/docs/media/staff/customers/finance/reporting/notifications exist unevenly. **Isolation** implemented in many places but must be audited module-by-module (policies, scopes, API, file access, signed URLs). **Add:** generic tenant onboarding by business category, branch management flows, centralized tenant scoping/helpers, storage authorization hardening, cross-tenant tests (IDOR). |
| **3. Shared + category-specific ERP modules** | [PARTIAL] | Shared primitives exist; venue/catering/photography/decorator/beauty/planner dashboards & some ops exist. **Missing/shallow:** full venue contracts/floor-plan-to-booking ops, photographer shoot/delivery/album/gallery workflows, catering recipe costing/purchase orders/suppliers/waste/logistics depth, decor material/installation/dismantling/approvals depth, car rental fleet docs/expiry/driver/mileage/deposits/agreements/utilization, salon resources/trials/reminders/product usage, planner milestones/budgets/vendor contracts, equipment rental serial/dispatch/return/inspection/late fees/deposits/agreements, per-category enablement by subscription/config. **Add:** module-by-module domain models/migrations/services/UI behind feature flags/entitlements. |
| **4. Service catalog & marketplace** | [PARTIAL] | Marketplace/vendor discovery exists but split (venues vs vendors). Filters/comparison/favorites/shortlists/contact flows not fully comprehensive. SEO storefront sub-routes (`/vendors/{slug}/services|packages|gallery|reviews`) not all implemented. Public/private visibility and verification gates need consistency. **Add:** unified discovery UX, requested routes/views, compare/shortlist/favorites/inquiry UX, robust filtering/pagination/search. |
| **5. Universal booking engine** | [PARTIAL] | `ServiceBookingService` + RFQ award path and basic lifecycle exist; hall booking concurrency preserved. **Gaps:** full coverage of requested booking types (quantity-based, multi-day, appointment/resource nuances) in one consistent engine, quotation revision/approval loop, reschedule policy engine, refunds/disputes state machine, duplicate/idempotency across all channels, availability/resource conflict checks for all resource types, configurable business rules per tenant/category. **Add:** unified booking contracts/state transitions, pricing engine hardening, availability/reservation primitives, cancellation/refund/dispute workflows, idempotency keys. |
| **6. Event management & multi-vendor coordination** | [PARTIAL] | `CustomerEventWorkspace` + tasks/vendors/budget linkage exist. **Gaps:** explicit customer-authorized sharing, per-vendor visibility boundaries, vendor-to-vendor isolation guarantees, timeline/documents/contracts/communications/checklists/reminders depth, multi-event UX polish. **Add:** permissioned sharing model, event collaboration objects, notifications/reminders, audit of data exposure. |
| **7. CRM** | [PARTIAL] | Booking pipeline/inquiry concepts exist in places; no complete cross-category CRM suite (leads/prospects/follow-ups/segmentation/lifecycle/communication history) evident as a unified module. **Add:** CRM entities, pipelines, activities, reminders, tenant-scoped reporting. |
| **8. Finance & accounting** | [PARTIAL] | Expenses/payments/wallets/payouts/commission primitives exist. **Gaps:** full invoices/quotations/receipts/receivables/refunds/deposits/supplier payments/POs/profitability/tax config/discount authorization/immutable audit trails across all money flows; must not claim full accounting. **Add:** accounting-adjacent ledger discipline, document models, server-side totals, reconciliation views, explicit scope disclaimer. |
| **9. Inventory, procurement & assets** | [PARTIAL] | `inventory_items`/logs exist in venue context; generic multi-business inventory/procurement/assets/maintenance/barcodes/valuation not fully implemented or category-configurable. **Add:** reusable inventory/asset modules with enablement flags, suppliers/PO/GRN/adjustments/transfers where required. |
| **10. Staff & HR** | [PARTIAL] | Staff/attendance/payroll/shifts/leaves/policies exist (venue-strong). **Gaps:** departments, granular per-business permission assignment UI, scheduling/assignments/performance/docs parity across categories, strict financial/customer-data separation in all views. **Add:** HR/assignment modules, permission management UX, tenant-safe access checks. |
| **11. Super Admin platform** | [PARTIAL] | `AdminDashboard`/tasks/settings concepts exist; not all requested controls/monitoring/analytics/moderation/health/queue/failure views are complete. **Add:** category/city/plan/entitlement management, verification/moderation/disputes, platform analytics, ops/health visibility, audited actions. |
| **12. SaaS subscriptions & entitlements** | [PARTIAL] | Plans/subscription models/services exist (`platform.billing_enabled` currently false). Limits/entitlements **not** fully DB/config-driven and consistently enforced server-side across modules. **Add:** entitlement engine, plan-feature matrix, trial/past-due/suspended/cancelled/expired enforcement, middleware/service guards, admin tooling. |
| **13. Premium UI/UX** | [PARTIAL] | Luxury theme present in many public/vendor views; consistency, loading/empty/error states, accessibility, and ERP usability across all new modules not fully verified. **Add:** design-system pass, component consistency, responsive/a11y QA. |
| **14. Architecture & engineering** | [PARTIAL/RISK] | Services/policies/migrations/indexes/transactions exist in core flows; risk of duplicated logic and inconsistent tenant checks as scope expands. **Add:** module boundaries, reusable domain services, centralized authorization/scoping, caching/invalidation strategy, idempotency/events where justified. |
| **15. Security & tenant isolation** | [RISK/PARTIAL] | Many controls exist (policies, middleware, token auth, signed/idempotent payment webhooks patterns). **Confirmed gaps:** `config/cors.php` missing (added in this pass), local `.env` uses non-Redis stores and `SESSION_ENCRYPT=false`/`SESSION_SECURE_COOKIE=null` (dev), systematic cross-tenant/IDOR/file-access/MFA/rate-limit/upload/PII audit not fully evidenced. **Add:** CORS config, secure session defaults in prod, storage access controls, MFA where applicable, tenant isolation tests, security review per OWASP concerns. |
| **16. Performance & scalability** | [RISK] | Redis cache/session/queue/locks intended for prod but **Redis not running locally**; `.env` uses database drivers. No benchmarks (RPS/p50/p95/p99/errors/resources), no load/HA proof. **Add:** Redis-backed runtime in prod-like env, query/index review, pagination/caching/jobs tuning, measured benchmarks. |
| **17. Complete browser testing** | [PARTIAL] | Prior smoke: sampled public pages 0 console errors; earlier persona QA reported but not exhaustive per this prompt. Full route/button/form/role/responsive/console/network/DB-persistence matrix across all pages **not completed**. **Add:** route inventory, Playwright/E2E suite, role matrices, screenshots/evidence. |
| **18. Automated testing** | [PARTIAL] | 43 test files / 238 passed (1 skipped) on MySQL historically; coverage not aligned to all new ecosystem workflows (universal booking variants, entitlements, cross-vendor coordination, full tenant isolation matrix). **Add:** targeted feature/policy/booking/payment/subscription/inventory/CRM/E2E tests per module. |
| **19. Phased implementation** | [IN PROGRESS] | Foundation exists (Phases 1–4 partially). Safe, incremental expansion required. **Add:** execute remaining phases without regressions. |
| **20. Final acceptance** | [NOT MET] | Cannot claim complete; evidence gaps remain (see below). |

---

## 3. Concrete Change/Add Map (prioritized)

### P0 — Safety & correctness (do first, protect existing)
- **Tenant isolation audit:** enforce/verify policies + query scopes + API resource scoping + file/media authorization for `tenants`, `vendor_services/packages/media/documents/availability/locations`, `service_bookings/payments`, `rfqs`, `customer_events`, `reviews`, `support_tickets`, documents, admin actions.
- **Booking/payment integrity:** state-machine validation, idempotency, server-side pricing/totals, availability conflict checks for all bookable/resource types, refund/cancellation rules.
- **Authorization:** align route middleware, Livewire actions, and policies with `PermissionMatrix`; remove reliance on UI-only checks.

### P1 — Foundation gaps (ecosystem enablers)
- **Categories/subcategories:** migration to add `parent_id` (nullable, self-FK), `depth/sort` helpers, model relations/scopes; admin CRUD UI; expand seeds to cover prompt categories/subcategories; keep slugs stable/backward-compatible.
- **Generic onboarding:** refactor `Auth\Register` and admin provisioning to support non-hall business types/categories (do not force venue+hall creation for all); branch/location setup.
- **Marketplace/storefront completeness:** unify discovery, implement missing vendor sub-routes, comparison/shortlist/favorites/inquiry where feasible.
- **Customer event sharing boundaries:** explicit authorization for vendor visibility into event context.

### P2 — Module depth
- Category-specific ERP features (photography delivery/galleries, catering recipes/POs, decor workflows, transport fleet ops, salon appointments/resources, planner projects, equipment rental lifecycle).
- CRM, finance documents/reconciliation, inventory/procurement/assets, HR/permissions UI — delivered incrementally with tests.

### P3 — SaaS, ops, quality
- Entitlement/plan enforcement (server-side), admin ops/monitoring surfaces, CORS/prod security hardening, Redis runtime, performance benchmarks, full E2E/browser/route matrices, deployment/backup/recovery validation.

---

## 4. Production-readiness gaps observed (local audit)
| Item | Observed | Action |
|---|---|---|
| `config/cors.php` | **Missing** | **Added** in this pass (`config/cors.php`, env-driven origins). |
| Cache/queue/session drivers | `.env`: `database/database/database` | Safe for local dev; use Redis in staging/prod (`.env.production` already targets Redis). Do not switch local without Redis service. |
| Session security | `.env`: `SESSION_ENCRYPT=false`, `SESSION_SECURE_COOKIE=null` | Enable `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, proper `SESSION_DOMAIN`/`SAME_SITE` behind HTTPS in production. |
| Redis availability | Service not running locally | Required for prod-grade cache/session/queue/locks/scaling; provision and verify before claiming readiness. |
| CORS policy | No explicit config before this pass | Configure allowed origins/methods/headers per deployment; avoid wildcard credentials. |
| Storage/files | Private docs/media must be access-controlled | Audit public vs private disks, signed URLs, MIME/size/malware checks. |
| Monitoring/ops | Queue/scheduler/failed jobs/health/alerts not fully evidenced | Add/verify observability and runbooks. |

---

## 5. Phased Implementation Plan (proposed)

1. **Phase 1 — Stabilize & secure foundation (in progress):** confirm baseline gates on MySQL, add missing infra config (CORS), tenant-isolation/security audit checklist, fix critical auth/scoping bugs found.
2. **Phase 2 — Taxonomy & onboarding:** subcategories + admin category management + expanded seeds; generic multi-category vendor onboarding and branch/location flows.
3. **Phase 3 — Universal commerce core:** harden booking/quotation/payment/refund/dispute/availability engines (idempotent, server-priced, auditable) across categories.
4. **Phase 4 — Marketplace & event workspace:** unified discovery, complete storefront routes, favorites/compare/inquiry, permissioned multi-vendor event coordination.
5. **Phase 5 — Category ERP modules:** implement/finish per-category operational modules behind feature flags.
6. **Phase 6 — CRM/Finance/Inventory/HR:** shared business modules with tenant isolation and reporting.
7. **Phase 7 — Super Admin & SaaS entitlements:** plans, limits, verification/moderation, analytics, platform ops.
8. **Phase 8 — QA, performance, production readiness:** full route/E2E matrices, tenant-isolation and security test evidence, benchmarks, backup/restore, deployment docs. **No completion claimed without evidence.**

---

## 6. Verification status (evidence snapshot)

| Check | Result | Evidence/notes |
|---|---|---|
| Git state at audit | Clean, `742313a` on `enterprise-hardening` | `git status` / log reviewed earlier in session context |
| MySQL full test suite | **PASS** (238 passed, 1 skipped, 1355 assertions) | Fresh MariaDB 10.4.32 run recorded in project reports/context (806.13s latest noted) |
| Pint / audits / build | **PASS** | Pint v1.24.0 (308 files), composer/npm audit clean, `npm run build` ✓ (prior verified) |
| Schema parity | **PASS** | 80/80 tables, 0 differences (fresh MySQL vs dev SQLite, prior verified) |
| Public smoke (browser) | **PASS (sampled)** | `/`, `/venues`, `/marketplace`, `/vendors`, `/gallery`, `/about`, `/contact`, `/faqs`, `/check-availability`, login — 0 console errors (dev server :9200) |
| Full prompt acceptance criteria | **NOT MET** | Requires exhaustive implementation + testing evidence beyond current state |

---

## 7. Immediate next actions (recommended)
1. Review/approve this gap analysis and the phased plan.
2. Prioritize **Phase 1–2**: tenant-isolation audit fixes + subcategories/admin taxonomy + generic onboarding (highest leverage, enables all categories).
3. Run full MySQL suite/Pint/build after every implementation batch; add regression tests per change.
4. Stand up Redis-backed local/staging runtime and define production CORS/session/security settings for real deployments.
5. Produce incremental, evidence-backed progress reports (no fabricated results).

---

*Prepared from repository inspection on 2026-11-19. Existing venue/hall functionality and legacy booking flows must remain intact throughout implementation.*
