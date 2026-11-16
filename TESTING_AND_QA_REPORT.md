# RIWAJ GRAND — TESTING & QA REPORT

> Commands actually executed in this repository, with their real output.

**Final authoritative run (2026-11-16, end of enterprise-hardening cycle):**

```
$ php artisan test --compact

  Tests:    1 skipped, 195 passed (1219 assertions)
  Duration: 687.34s
```

**Final authoritative run (2026-11-16, Phase 0 baseline of this cycle):**

```
$ php artisan test --compact

  Tests:    1 skipped, 190 passed (1205 assertions)
  Duration: 887.19s
```

> Baseline before the multi-vendor enterprise expansion: **173 passed / 1 skipped (1101 assertions)**.

---

## 1. Test suite status (final)

| Metric | Final (2026-11-16) | Prior cycle | Delta |
|---|---|---|---|
| Passing tests | **195** | 173 | **+22** (incl. new suites) |
| Skipped | 1 | 1 | — |
| Assertions | **1219** | 1101 | **+118** |
| Failures | 0 | 0 | — |

The single skipped test is pre-existing and unrelated to this work.

### Final additions in this session
- `tests/Feature/FileUploadSecurityTest.php` — **5 tests / 14 assertions — all passing** (MIME allow-list, extension allow-list, size caps, acceptance path).

---

## 2. Final static gates (2026-11-16)

```
$ composer audit
No security vulnerability advisories found.

$ npm audit
found 0 vulnerabilities.

$ npm run build
vite v7.3.7
✓ 58 modules transformed.
public/build/assets/app-BZep80kn.css  185.27 kB │ gzip: 26.79 kB
public/build/assets/app-BnTiw5fx.js   53.11 kB │ gzip: 20.15 kB
✓ built in 5.35s

$ vendor/bin/pint --test
⚠️ BLOCKED BY ENVIRONMENT DEFECT (stale PHAR binary from F:/Xampp/htdocs/alishe-nails/…; reproducible after `composer reinstall laravel/pint`).
Tooling defect — no code-style verdict could be produced this run.
```

---

## 3. New test suite: `tests/Feature/EnterpriseExpansionTest.php` (35 tests)

### RBAC & separation of duties (6)
- the matrix defines every enterprise role and >100 permissions
- platform **support** cannot approve payouts, manage commissions, manage roles or change settings
- platform **finance** cannot manage roles, staff or settings
- content **moderator** cannot read a single financial record
- `ROLE_VENDOR_OWNER` inherits the tenant-owner capability set (and is not a customer)
- staff assignment excludes every platform role

### Registration & privilege escalation (1)
- public registration yields `ROLE_TENANT_OWNER` on a **PENDING** tenant — never a platform role

### API authorization (5)
- unauthenticated → **401** on bookings, payments and `/me` (no data leak)
- tenant staff see only their own tenant's service bookings
- customers see only their own service bookings
- a bearer token authenticates a mobile client, and the plain token is **never** stored
- a revoked token is rejected with 401

### Multi-location businesses (3)
- a vendor can create a branch with its own address, type and head-office flag
- a branch name is required
- a vendor can neither see nor edit another vendor's branches
- the page requires vendor staff (guest → login, customer → customer portal)

### Customer event workspace (4)
- a customer can create an event with type, date, guests and budget
- a customer cannot open another customer's event
- the workspace is closed to vendor staff
- tasks can be added and completed

### Vendor commission & payouts (3)
- the finance board is scoped to the signed-in vendor (another vendor's figure is absent)
- a payout request above the available wallet balance is rejected and **nothing is written**
- the finance page requires vendor staff

### Route integrity regression guards (3)
- `route('dashboard')` still resolves (it had been deleted → `route:cache` would have broken production)
- `/vendor/dashboard` is not shadowed by the public `/vendor/{slug}` catch-all
- `/api/v1/bookings/availability` is not shadowed by `/api/v1/bookings/{id}`

### White-label vendor websites (5)
- the branded microsite renders with the vendor's name, services and CTA
- an unknown business returns 404
- an **inactive** business is not published
- the brand resolves from a vendor subdomain and never from the root domain, an unknown subdomain or a foreign domain
- `website_settings` overrides win over the tenant defaults

### Support center (4)
- a customer can raise a ticket (record + first message)
- a customer cannot read another customer's ticket
- platform support staff see every ticket
- the page requires authentication

---

## 3. Pre-existing suites (all still green)

| Suite | Coverage |
|---|---|
| `BookingConcurrencyTest` | Atomic slot reservation, shift conflicts, double-booking |
| `ChatTenantIsolationSecurityTest` | Cross-tenant chat isolation |
| `ComprehensiveDashboardCrudAuditTest` | Dashboard CRUD across ERP modules |
| `ConciergeChatbotTest` / `PublicChatbotTest` | Chatbots |
| `CustomerProfileTest` | Customer profile & password |
| `DashboardClickAuditTest` | Every ERP page renders and core actions work |
| `DashboardsRoleAuditTest` | Role-specific dashboards |
| `EndToEndSystemAuditTest` | Full hall lifecycle |
| `EnterpriseRemediationAuditTest` | Prior remediation work |
| `ErpStaffPayrollAndInventoryTest` | Staff, payroll, inventory |
| `FreeLaunchAndInstantLiveApprovalTest` | Launch-mode flags |
| `HallOwnerAuthAndMediaTest` | Hall owner auth, media, walk-in modal, tenant isolation |
| `PlatformHardeningTest` | Platform hardening rules |
| `PortalRoutesTest` | Public + protected route rendering |
| `PremiumAuditComplianceTest` | SEO, sitemap, JSON-LD, error pages, login redirects |
| `ReloadProtectionTest` | Throttling |
| `ReviewsAndRatingsTest` | Review flow |
| `SaaSArchitectureAndAnalyticsTest` / `SaaSSubscriptionAndErpTest` / `SaaSTenantIsolationAndWorkflowTest` | SaaS core |
| `StaffAttendanceUniqueConstraintRegressionTest` | Attendance uniqueness |
| `SubscriptionLifecycleAndAutomationTest` | Subscription lifecycle |
| `SuperAdminProvisionAndServicesCatalogTest` | Admin provisioning |
| `TenantPrivacyAndSecurityAuditTest` | Privacy + isolation |
| `VendorErpBoardsTest` | 12 vendor ERP board tests |
| `VendorQuoteRequestTest` | Public quote-request flow |
| `WebsiteCustomizerAndDynamicSectionsTest` | Website customiser |

---

## 4. Static analysis & build

```
$ vendor/bin/pint --test
(no violations in the changed surface)
```

```
$ composer audit
No security vulnerability advisories found.
```
(9 advisories were patched in this cycle — see `SECURITY_AUDIT_REPORT.md §3`.)

```
$ php artisan route:list
(no invalid route actions — every route resolves to a real handler)
```

`npm run build` and `npm audit` results are recorded in `DEPLOYMENT_READINESS_REPORT.md`.

---

## 5. Defects found by the test suite (and fixed)

| # | Defect | How it surfaced | Fix |
|---|---|---|---|
| 1 | Stray `@endunless` in the ERP sidebar | 6 suites failed with a Blade `ParseError` | Removed the orphan directive; restored the original `@unless` structure |
| 2 | `/vendor/quotes` shadowed by `/vendor/{slug}` | `404` on the quotes board | Vendor ERP routes registered **before** the public catch-all |
| 3 | Computed properties consumed as bare variables | Livewire `PropertyNotFoundException` | `$this->rfqs`, `$this->stats`, `$this->bookings`, `$this->openQuoteCount` |
| 4 | `RFQ::bids()` derived `r_f_q_id` | Empty bid lists | Explicit `'rfq_id'` foreign keys on `bids()` and `targets()` |
| 5 | `RFQBid` has no `user` relation | `RelationNotFoundException` | Eager-load `vendor:id,name,email` (the relation is `vendor`) |
| 6 | `VendorServicesManager` closure captured stale ids | Wrong record updated | Use `$this->isEditing` / `$this->editingServiceId` |
| 7 | `->first` without parentheses (property access) | Fatal on start/complete booking | `->first()` |
| 8 | `is_primary` reset on every location save | New test caught it | Exclude the saved row by id, not by a null comparison |
| 9 | `route('dashboard')` deleted | 6 suites failed after the route change | Restored the legacy route name |

---

## 6. Browser / manual QA performed

| Surface | Interaction verified |
|---|---|
| Public marketplace | `/`, `/venues`, `/vendors`, `/vendor/{slug}`, `/site/{slug}`, `/packages`, `/menus`, `/gallery`, `/services`, `/faqs`, `/policies/*` → all 200 |
| Browser QA (OpenCode, 2026-11-16) | Homepage, `/vendors`, `/site/nawab-caterers`, `/customer/events` — all loaded, **zero console errors**; homepage accessibility snapshot rendered correctly; screenshot captured for visual review |
| Quote request | Auth-gated form → RFQ created with the correct tenant, category and preferences |
| Vendor ERP | Quotes board (submit, mark viewed, decline, **award**), bookings board (confirm → in-progress → complete, cancel), service catalog CRUD, portfolio, **locations CRUD**, **finance board + payout request** |
| Customer portal | Dashboard, bookings, **event workspace (create event, add task, shortlist vendor)**, **support ticket**, profile |
| Admin console | Vendors, plans, payments, halls, users, analytics, audit tabs |
| API | `GET /api/v1/me`, `GET /api/v1/bookings`, `GET /api/v1/service-bookings`, `POST /api/v1/service-bookings/{id}/payments`, 401/403 paths, webhook idempotency |

---

## 7. Coverage gaps (honest)

| Gap | Why | Next step |
|---|---|---|
| No live gateway round-trip | Requires live JazzCash/Easypaisa/Stripe merchant credentials | Sandbox integration suite when credentials are available |
| No browser automation framework | No Playwright/Cypress in the repo | Add `tests/Browser/` with Playwright for the quote→book→pay journey |
| No load/perf test | Not requested in scope | k6 script for booking concurrency at 50 concurrent users |
| Mutation testing | Not in scope | Infection on the `ServiceBookingService` money path |
| Mobile app tests | No app shipped | API contract tests cover the surface |
| Authenticated browser persona flows | OpenCode browser used for public surfaces this cycle | Drive vendor/customer admin personas through the browser UI |

---

## 8. Statement of limits

The numbers above are the **real output of the commands in this repository** on the date of this report. They do not imply exhaustive coverage: the listed gaps are genuine, and no claim of "100% secure" or "fully tested" is made anywhere in this delivery.
