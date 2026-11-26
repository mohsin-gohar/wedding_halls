# Security and Tenant Isolation Report — Riwaj Grand

**Branch:** `audit/riwaj-grand-repair-20261126` · **Base:** `df8de437` · **Date:** 2026-11-26

## 1. Scope

Middleware (`bootstrap/app.php`, `app/Http/Middleware/*`), routes (`web.php`, `api.php`), policies/gates, 61 Livewire components, API tokens, uploads/private documents, session/auth/2FA/password reset, rate limiting, input validation and all 21 roles / 110 permissions in `App\Support\PermissionMatrix`.

## 2. Confirmed findings and resolutions

| Severity | Finding | Evidence | Resolution |
|---|---|---|---|
| CRITICAL | Public stored XSS in JSON-LD | `resources/views/livewire/public-hall-details.blade.php:35` used `{!! json_encode(...) !!}` without HEX flags; vendor-controlled `hall->name`, `venue->address` can close the `<script>` tag | Added `JSON_HEX_TAG/AMP/APOS/QUOT`; new `tests/Feature/PublicJsonLdSecurityTest.php` asserts escaped output |
| HIGH | Open redirect after 2FA | `TwoFactorAuthComponent.php:47,199,215` accepted absolute `?intended=` | Added `normalizeIntended()` allowing only local relative paths |
| HIGH | Cross-tenant data leak via walk-in `hall_id` | `WalkInBookingModal.php:200-215,558-591` loaded unscoped hall and used its tenant for package/menu/ShiftWindows data | Reset `hall_id` on rejection; staff hall query scoped by `tenant_id` |
| HIGH | Accountant access to HR attendance surface | `PermissionMatrix.php` ROLE_ACCOUNTANT had `staff.attendance`; dashboard/attendance tests expected denial | Permission removed; affected tests use HR/authorized personas |
| HIGH | Floor-staff privilege to create confirmed bookings/cash | Resolved matrix granted `bookings.create`; service-boundary test expects denial | Grant removed; walk-in tests use front-desk persona |
| HIGH | API customers denied own records | `BookingController::index/show` and `ServiceBookingController::index/show` called `assertPermission()` before customer scope | Permission check now skipped for customers; tenant/customer scoping retained |
| HIGH | HR manager could suspend privileged staff | `StaffManager::toggleStaffStatus()` lacked `authorizeTargetStaffRole()` | Added call alongside existing owner/self guards |
| MEDIUM | HR manager could provision privileged roles | `allowedRoles()` returned seven operational roles | Restricted to `ROLE_STAFF_MEMBER`; owner retains full set |
| MEDIUM | Livewire login only blocked `SUSPENDED`; reset auto-login ignored status | `Login.php:48`, `ResetPassword.php:64-71` | Require `ACTIVE` in both |
| MEDIUM | Password reset email enumeration | `ForgotPassword.php` used `exists:users,email` and distinct errors | Rule relaxed to `email`; generic success for both outcomes |
| MEDIUM | Wildcard API token self-service / platform-support global read / Livewire update throttling | Documented design choices | Left unchanged; require product decision (not silently altered) |

## 3. Controls verified correct

- Fail-closed API scoping (`ApiController::apiUser`, `scopeToPrincipal`, `assertTenantAccess`), bearer-token abilities, hash-at-rest and 90-day expiry, revoke scoped to owner.
- POST-only logout (`GET /logout` → **405**, verified live twice); CSRF exempt only for `api/*` and `webhooks/*`; webhook independently HMAC-verified.
- Livewire ERP authorization in `mount()` and each mutating action (`Dashboard`, `StaffManager`, `StaffAttendance`, `AttendanceCalendar`, `CalendarShiftGrid`, `ExpenseTracker`, `InventoryTracker`, `StaffPayroll`, `VendorServicesManager`, `WalkInBookingModal`).
- Document authorization: tenant match **and** explicit permission, customer matched by `customer_id`, private proof path traversal blocked.
- Upload validation: MIME + extension + size in `HallMediaManager`, `VendorPortfolioManager`, `HallChat`, `VenueChatWidget`.
- TOTP secret encrypted, recovery codes hashed/single-use, 2FA attempts throttled, password required to disable.
- Customer portal IDOR defenses in `CustomerDashboard::acceptQuote`, `CustomerEventWorkspace`, `SupportCenter::scopedTicket`.
- Chat views escape with `e()` before `nl2br()`.

## 4. Regression coverage

New/updated: `PublicJsonLdSecurityTest`, `ApiBookingFinancialIntegrityTest` (customer own-booking read, hidden hall non-disclosure), `AttendanceAuthorizationTest`, `DashboardAuthorizationTest`, `WalkInBookingAuthorizationTest`, `CalendarShiftAuthorizationTest`, `StaffManagerRoleAssignmentTest`, `DocumentAuthorizationTest`, `SecurityHardeningRegressionTest`. Cross-tenant suites (`CrossTenantIsolationRegressionTest`, `TenantPrivacyAndSecurityAuditTest`, `ChatTenantIsolationSecurityTest`) remain in the suite.

## 5. Remaining security gaps (not silently "fixed")

1. Livewire update endpoint lacks a global throttle; per-component guards exist for auth flows only.
2. Platform support has global API read scope; requires product sign-off or ticket-scoped implementation.
3. `*` API tokens can be self-issued; downstream RBAC still applies.
4. Financial FKs still cascade on deletion for several tables; application paths suspend instead of delete, but DB-level guarantee is incomplete.
5. Nginx/PHP/Livewire upload limits disagree (32m / 64m / 50MB); deployment config fix required before go-live.

No known unpatched CRITICAL issue remains in application code after the fixes above.
