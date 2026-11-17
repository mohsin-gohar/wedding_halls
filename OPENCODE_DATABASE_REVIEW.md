# OPENCODE DATABASE REVIEW — Riwaj Grand

**Date:** 2026-11-16
**Branch:** `enterprise-hardening`
**Scope:** Migration/relationship/integrity review of the Laravel database layer.

---

## 1. SCHEMA INVENTORY (migrations added during the enterprise transformation)

The database evolved through 30+ dated migrations. Key new areas added in late 2026:

| Area | Migration(s) | Status |
|---|---|---|
| Permission tables | `2026_10_09_135656_create_permission_tables.php` | ✅ |
| SaaS tenant/enhancement, subscription & ERP tables | `2026_10_10_000003_create_saas_subscription_and_erp_tables.php` | ✅ |
| ERP modules + automation | `2026_10_11_000001_create_erp_modules_and_automation_tables.php` | ✅ |
| Performance indexes (core) | `2026_10_24_000001_add_performance_indexes_to_core_tables.php` | ✅ |
| Reviews | `2026_10_24_000002_create_reviews.php` | ✅ |
| Hall chat messages | `2026_10_29_000001_create_hall_chat_messages_table.php` | ✅ |
| Notifications | `2026_11_06_000001_create_notifications_table.php` | ✅ |
| Subscription plans | `2026_11_20_000001_create_subscription_plans.php` | ✅ |
| Vendor wallets, shifts, unified cart, catering menus, photo fleets, RFQ dispatch | `2026_11_20_*` … `2026_12_01_000009_create_rfq_contract_dispatch.php` | ✅ |
| Vendor ecosystem (portfolios, services, packages, RFs) | `2026_12_01_000001_create_vendor_ecosystem_tables.php` | ✅ |
| Service booking + finance (payments, wallets, payouts, refunds, invoices) | `2026_12_01_000002_create_service_booking_and_finance_tables.php` | ✅ |
| Workspace support / review tables | `2026_12_01_000003_create_workspace_support_review_tables.php` | ✅ |
| API tokens | `2026_12_05_000001_create_api_tokens_table.php` | ✅ |
| Vendor locations + hardening | `2026_12_06_000001_create_vendor_locations_and_harden_references.php` | ✅ |
| Two-factor columns | `2026_12_07_000001_add_two_factor_columns_to_users_table.php` | ✅ |

---

## 2. MONEY COLUMNS

- All financial columns use **decimal(12,2)** with explicit PKR currency context.
- `ServiceBookingService` derives commission from `tenants.commission_rate` → `platform_settings.marketplace.commission.default_rate` (default 0.05) and advance from `platform_settings.marketplace.advance.default_percent` (default 30).
- Money math applied via `round(..., 2)` — floats are cast to decimal before persistence (documented accepted pattern in tests).

---

## 3. KEY RELATIONSHIPS REVIEWED

- Polymorphic bookings: `service_bookings.bookable_type` / `bookable_id` → polymorphic to `halls`, `vendor_services` etc. Legacy `bookings` table untouched.
- Wallet ledger: `vendor_wallets` + `wallet_transactions` with immutable transaction history.
- Payouts: `vendor_payouts` bounded by available wallet balance (validated in service layer + tests).
- Refunds: `service_payments` rows with `METHOD=REFUND`, status `REFUNDED`, wallet reversal.

---

## 4. DEFECTS FOUND & FIXED THIS CYCLE (database-adjacent)

| # | Defect | Area | Fix |
|---|---|---|---|
| 1 | Two-factor migration missing entirely | Auth | Added `2026_12_07_000001_add_two_factor_columns_to_users_table.php` + model casts (`encrypted:secret`, `array:recovery_codes`) |
| 2 | `bids()` / `targets()` derived `r_f_q_id` (typo) | RFQ | Explicit foreign keys |
| 3 | `vendor_locations` references hardening | Vendor ERP | Migration hardening references |
| 4 | `utility_logs.booking_id` made nullable (utility module has no booking) | Utility logs | `2026_10_17_003500_make_booking_id_nullable_in_utility_logs_table.php` |

---

## 5. VERIFICATION

- Migration state: all migrations ran cleanly in the isolated SQLite test database (`RefreshDatabase`).
- No destructive operations were run against real user data (no `migrate:fresh` on production data).

---

*Next: `OPENCODE_BROWSER_QA_REPORT.md`.*
