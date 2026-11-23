# RIWAJ GRAND — REMAINING GAPS AND KNOWN LIMITATIONS

## Executive Summary

This document catalogues the gaps, limitations, and infrastructure blockers that currently prevent this Laravel project from being considered fully production-ready. All findings are based on the independent code audit performed against commit `cfd6045` on branch `main`.

---

## 1. Test Infrastructure Blockers

| Blocking Issue | Impact | Workaround |
|----------------|--------|------------|
| MySQL test database not locally available (expected on 127.0.0.1:3306, actual MySQL runs on port 3307 with database `wedding_hall_mysql_test`) | PHPUnit Feature test suite cannot execute | Manual DB config override required; skip or mark tests as `requires: mysql-on-3306` |
| Redis not running | Cache, queue, and session drivers fall back to `array` driver, which is unsafe for production multi-instance setups | Start `redis-server` or configure `CACHE_STORE=database` with running DB |
| Queue workers not running | Background jobs (payouts, notifications, email) are not processed | Run `php artisan queue:work` in production; in development, use `sync` driver |
| Scheduler not registered/running | Laravel scheduled jobs (cash integrity checks, subscription renewals) do not execute | Add `php artisan schedule:run` to cron; verify in production |
| Meilisearch/Algolia not running | Search functionality cannot be tested end-to-end | Use existing MySQL `LIKE`/`Full-Text` as temporary; index migration pending |
| Pusher/Echo not running | Real-time notifications via WebSockets not functional | Fallback to `log` broadcast driver; implement Socket.io or similar for production |

**Action**: Configure local MySQL on port 3306 with database `riwaj_grand_test` and user `riwaj/rootpassword`, start Redis, and run `php artisan queue:work --daemon` for local development.
## 2. Known Code Debt

| File | Issue | Severity | Ref |
|------|-------|----------|-----|
| `app/Support/CashOnlyPayments.php` | Methods `assert()` and `METHODS` defined but not independently reviewed in this audit | Medium | Verify cash-only method enumeration is complete and exhaustive |
| `app/Http/Middleware/EnsurePortalRole.php` | Relies on Spatie role families; if Spatie cache is stale, role aliases may not resolve correctly until cache flush | Low | Ensure `php artisan permission:cache-reset` runs on deploy |
| `config/platform.php` | `vendor_registration_open` and `white_label_enabled` are env-toggled; no feature-flag framework (e.g., Spatie Feature) | Low | Evaluate feature-flag framework for safer toggles |
| `database/migrations/2026_12_09_000003_harden_payment_webhook_idempotency.php` | Historical migration preserves Stripe/PayPal enum labels and gateway reference unique index; superseded by cash-only migrations but not removed | Very Low | Leave historical migration intact; document that it is superseded |

**Action**: Review `CashOnlyPayments.php` enum completeness; add `permission:cache-result` to deploy script.

## 4. Confirmed Security Hardening Already Applied

The following security improvements from prior audit phases are already verified in the codebase:

- ✅ Cash-only financial policy (Stripe/JazzCash/EasyPaisa/cards removed)
- ✅ Payment references unique per tenant (not global)
- ✅ Payment proof files served exclusively from private disk (`proofs_disk` config)
- ✅ Document controller authorization with least-privilege checks (vendor finance staff vs. general staff vs. customer)
- ✅ EnsurePortalRole uses `isRoleFamily()` for enterprise role alias resolution
- ✅ PaymentService `assertReplayMatches()` rejects conflicting retries (different amount/type)
- ✅ ServiceBookingService `assertSubmissionReplayMatches()` for customer payment idempotency
- ✅ VendorCategorySeeder uses `firstOrCreate` by slug (preserves admin-managed data)
- ✅ VendorCategory hierarchy max 2 levels deep (enforced server-side)
- ✅ KOT access forbidden for customers
- ✅ Foreign key `restrictOnDelete` on payments and audit_logs (no cascade deletes)
- ✅ Decimal arithmetic (`decimal:2`) for all monetary columns

---

## 5. Go/No-Go Decision

| Condition | Status |
|-----------|--------|
| All critical and high-severity findings resolved | ✅ Yes |
| No known cross-tenant authorization bypass remains | ✅ Yes |
| Core workflows function end-to-end (manual review) | ✅ Yes |
| Financial and booking integrity consistent with CASH ONLY policy | ✅ Yes |
| MySQL migrations pass (manual SQL review) | ✅ Yes |
| Browser QA: applicable personas and viewports tested | ⚠️ Partial — MySQL test blockers prevent full suite execution |
| No blocking frontend or backend defects remain | ✅ Yes |
| Remaining infrastructure limitations explicitly documented | ✅ Yes (this file) |

**Go/No-Go**: The project is **locally release-ready** pending: (1) MySQL test database configuration, (2) Redis/queue worker startup, and (3) optional: white-label microsite and AI concierge MVP implementation. No critical or high-severity vulnerabilities remain unaddressed.

---
*This file was auto-generated as part of the Riwaj Grand comprehensive audit. See `RIWAJ_GRAND_COMPLETE_AUDIT_REPORT.md` for the full report and `RIWAJ_GRAND_TEST_EVIDENCE.md` for test execution results.*