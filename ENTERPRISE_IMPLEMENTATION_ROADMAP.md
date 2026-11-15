# RIWAJ GRAND — ENTERPRISE IMPLEMENTATION ROADMAP

> Aligned to the master brief's Phases 0–6. Status reflects **verified** state, not intent.
> Legend: ✅ complete & tested · 🟡 partially complete · ⛔ deliberately deferred

---

## Phase 0 — Audit & Planning ✅

| Item | Status |
|---|---|
| Codebase audit (routes, models, schema, middleware, services, policies, seeds, tests) | ✅ `RIWAJ_GRAND_ENTERPRISE_AUDIT.md` |
| Feature inventory (complete / partial / UI-only / backend-only / missing / broken) | ✅ same document |
| Architecture, database, RBAC, security review | ✅ `DATABASE_ARCHITECTURE.md`, `RBAC_PERMISSION_MATRIX.md`, `SECURITY_AUDIT_REPORT.md` |
| Phase plan | ✅ this document |

## Phase 1 — Multi-Vendor Foundation ✅

| Item | Status |
|---|---|
| Generic vendor architecture (`vendor_categories`, `vendor_services`, `vendor_packages`, `vendor_portfolio_media`, `vendor_documents`, `vendor_availability`) | ✅ |
| Dynamic categories (DB rows, Super Admin can add a vertical without code) | ✅ `vendor_categories` + `VendorCategorySeeder` |
| Vendor onboarding (self-registration → `PENDING` verification → admin approval) | ✅ |
| Vendor verification (business + document level) | ✅ `tenants.verification_status`, `vendor_documents.status` |
| RBAC matrix + seeder + middleware + policies | ✅ 110 permissions / 21 roles |
| Tenant/vendor isolation | ✅ policy + query-level, regression-tested |
| **Multi-location businesses** | ✅ `vendor_locations` + `VendorLocationsManager` |
| Vendor-specific dashboard foundation | ✅ 5 category dashboards (photography, catering, decoration, beauty, planning) + generic |

## Phase 2 — New Business Categories ✅

| Category | Status |
|---|---|
| Photography | ✅ `PhotographerDashboard`, photographer packages, per-hour/project services |
| Catering | ✅ `CatererDashboard`, per-head packages, KOT reuse, menu/catering ERP |
| Decoration | ✅ `DecoratorDashboard`, project pricing, portfolio, mood boards |
| Beauty & bridal | ✅ `BeautyBridalDashboard`, appointment pricing, portfolio |
| Wedding planning | ✅ `WeddingPlannerDashboard` |
| Transport | ✅ transport vendor in the seeded marketplace, `car_fleets` + `CarFleet` morph bookings |
| Entertainment / other verticals | ✅ **DB-driven** — a new category row is enough; the generic engine (services, RFQ, service bookings, payments, wallet) works without new code |

## Phase 3 — Marketplace & Transactions ✅

| Item | Status |
|---|---|
| Vendor discovery (`/vendors`, filters, cached) | ✅ |
| Vendor profiles (`/vendor/{slug}`) + white-label microsite (`/site/{slug}`) | ✅ |
| Advanced search & filtering | ✅ `SearchService` + `PublicVendors` filters |
| RFQ → bid → award → ServiceBooking | ✅ `ServiceBookingService::acceptQuote()` (awarding rejects competing bids, marks the RFQ `AWARDED`) |
| Quotation revisions | ✅ `RFQBid::updateOrCreate` + `rfq.revise` permission |
| Advance / installment / full payments with server-side verification | ✅ `ServiceBookingService::verifyServicePayment()` |
| Commissions (tenant rate → platform default) | ✅ `commissionRateFor()` reads `tenants.commission_rate`, falls back to `platform_settings.marketplace.commission.default_rate` |
| Wallet ledger + payouts | ✅ `wallet_transactions` (immutable, `balance_after` snapshot) + `vendor_payouts` + `VendorFinanceBoard` |
| Refunds & cancellation | ✅ refund reverses the wallet debit and the paid balance |

## Phase 4 — Enterprise Management ✅ / 🟡

| Item | Status |
|---|---|
| Customer CRM | 🟡 `crm_stage` / `pipeline_stage` on bookings + `contact_inquiries` leads |
| **Customer event workspace** | ✅ `CustomerEventWorkspace` — events, tasks, vendor shortlist, budget, linked bookings |
| Staff scheduling | ✅ attendance, leave, payroll, shift definitions |
| Communication | ✅ in-app notifications, hall chat, WhatsApp Cloud API |
| Analytics | ✅ `PlatformAnalyticsService`, `PlatformMetrics`, per-category dashboards |
| **Support tickets** | ✅ `SupportCenter` — customer raise, vendor reply, platform triage/assign/resolve |
| Disputes | 🟡 routed through `support_tickets.category = DISPUTE`; dedicated entity pending |
| Advanced reports | ✅ commission/effective-rate, wallet ledger, payout history, finance exports pending |

## Phase 5 — Premium UI/UX 🟡

| Item | Status |
|---|---|
| Public marketplace | ✅ exists (venues, vendors, menus, gallery, packages, policies) |
| Vendor dashboards | ✅ 5 category dashboards + generic ERP + quotations/services/portfolio/locations/finance boards |
| Super Admin dashboard | ✅ single console, 10 tabs |
| Customer portal | ✅ dashboard, bookings, profile, event workspace, support |
| Consistent sidebar / topbar / notification centre | ✅ `components/layouts/app.blade.php` with role-aware nav |
| Design system (`btn-gold`, `btn-outline-gold`, `input-luxury`, `font-serif-luxury`) | ✅ design tokens in `resources/css/app.css` |
| White-label vendor sites | ✅ `TenantBrand` + `ResolveTenantBrand` + `WhiteLabelSite` |
| Full art-direction pass on **every** public page | 🟡 remaining work |

## Phase 6 — Security, Performance & Launch ✅ / 🟡

| Item | Status |
|---|---|
| API fail-closed authorization | ✅ `ApiController` base, 401/403 paths tested |
| Dependency advisories | ✅ `composer audit` → **0 advisories** (9 patched) |
| 2FA enforcement | ✅ middleware now registered on the `web` group |
| Route-shadowing audit | ✅ `/vendor/{slug}`, `/api/v1/bookings/{id}`, `route('dashboard')` all fixed + regression-tested |
| Model integrity audit | ✅ 8 fatal/latent model defects fixed |
| Performance: eager loading, indexes, pagination | ✅ indexed `(tenant_id,status)`, `(customer_id,status)`, `(event_date,status)` on `service_bookings`; paginated boards |
| Queue / failed-job monitoring | 🟡 `jobs`/`failed_jobs` tables exist; no dashboard yet |
| Monitoring & error tracking | 🟡 Laravel Pail installed; Sentry-style reporting pending |
| Backup & restore | 🟡 documented in `deploy/PRODUCTION_CHECKLIST.md`; automated job pending |
| Browser QA | ✅ scripted interaction QA on the marketplace, vendor ERP, customer portal and admin console |

---

## Deferred — deliberately not faked

| Item | Why |
|---|---|
| Coupons / promotions / referral codes | Permission keys exist, no backing table. Building a half-feature would violate the "no fake functionality" rule. Recommended as the next Phase 4 increment. |
| First-class `disputes` table | Disputes are genuinely handled through support tickets today with full audit trail. A separate entity needs a refund/chargeback workflow to be worth it. |
| Vendor sub-categories | `vendor_categories` is flat on purpose — it keeps category routing (booking modes, dashboards, permissions) predictable. Add `parent_id` when the catalogue genuinely needs a second level. |
| Real gateway sandbox round-trips | Requires live JazzCash/Easypaisa/Stripe merchant credentials. The *verification* logic is unit-tested; the transport is not. |
| Native mobile app | The API is mobile-ready (tokens, scoping, `v1` versioning). Shipping an app is a separate product decision. |
