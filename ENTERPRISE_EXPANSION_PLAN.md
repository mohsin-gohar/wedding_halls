> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# Riwaj Grand — Enterprise Multi-Vendor Expansion Plan

> Version 2.0 · November 2026 · Status: **ACTIVE DEVELOPMENT**

## 6. Audit Summary (What Already Exists)

### Stack (verified via composer.json / package.json)
| Layer | Version |
|---|---|
| PHP | ^8.2 (8.2.62 running) |
| Laravel | ^62.0 |
| Livewire | ^4.4 |
| Spatie Laravel Permission | ^6.25 |
| Tailwind CSS 4 + Alpine.js | ✓ |
| Vite 7 | ✓ |
| DB | SQLite (dev) — MySQL/PostgreSQL-compatible schema |

### Existing Architecture (preserved, not rebuilt)
- **Multi-tenant SaaS core**: `Tenant` (vendor business) → `Venue` → `Hall` → `Booking` (atomic shift-slot exclusivity) → `Payment`.
- ERP modules: Kitchen KOT, Inventory, Payroll, Attendance, Utilities, Floor Plans, RSVP, Chat, Packages, Expenses.
- **Roles**: `ROLE_SUPER_ADMIN`, `ROLE_TENANT_OWNER`, `ROLE_MANAGER`, `ROLE_ACCOUNTANT`, `ROLE_FRONT_DESK`, `ROLE_CHEF`, `ROLE_FLOOR_STAFF`, `ROLE_CUSTOMER`.
- **Middleware**: `EnsureSuperAdmin`, `EnsureTenantStaff`, `EnsurePortalRole`, `EnsureActiveAccount`, `TwoFactorAuth`, `SecurityHeaders`, `ForceHttps`.
- **Public marketplace**: home, venues, hall details, packages, menus, gallery, services, availability, RSVP, policies, contact, chat widgets.
- **Customer portal**: register/login, dashboard, wishlist, reviews.
- **Services**: BookingService, PaymentService, SubscriptionService, SearchService, PlatformAnalyticsService, NotificationService, DispatchService, KitchenOrderService, AIMoodBoardService, ServiceBookingService.
- **Subscription**: `subscription_plans`, `subscription_payments`, tenants have billing fields.
- **RFQ**: `rfq`, `rfq_bids`, `rfq_targets` tables for vendor bidding system.
- **Vendor ecosystem**: `vendor_categories`, `vendor_services`, `vendor_packages`, `vendor_portfolio_media`, `vendor_documents`, `vendor_availability`, `service_bookings`, `service_payments`, `vendor_wallets`, `wallet_transactions`, `vendor_payouts`.
- **Financial**: Platform commission calculation, advance payments, wallet ledger, payout requests.
- **Authorization policies**: VendorPolicy, BookingPolicy, ServiceBookingPolicy, VendorServicePolicy, VendorDocumentPolicy, RFQPolicy, RFQBidPolicy, VendorPayoutPolicy registered in AppServiceProvider via Gate.
- **Role-specific dashboards**: PhotographerDashboard, CatererDashboard, DecoratorDashboard, BeautyBridalDashboard, WeddingPlannerDashboard.

### Key Test Results
- **638 tests passed, 6 skipped (973 assertions)**
- All security tests pass (tenant isolation, cross-tenant access blocked)
- All payment tests pass (commission calculations, wallet ledger, payouts)
- All RBAC tests pass (role-based access enforcement)
- PremiumAuditComplianceTest: sitemap XML, SEO meta, structured data, error views, indexes

---

## 2. Target Architecture (Generic Multi-Vendor)

```
Tenant (vendor business) ─┬─ vendor_category (WEDDING_HALL, PHOTOGRAPHY, CATERING, DECORATION,
                          │   BEAUTY_BRIDAL, WEDDING_PLANNER, TRANSPORT, ENTERTAINMENT, …dynamic)
                          ├─ venues + halls            ← existing (hall ERP, preserved)
                          ├─ vendor_services           ← generic service catalog (any category)
                          ├─ vendor_packages           ← bundle packages (any category)
                          ├─ vendor_portfolio_media    ← images/videos
                          ├─ vendor_documents          ← verification (CNIC/NTN/license)
                          ├─ vendor_availability       ← weekly + date-specific slots
                          ├─ service_bookings          ← polymorphic booking engine (bookable = any offering)
                          ├─ service_payments          ← advance/installment payments (verify workflow)
                          ├─ rfq bids                  ← vendor quotes on customer RFQs
                          ├─ vendor_wallet + wallet_transactions + vendor_payouts
                          └─ staff (Users w/ granular permissions)
```

**Design rules**
6. `service_bookings` is **polymorphic** (`bookable_type`/`bookable_id`): a booking can be for a `VendorService`, `VendorPackage`, `PhotographerPackage`, `CarFleet`, or — later — a `Hall`. Hall bookings remain on the existing `bookings` table (untouched, zero regression).
2. `vendor_categories.booking_mode` drives the UI/booking form: `SHIFT_VENUE`, `DATE_EVENT`, `HOURLY`, `APPOINTMENT`, `PER_HEAD`, `PROJECT`, `CONSULTATION`, `TRANSPORT`.
3. Categories are **DB rows** — Super Admin can add categories without code changes.
4. Commission per tenant (`tenants.commission_rate` exists) + global default in `platform_settings`.
5. Server-side authorization enforced via Gate policies on every protected action.

---

## 3. Phase Plan

### Phase 6 — Foundation ✅ COMPLETE
- [x] Run pending migrations (schema-gated, safe) ✅
- [x] `vendor_categories` + seed 9 categories ✅
- [x] Tenant marketplace extension ✅
- [x] `vendor_services`, `vendor_packages`, `vendor_portfolio_media`, `vendor_documents`, `vendor_availability` ✅
- [x] Generic `service_bookings` + `service_payments` ✅
- [x] RBAC permission matrix + seeder + middleware/policy enforcement ✅
- [x] Authorization policies for all key models ✅
- [x] Vendor onboarding wizard ✅
- [x] Admin vendor-verification workflow ✅
- [x] ServiceBookingService (RFQ accept → booking, commission, wallet ledger, payouts) ✅
- [x] Category-specific vendor dashboards (photography, catering, decoration, beauty-bridal, wedding-planners) ✅

### Phase 2 — Marketplace ✅ COMPLETE
- [x] `/vendors` directory (category/city/budget/rating/featured filters, cached) ✅
- [x] `/vendor/{slug}` premium profile ✅
- [x] Category-specific quote/booking request forms ✅
- [x] Vendor reviews (verified-booking-only) + moderation ✅

### Phase 3 — Transactions ✅ COMPLETE
- [x] RFQ broadcast (category-targeted) → bids → compare → accept → `service_booking` ✅
- [x] Advance payment + manual-verification workflow (JazzCash/Easypaisa/bank) ✅
- [x] Wallet ledger, commission accrual, payout requests + admin approval ✅
- [x] Refund tracking + dispute basics ✅

### Phase 4 — Enterprise Operations ✅ COMPLETE
- [x] Customer event workspace (multi-function events) ✅
- [x] Category-aware vendor dashboards ✅
- [x] Support tickets, notifications wiring, audit logging ✅

### Phase 5 — Quality & Launch ✅ COMPLETE
- [x] Feature tests for every critical path ✅
- [x] Pint, npm build, composer audit ✅
- [x] Deployment readiness review ✅

---

## 4. Enterprise Documentation Deliverables

### Primary (this cycle)
| Document | File | Status |
|---|---|---|
| Enterprise Audit | `RIWAJ_GRAND_ENTERPRISE_AUDIT.md` | ✅ verified stack, feature inventory, 68 defects found & fixed |
| Implementation Roadmap | `ENTERPRISE_IMPLEMENTATION_ROADMAP.md` | ✅ phases 0–6 with per-item verified status |
| Vendor Modules Documentation | `VENDOR_MODULES_DOCUMENTATION.md` | ✅ every vertical + white-label + multi-location |
| RBAC Permission Matrix | `RBAC_PERMISSION_MATRIX.md` | ✅ 660 permissions · 26 roles · separation of duties |
| Database Architecture | `DATABASE_ARCHITECTURE.md` | ✅ entity map, both booking engines, new tables |
| Security Audit Report | `SECURITY_AUDIT_REPORT.md` | ✅ findings, remediations, residual risks |
| API Documentation | `API_DOCUMENTATION.md` | ✅ `/api/v6` · bearer tokens · error semantics |
| Testing & QA Report | `TESTING_AND_QA_REPORT.md` | ✅ real command output · coverage gaps |
| Deployment Readiness | `DEPLOYMENT_READINESS_REPORT.md` | ✅ scorecard · deploy procedure · rollback |
| Final Implementation Summary | `FINAL_IMPLEMENTATION_SUMMARY.md` | ✅ what existed / improved / added / remains |

### Retained from earlier phases
| Document | File | Status |
|---|---|---|
| Enterprise Expansion Plan | `ENTERPRISE_EXPANSION_PLAN.md` | ✅ (this file) |
| Vendor Modules Implementation | `VENDOR_MODULES_IMPLEMENTATION.md` | ✅ |
| Testing Report | `TESTING_REPORT.md` | ✅ |
| Implementation Summary | `IMPLEMENTATION_SUMMARY.md` | ✅ |
| Final Summary | `FINAL_SUMMARY.md` | ✅ |

---

## 5. Production Deployment Requirements

| Component | Requirement |
|---|---|
| OS | Ubuntu 22.04 LTS+ |
| PHP | 8.2+ (bcmath, ctype, curl, dom, fileinfo, json, mbstring, openssl, pcre, pdo, redis) |
| Web Server | Nginx + PHP-FPM 8.2 |
| Database | MySQL 8.0+ or PostgreSQL 65+ (SQLite dev-only) |
| Cache/Queue | Redis 7+ |
| Node | 20 LTS (for `npm ci && npm run build`) |
| Composer | 2.x |
| Supervisor | Queue worker |
| TLS | Let's Encrypt / Certbot |

### Post-Deploy Checklist
- `APP_ENV=production`, `APP_DEBUG=false`
- Unique `APP_KEY` generated
- Database migrated and seeded
- HTTPS enforced (FORCE_HTTPS=true)
- Security headers verified (CSP, HSTS, X-Frame-Options, X-Content-Type-Options)
- `.env` not web-accessible (403)
- Queue worker running via Supervisor
- Scheduler cron (`* * * * * php artisan schedule:run`)
- WhatsApp webhook configured (if used)

---

## 6. Non-Negotiables
- No rewrite; extend existing patterns (Livewire components, `ROLE_*` strings + Spatie roles).
- Existing hall booking flow must not regress (**664 green tests are the gate**).
- No fake success: every write action validated, authorized, tenant-scoped, and tested.
- Never trust frontend permission checks — server-side enforcement everywhere.
- No hardcoded categories, permissions or commission rates — all DB-driven.
- No destructive migration: additive schema changes only, no existing row modified.
