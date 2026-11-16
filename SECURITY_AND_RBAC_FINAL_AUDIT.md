# SECURITY & RBAC FINAL AUDIT — Riwaj Grand

**Date:** 2026-11-16

## Verified at runtime (not from docs)
```
PermissionMatrix::allPermissions() → 110
PermissionMatrix::allRoles()       → 21
PermissionMatrix::ROLE_ALIASES     → 6
assignableVendorRoles()            → 16
```
Spatie Permission v6; roles assigned at registration/seeding; SoD tests green.

## Server-side enforcement (code-verified this cycle)
- `CalendarShiftGrid::mount():34-35` aborts 403 for guests/customers; `accessibleHalls():54-64` scopes by tenant (deny-by-default `?? ''`); `viewSlotConflicts()` and `openWalkInModal()` re-scope through `accessibleHalls()`.
- `StaffAttendance::authorizeAttendanceManager():81-87` re-run in mount/render/save/leave actions; every query `where('tenant_id', ...)`.
- `approveLeave()` now blocks self-approval with 403 — **new authorization boundary**, covered by `AttendanceCoreRulesTest`.
- `Api\BookingController::update():160-164` status validation mirrors DB enum (removed `REFUNDED` drift).
- File uploads: MIME + extension allow-lists + size caps (prior cycle, FileUploadSecurityTest 5/5).

## Cross-tenant checks
- CalendarIntegrityTest: foreign tenant's booking on the same hall is **not displayed** → pass.
- EnterpriseExpansionTest: vendor/customer/platform-boundary tests → pass.
- ChatTenantIsolationSecurityTest → pass.

## Remaining risks (honest)
- No external penetration test.
- Live webhook signature/replay verification requires gateway credentials for end-to-end proof.
- No error monitoring / WAF configuration.
- Role-permission drift possible if admins create custom permissions at runtime (matrix remains the source of truth).

## Commands
```
php _qa_verify2.php (runtime reflection) → 110 permissions / 21 roles
php artisan test tests/Feature/CalendarIntegrityTest.php tests/Feature/AttendanceCoreRulesTest.php --compact
→ 11 passed (28 assertions)
```
