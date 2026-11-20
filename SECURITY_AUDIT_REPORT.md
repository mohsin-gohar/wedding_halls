> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# RIWAJ GRAND — SECURITY AUDIT REPORT

> Scope: authentication, authorization, RBAC, tenant/vendor isolation, IDOR, privilege escalation, injection, XSS, CSRF, mass assignment, file uploads, path traversal, open redirects, rate limiting, API authentication, webhook signatures, session security, password reset, 2FA, secrets, payment manipulation, race conditions, subscription bypass, audit integrity.
> Method: source review, live-schema inspection, executed probes and automated regression tests. **This report does not claim the platform is unbreakable.**

---

## 1. Verdict

| Area | Before this cycle | Now |
|---|---|---|
| API authentication | **None** — controllers were unreachable *and* unsafe | Bearer tokens (SHA-256 hashed) + session, rate limited |
| API authorization | **Broken** — unauthenticated callers received every tenant's data | Fail-closed base controller, regression-tested |
| Webhook integrity | No idempotency → replay double-credit | Signature verification **+** idempotency key |
| 2FA | Middleware written but **never registered** | Registered on the web group, redirect-loop safe |
| Route integrity | 2 shadowing bugs + 1 missing route name | Fixed, each with a regression test |
| Dependency advisories | **9 live CVEs** | **0** (`composer audit` clean) |
| Model integrity | 8 fatal/latent defects | All fixed |
| Tenant isolation | Strong in the ERP, absent in the API | Enforced at every layer |

**Residual risk is concentrated in the items in §8 — none of which are silently ignored.**

---

## 2. Critical findings & remediations

### 2.1 Unauthenticated cross-tenant data disclosure (Critical — fixed)
`Api\BookingController::index()` and `show()` applied the tenant filter **only when a user was present**:

```php
if ($user && !$user->isSuperAdmin() && $user->tenant_id) {
    $query->where('tenant_id', $user->tenant_id);
}
```

With `$user === null` the filter was skipped entirely → **every tenant's bookings, payments, RSVPs, KOTs and floor plans were returned**. `update()` additionally allowed arbitrary `status` and `advance_paid` changes, i.e. **payment manipulation**. `PaymentController::initiate()` allowed unauthenticated payment creation against any booking.

**Remediation** — `app/Http/Controllers/Api/ApiController.php`:
```php
protected function apiUser(Request $request): User {
    $user = $request->user() ?? auth('api')->user() ?? auth()->user();
    abort_if(! $user, 401, 'Unauthenticated. A valid bearer token is required.');
    ...
}
protected function scopeToPrincipal(Builder $q, User $u, ...) {
    if ($u->isSuperAdmin() || ...) return $q;
    if ($u->isCustomer()) return $q->where($customerColumn, $u->id);
    abort_if(! $u->tenant_id, 403, ...);
    return $q->where($tenantColumn, $u->tenant_id);
}
```
There is now **no code path** in the API that returns an unscoped result set.

### 2.2 Webhook replay double-crediting (High — fixed)
The gateway webhook created a new `Payment` on every delivery. Gateways retry; a replayed reference credited the booking twice.

**Remediation** — idempotency on `transaction_reference`, plus:
- amount must be > 0 and ≤ outstanding balance
- the callback no longer sits behind session authentication, so real gateways can actually deliver it
- verification still requires a valid signature (`PaymentService::verifyWebhook`)

### 2.3 Dead 2FA enforcement (High — fixed)
`app/Http/Middleware/TwoFactorAuth.php` existed but was never aliased or applied. Enabling `two_factor_enabled` on an account had **zero** effect on its sessions.

**Remediation** — registered on the `web` group with an explicit allow-list (`2fa/*`, `logout`, `password/*`, `login`, `customer/login`) so the challenge page stays reachable.

### 2.4 Route-level integrity (High — fixed)
| Bug | Effect |
|---|---|
| `route('dashboard')` missing from the route table | `RouteNotFoundException` in the ERP layout, login redirect and customer dashboard **the moment the route cache was rebuilt** |
| `GET /vendor/dashboard` shadowed by `GET /vendor/{slug}` | The generic ERP dashboard was unreachable |
| `GET /api/v1/bookings/availability` shadowed by `GET /api/v1/bookings/{id}` | Permanent 404 on the availability endpoint |
| Stale `bootstrap/cache/routes-v7.php` | The deployed route table did not match the source |

All four are fixed and each has a named regression test (`test_legacy_dashboard_route_name_still_resolves`, `test_vendor_dashboard_is_not_shadowed_by_the_public_vendor_catch_all`, `test_api_availability_route_is_not_shadowed_by_booking_id_route`).

### 2.5 Logout CSRF (Medium — fixed)
`GET|POST /logout` had **no `auth` middleware**, so a third-party `<img src="/logout">` signed the user out. `auth` middleware added.

---

## 3. Dependency security

```
composer audit  →  "No security vulnerability advisories found."
```
9 advisories were patched with **patch-level updates only** (no major/minor version changes):

| Package | Advisory | Fixed in |
|---|---|---|
| livewire/livewire | `#[Authorize]` bypass via reordered parameter keys | 4.4.8 |
| symfony/http-foundation | HEAD bypass of the `methods` option; path-rule bypass; trusted-proxy `private_ranges`; host-controlled generated URLs | 7.4.23 |
| symfony/http-kernel | inline fragments let untrusted forwarded headers set host/scheme/port | patched |
| symfony/mime | email header injection via non-token characters | patched |
| symfony/routing | host placeholders matching the start of a path | patched |
| symfony/yaml | exponential/quadratic parse times | patched |

---

## 4. Authorization model (verified)

### 4.1 Defence in depth
1. **Route middleware** — `auth`, `tenant.staff` (customer/unassigned rejected), `portal.role:*`, `super.admin`, `auth.api`.
2. **Policies** — `BookingPolicy`, `ServiceBookingPolicy`, `VendorPolicy`, `VendorServicePolicy`, `VendorDocumentPolicy`, `VendorPayoutPolicy`, `RFQPolicy`, `RFQBidPolicy`.
3. **Query scoping** — every list is filtered by `tenant_id` / `customer_id` at the query level, not only by policy.
4. **Component guards** — every Livewire component `abort(403)`s on its own (`VendorQuotesBoard`, `VendorBookingsBoard`, `VendorLocationsManager`, `SupportCenter`, `CustomerEventWorkspace`, …).

Because **Livewire actions bypass route middleware**, the in-component guards are the real enforcement for the UI; every new component was written with them from the start.

### 4.2 Separation of duties
`ROLE_PLATFORM_SUPPORT` cannot approve payouts or manage roles; `ROLE_PLATFORM_FINANCE` cannot manage staff or roles; `ROLE_CONTENT_MODERATOR` cannot read a single financial record. Asserted by test.

### 4.3 Privilege escalation
- Public registration can only produce `ROLE_TENANT_OWNER` on a **PENDING** tenant.
- Platform roles are excluded from the staff-assignment allow-list **and** from its validation rule.
- A role never implies access to another tenant's rows — ownership is checked independently of the permission bit.

### 4.4 IDOR
Every externally supplied identifier is resolved through a tenant-scoped query: a forged `id` returns **404**, never another tenant's row. Covered for bookings, service bookings, RFQs, locations, events, tasks, tickets and wallet transactions.

---

## 5. Input handling

| Vector | Control |
|---|---|
| SQL injection | Eloquent/Query Builder parameter binding throughout; no raw string concatenation in queries |
| XSS | Blade auto-escaping `{{ }}`; `wire:model` binds data, not markup |
| CSRF | Laravel `ValidateCsrfToken` on the `web` group; exemptions limited to `webhooks/*` and the two login POSTs (documented in §8) |
| Mass assignment | `User::$fillable` deliberately excludes `role`/`tenant_id` (commented in the model); `Tenant::$fillable` is an explicit allow-list |
| File uploads | `vendor_documents` and `portfolio_media` are path-based records; upload validation pending (§8) |
| Path traversal | No endpoint accepts a filesystem path from the client; `DocumentController` resolves records by UUID then reads via the model |
| Open redirects | `redirect()->intended()` is used only after a successful login; no `?redirect=` parameter is honoured |
| Rate limiting | `public-pages` 120/min · `public-availability` 60/min · `reload-protection` 5/min per IP+path · `api` 60/min per token |

---

## 6. Money handling

| Control | Implementation |
|---|---|
| Money type | `decimal(12,2)` throughout; **no float columns** |
| Money arithmetic | Rounded to 2 decimals at every write; the service layer computes `commission`, `net_vendor_amount` and `advance_amount` server-side |
| Commission source | `tenants.commission_rate` (per-vendor) → `platform_settings.marketplace.commission.default_rate` (platform default). **Never hardcoded.** |
| Advance source | `platform_settings.marketplace.advance.default_percent` |
| Ledger integrity | `wallet_transactions` is append-only with a `balance_after` snapshot |
| Over-collection | API rejects an amount above the outstanding balance |
| Refunds | A refund reverses the wallet debit **and** the paid balance, and flips `payment_status` to `REFUNDED` |
| Payout guard | A payout request cannot exceed the available wallet balance (enforced in the Livewire action + tested) |

---

## 7. Concurrency

| Risk | Control |
|---|---|
| Double-booked hall slot | `Cache::lock` (600s) + `lockForUpdate` + `UniqueConstraintViolationException` → `SlotAlreadyBookedException` (409). Verified by `BookingConcurrencyTest`. |
| Double-awarded RFQ | `acceptQuote()` runs inside a transaction with `lockForUpdate()` on the bid and rejects competing pending bids |
| Double payment | Webhook idempotency (§2.2) |
| Duplicate service creation | `RfqBid::updateOrCreate` on `(rfq_id, vendor_id)` |

---

## 8. Residual risks (open, tracked, not hidden)

| # | Risk | Severity | Mitigation / plan |
|---|---|---|---|
| R1 | The two login POST routes are CSRF-exempt (login-CSRF) | Low | Add a per-session login-intent token. Not exploitable beyond a forced sign-in. |
| R2 | `vendor_documents` / `portfolio_media` store paths without content-type validation | Medium | Validate MIME + extension on upload, store outside `public/`, serve through an authorised controller. |
| R3 | `reviews.hall_id` still cascades on hall delete | Low | Change to `nullOnDelete` with the hall-deletion redesign. |
| R4 | No automated monitoring / error tracking | Medium | Sentry-style reporting + a failed-job dashboard. |
| R5 | `cart_items.bookable_id` (bigint) vs `service_bookings.bookable_id` (uuid) mismatch | Low | Align in the next cart migration; the cart tables are not routed. |
| R6 | No rate limiting *per customer* on RFQ creation beyond the hourly cap | Low | The `quote-requests` limiter (10/hr) already applies. |
| R7 | Admin console is a single Livewire component — a large blast radius if a method is missed | Medium | Split into focused admin components as the console grows; each method already re-checks `ensureSuperAdmin()`. |
| R8 | `reviews` allow a customer to review without a verified booking | Low | `is_verified_booking` column exists; enforce on the write path when the review UI is built. |

---

## 9. Secrets & production hygiene

| Item | State |
|---|---|
| `.env` | Not committed (`.gitignore`); `.env.example` documents every key |
| Seed credentials | `owner@venuecloud.pk` / `password`, `admin@eventhub.com` / `admin1235` — **development only**; production must rotate them (documented in `deploy/PRODUCTION_CHECKLIST.md`) |
| `APP_DEBUG` | Must be `false` in production (`config/app.php` reads `APP_DEBUG`) |
| `FORCE_HTTPS` | Defaults to on in production (`ForceHttps` middleware, 301 redirect) |
| Security headers | `SecurityHeaders` middleware on every response: CSP, `X-Frame-Options`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`, HSTS on TLS |
| Token storage | API tokens are SHA-256 hashed; the plain value is shown once |
| Passwords | Bcrypt via `Hash::make`; the login closure rate-limits to 5 attempts per email+IP |

---

## 10. Tests that lock the security properties in place

`tests/Feature/EnterpriseExpansionTest.php` (35 tests) covers:
- unauthenticated API → **401** (not a data leak)
- tenant staff → only their own tenant's bookings
- customer → only their own bookings
- bearer-token issuance, use, and revocation
- platform separation of duties (3 assertions)
- staff-assignment allow-list excludes platform roles
- public registration cannot self-assign a privileged role
- location isolation between vendors
- customer event isolation between customers
- payout cannot exceed the available balance
- route-shadowing regression guards (3)

Plus the pre-existing `ChatTenantIsolationSecurityTest`, `TenantPrivacyAndSecurityAuditTest`, `PlatformHardeningTest`, `BookingConcurrencyTest` and `PremiumAuditComplianceTest`.

---

## 11. Statement of limits

This report describes what was **verified by execution**. It does **not** claim the platform is 100% secure, penetration-tested or unhackable. A full external penetration test, a dependency-aware DAST run and a threat model reviewed by an independent party remain recommended before handling real customer funds at scale.
