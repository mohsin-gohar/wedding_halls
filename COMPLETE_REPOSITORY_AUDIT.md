# Complete Repository Audit — Riwaj Grand

**Branch:** `audit/riwaj-grand-repair-20261126`  
**Base commit:** `df8de43717415f3c7f47d50845f114502f437037` (inherited an in-progress merge with 16 unresolved files)  
**Audit date:** 2026-11-26  
**Authority:** This document is the current audit ledger for this pass. Historical `CURSOR_*`, `OPENCODE_*`, `RIWAJ_GRAND_*` and other older reports are preserved as context; see `DOCS_STATUS.md`.

## 1. Scope and method

- Read `AGENTS.md` (none present), `README.md`, `system-design.md`, `DOCS_STATUS.md`, `FINAL_REPOSITORY_AUDIT.md`, `PRODUCTION_READINESS_REPORT.md`, `CASH_ONLY_PAYMENTS.md`, `MASTER_REMEDIATION_TODO.md`, deployment guides and CI workflow before any edits.
- Inspected git state: branch `main`, ahead 307 commits, two merge commits at HEAD, staged hardening work, and 16 unmerged index entries (`UU`/`AA`). Created the dedicated repair branch **without touching `main`** and preserved all in-progress work.
- Ran four parallel read-only audit passes: security/tenancy, financial/business logic, database/performance/deployment, and tests/CI coverage. Findings are consolidated below.
- No production data, production migration, deployment, force-push or merge into `main` was performed.

## 2. Architecture map (confirmed)

| Area | Implementation |
|---|---|
| Runtime | PHP 8.2+, Laravel 12, Livewire 4, Vite 6, Tailwind 4, Spatie Permission, MySQL 8 |
| Multi-tenancy | `tenant_id` scoping, `Hall::publicVisible()`, `Tenant::marketplaceVisible()`, `TenantAccess`, 21 roles / 110 permissions via `App\Support\PermissionMatrix` |
| Money | `CashOnlyPayments` + `GuardsCashOnlyMethod`; cash only for all new writes; historical labels preserved |
| Domain services | `BookingService`, `ServiceBookingService`, `PaymentService`, `SubscriptionService`, `HallAvailabilityService` with locks/transactions |
| UI | Public marketplace, hall ERP, vendor ERP, customer workspace, super-admin console, printable documents |
| API | `/api/v1` bearer tokens with abilities, hashed at rest, 90-day default expiry |
| Ops | Docker (php-fpm + queue + scheduler under supervisor), Hostinger deployment scripts, CI with lint/Pint/audits/build/tests/browser, deployment job disabled |

## 3. Issue register (severity → resolution)

### CRITICAL
| ID | Finding | Resolution |
|---|---|---|
| C1 | Service-booking cancellation/debit reversed the **gross** amount from the vendor wallet even when only net was credited; refund failed and rolled back (wallet 0 / partial payments) | `reverseBookingPayment()` now reverses only `min(refund, credited − commission − already reversed, balance)`, never throws on an empty wallet; cash refund is always recorded; pending payout for the booking is cancelled; audit entry added |
| C2 | Concurrent cancellation could create two cash refunds (no row lock / idempotency) | Booking row locked with `lockForUpdate()` inside the transaction and existing REFUND row check added |
| C3 | Undefined `ExpenseTracker::assertExpenseCanBeVoided()` made every expense-void fatal | Method added, delegating to the source-linked expense guard |
| C4 | Stored XSS: unescaped JSON-LD on public venue pages (`json_encode` without HEX flags) allows `</script>` breakout from vendor-controlled hall/venue fields | Added `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`; regression test added |
| C5 | Merge-broken Blade markup in `inventory-tracker.blade.php` (extra `@endif`, duplicate `@else`) caused 500s on `/inventory` and nine test failures | Markup balanced; compiled views cleared |

### HIGH
| ID | Finding | Resolution |
|---|---|---|
| H1 | Cancellation and lifecycle transitions were not audited despite cash-only policy | `SERVICE_BOOKING_CANCELLED` audit entry added inside the refund transaction |
| H2 | Wallet `balance_after` could be stale under concurrent credits | Re-read balance after atomic increment |
| H3 | API customers were denied own booking reads by an unconditional `bookings.view` check in `BookingController`/`ServiceBookingController` | Permission check now applies only to non-customers; customer scope still enforced |
| H4 | Accountant had `staff.attendance` (HR surface) and floor staff had `bookings.create` (confirmed bookings + cash) | Removed both grants; adjusted affected test fixtures to use authorized personas |
| H5 | Stale route/view cache made `/ready` return 404 in tests | Caches cleared; no code change needed |
| H6 | 2FA `intended` redirect accepted absolute URLs (open redirect) | Restricted to local relative paths |
| H7 | Tampered `hall_id` in walk-in terminal leaked another tenant's package/menu/shift data | Cross-tenant hall selection reset and staff hall query tenant-scoped |
| H8 | Livewire login checked only `SUSPENDED`; reset auto-login skipped status | Both now require `ACTIVE`; forgot-password no longer reveals account existence |

### MEDIUM / LOW (fixed where safe)
- HR manager could suspend managers/accountants: added target-role authorization to `toggleStaffStatus()`.
- HR manager could provision privileged roles: `allowedRoles()` restricted to `ROLE_STAFF_MEMBER`.
- Expense default category was outside the validation allow-list: defaulted to `OTHER`.
- Expense void idempotency branch unreachable due to global scope: `withoutGlobalScope('not_voided')` used.
- `UtilityLog::expense()` could not resolve voided source rows: global scope bypassed on relation.
- New migrations unguarded against schema drift: `Schema::hasColumn`/`hasIndex` guards added.
- `acceptQuote()` trusted caller for bid/RFQ/customer ownership: assertions added inside the locked transaction.
- Pay reservation for cancelled bookings: additive `vendor_payouts.service_booking_id` FK + cancellation on refund.
- Test-side corrections: missing helper, wrong HTTP verb (PUT→PATCH), INF/NAN via Livewire transport, seeded-data assumptions, rate-limiter cache isolation, role fixture policy alignment.

### Documented / not changed (design or infra-dependent)
- Platform support global API read scope; wildcard API token creation; floor-staff policy decision (documented above); Livewire update endpoint rate limiting; financial FK cascades; unbounded dashboard result sets; utility tracker `expense_id` backfill; Nginx upload-limit mismatch; PHP-FPM running as root in supervisor; Docker/composer-audit verification blocked locally.

## 4. Files changed in this pass

`app/Http/Controllers/Api/ApiController.php`, `BookingController.php`, `ServiceBookingController.php`, `DocumentController.php`, `WhatsAppWebhookController.php`; `app/Livewire/*` (auth, dashboards, ERP, vendor boards, walk-in); `app/Models/*`; `app/Services/ServiceBookingService.php`; `app/Support/PermissionMatrix.php`; 16 conflict resolutions; new/updated migrations, tests and views. Full list is in `git log` between `df8de43` and the final acceptance commit.

## 5. Verification summary

See `TESTING_AND_BROWSER_QA_REPORT.md` and `FINAL_ACCEPTANCE_REPORT.md` for exact commands, counts, exit codes and artifacts. No result is claimed without recorded output.

## 6. Residual risk

Host-level items (HTTPS termination, queue workers, scheduler, backups, mail/WhatsApp delivery, production migration rehearsal) remain owner-dependent. Docker container tests and `composer audit` are blocked by local infrastructure (no daemon / no network). No 100% completion is claimed.
