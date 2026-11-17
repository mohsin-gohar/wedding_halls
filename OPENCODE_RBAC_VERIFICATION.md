# OPENCODE RBAC VERIFICATION — Riwaj Grand

**Date:** 2026-11-16
**Branch:** `enterprise-hardening`
**Scope:** Verification of the server-side Spatie Permission RBAC system.

---

## 1. REQUIREMENTS CHECKLIST

| # | Requirement | Status |
|---|---|---|
| 1 | Granular permissions for every important module | ✅ 110 granular permissions in `App\Support\PermissionMatrix` |
| 2 | Role-permission assignment | ✅ Spatie roles + permissions tables + seeder |
| 3 | Custom vendor roles where appropriate | ✅ Vendor staff roles (PHOTOGRAPHER, CHEF, DECORATOR, MAKEUP, PLANNER) |
| 4 | Platform vs vendor administration separation | ✅ Separate platform/vendor role sets |
| 5 | Safe staff-role assignment | ✅ Vendor staff cannot be assigned platform roles (SoD) |
| 6 | Server-side authorization (no frontend-only checks) | ✅ Policies + gates + middleware + Livewire action gates + API gates |
| 7 | Policies and gates | ✅ Implemented & tested |
| 8 | Middleware enforcement | ✅ `EnsurePortalRole` + route middleware |
| 9 | Livewire action authorization | ✅ Component-level gates |
| 10 | API authorization | ✅ Bearer tokens + tenant scoping + ownership checks |
| 11 | Tenant and vendor ownership checks | ✅ |
| 12 | Customer record ownership checks | ✅ |
| 13 | Audit logs for privileged changes | ✅ Spatie activity logs on privileged mutations |
| 14 | Tests for every critical permission boundary | ✅ EnterpriseExpansionTest + dashboards audits + SaaS isolation suites |

---

## 2. ARCHITECTURE

- Central matrix: `app/Support/PermissionMatrix.php`
- Spatie permission package v6
- Roles include platform roles (`SUPER_ADMIN`, `PLATFORM_SUPPORT`, `PLATFORM_FINANCE`, `CONTENT_MODERATOR`) and vendor roles (`TENANT_OWNER`, staff categories, `MANAGER`, `ACCOUNTANT`, `BOOKING_MANAGER`, `SERVICE_STAFF`, `INVENTORY_MANAGER`, `HR_MANAGER`, `PHOTOGRAPHER`, `CHEF`, `DECORATOR`, `MAKEUP_ARTIST`, `PLANNER`).

---

## 3. VERIFIED BOUNDARY TESTS (all green)

From the enterprise suite and adjacent suites:

| Boundary | Test | Result |
|---|---|---|
| Platform support cannot approve payouts / manage commissions / manage roles / change settings | `EnterpriseExpansionTest` | ✅ PASS |
| Platform finance cannot manage roles / staff / settings | `EnterpriseExpansionTest` | ✅ PASS |
| Content moderator cannot read financial records | `EnterpriseExpansionTest` | ✅ PASS |
| Vendor owner inherits tenant-owner capability set | `EnterpriseExpansionTest` | ✅ PASS |
| Staff assignment excludes platform roles (SoD) | `EnterpriseExpansionTest` | ✅ PASS |
| Public registration never creates privileged accounts | `EnterpriseExpansionTest` | ✅ PASS |
| Unauthenticated API access → 401 (no data leak) | `EnterpriseExpansionTest` | ✅ PASS |
| Tenant staff see only their own tenant's bookings | `EnterpriseExpansionTest` | ✅ PASS |
| Customer sees only their own bookings | `EnterpriseExpansionTest` | ✅ PASS |
| Revoked API token rejected | `EnterpriseExpansionTest` | ✅ PASS |
| Customer cannot open another customer's event | `EnterpriseExpansionTest` | ✅ PASS |
| Vendor cannot see another vendor's payouts | `EnterpriseExpansionTest` | ✅ PASS |
| Vendor finance page requires vendor staff | `EnterpriseExpansionTest` | ✅ PASS |
| Support ticket customer isolation | `EnterpriseExpansionTest` | ✅ PASS |
| Hall owner cannot open another tenant's chat thread | `ChatTenantIsolationSecurityTest` | ✅ PASS |
| Tenant isolation (public hall/vendor surfaces) | `SaaSTenantIsolationAndWorkflowTest` | ✅ PASS |

Final full-suite integration of RBAC enforcement:

```
php artisan test --compact
→ Tests: 1 skipped, 195 passed (1219 assertions) — RBAC suites included & green
```

---

## 4. HONEST LIMITATIONS

- Custom granular permissions beyond the matrix can still be created at runtime in the admin console; the seeder defines defaults.
- Some UI-only permissions exist (navigation display) — backend is authoritative (asserted by tests that hit Livewire actions directly).

---

*Next: `OPENCODE_DATABASE_REVIEW.md`.*
