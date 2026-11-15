# RIWAJ GRAND — DATABASE ARCHITECTURE

> Live schema verified with `php artisan db:table` / `tinker`. 49 migrations, 75 tables.
> Conventions: PKs are UUIDs for marketplace objects and BIGINTs for the legacy hall ERP; all money is `decimal(12,2)` PKR; timestamps are `Asia/Karachi`.

---

## 1. Entity map

```
                       ┌──────────────────┐
                       │  vendor_categories│  ← DB-driven verticals (9 seeded)
                       └────────┬─────────┘
                                │ vendor_category_id
        ┌───────────────────────┼───────────────────────────────┐
        │                       │                               │
┌───────▼────────┐    ┌─────────▼─────────┐          ┌──────────▼──────────┐
│    tenants     │    │ vendor_services   │          │  vendor_packages   │
│ (the business) │    │ vendor_packages   │          │  portfolio_media   │
│  + 44 cols     │    │ vendor_documents  │          │  vendor_availability│
│  + marketplace │    │ vendor_locations  │          └──────────┬──────────┘
└───┬────┬────┬──┘    └─────────┬─────────┘                     │
    │    │    │                  │                               │
    │    │    │         ┌────────▼────────┐                      │
    │    │    │         │  service_        │◄──── morph ────────┘
    │    │    │         │  bookings        │  (bookable_type/id)
    │    │    │         │  (polymorphic)   │
    │    │    │         └───┬────────┬─────┘
    │    │    │             │        │
    │    │    │   ┌─────────▼──┐  ┌──▼───────────────┐
    │    │    │   │service_    │  │  rfq_bids        │
    │    │    │   │payments    │  └────────┬─────────┘
    │    │    │   └────────────┘           │
    │    │    │                    ┌────────▼─────────┐
    │    │    │                    │  rfqs            │
    │    │    │                    │  rfq_targets     │
    │    │    │                    └──────────────────┘
    │    │    │
    │    │    ├──► users (staff, tenant_id)      ── spatie: roles/permissions
    │    │    ├──► venues ─► halls ─► bookings (LEGACY hall ERP, untouched)
    │    │    ├──► vendor_wallets ─► wallet_transactions (immutable ledger)
    │    │    ├──► vendor_payouts
    │    │    ├──► support_tickets ─► ticket_messages
    │    │    └──► customer_events ─┬─► customer_event_tasks
    │    │                         └─► customer_event_vendors
    │    └──► subscription_plans / subscription_payments
    └──► audit_logs, platform_settings, platform_tasks
```

---

## 2. The two booking engines (both preserved)

| Engine | Table | Used by | Concurrency |
|---|---|---|---|
| **Legacy hall ERP** | `bookings` | Venue owners, shift slots (AFTERNOON / EVENING / NIGHT / FULL_DAY) | Cache lock + `lockForUpdate` + unique-violation handling in `BookingService` |
| **Service engine** | `service_bookings` | Every non-hall vertical (photography, catering, decoration, beauty, planning, transport) | Polymorphic `bookable_type`/`bookable_id`; row-locked award in `ServiceBookingService` |

The legacy `bookings` table was **not** migrated, renamed or re-keyed. The service engine sits beside it and is selected by `vendor_category.booking_mode`.

---

## 3. Marketplace schema

### `vendor_categories` (13 cols)
`id · name · slug(uniq) · tagline · icon · hero_color · booking_mode · features(json) · description · is_active · sort · timestamps`

`booking_mode` drives the customer-facing form and the vendor workflow:
`SHIFT_VENUE · DATE_EVENT · HOURLY · APPOINTMENT · PER_HEAD · PROJECT · CONSULTATION · TRANSPORT`

**Categories are rows, not code** — adding a vertical requires no deployment.

### `tenants` — marketplace extension (44 cols)
Existing core + `vendor_category_id · about · cover_image · service_areas(json) · rating_avg · rating_count · min_budget · max_budget · is_featured · featured_until · verified_at · onboarding_completed_at · onboarding_step · marketplace_visible · profile_views`
Index: `tenants_marketplace_idx (vendor_category_id, marketplace_visible, city)`

### `vendor_locations` — multi-location (NEW, 21 cols)
`id · tenant_id(FK) · name · slug · branch_type · address · area · city · province · postal_code · latitude · longitude · phone · email · service_areas(json) · is_primary · is_active · notes · timestamps`
Unique `(tenant_id, slug)` · Index `(tenant_id, is_active)`

### `vendor_services` (15) · `vendor_packages` (15) · `vendor_portfolio_media` (10) · `vendor_documents` (11) · `vendor_availability` (10)
All UUID-keyed, all `tenant_id`-scoped with cascade delete, all indexed on `(tenant_id, is_active)`.

---

## 4. Transaction schema

### `service_bookings` (34 cols)
| Group | Columns |
|---|---|
| Ownership | `tenant_id` · `vendor_category_id` · `customer_id` · `rfq_bid_id` · `customer_event_id` |
| Polymorphic target | `bookable_type` · `bookable_id` (uuid) |
| Identity | `booking_reference` (unique, `RG-SB-XXXXXX`) |
| Event | `event_name` · `event_date` · `start_time` · `end_time` · `hours` · `guest_count` · `requirements` · `requirements_json` |
| Money | `quoted_amount` · `discount_amount` · `advance_amount` · `paid_amount` · `commission_amount` · `net_vendor_amount` |
| State | `status` · `payment_status` · `deliverables_json` · `delivery_status` · `cancellation_reason` · `cancellation_policy` |
| Timestamps | `confirmed_at` · `completed_at` · `cancelled_at` |

Indexes: `(tenant_id,status)` `(customer_id,status)` `(event_date,status)` `(status)` `(payment_status)` `(bookable_type,bookable_id)` + unique `booking_reference`.

**Statuses:** `PENDING_VENDOR · QUOTE_SENT · ACCEPTED · CONFIRMED · IN_PROGRESS · COMPLETED · CANCELLED · DECLINED`
**Payment statuses:** `UNPAID · ADVANCE_PAID · PARTIAL · PAID · REFUNDED`

### `service_payments` (18)
`amount · method(JAZZCASH|EASYPAISA|BANK_TRANSFER|CARD|CASH|WALLET) · gateway · gateway_ref · reference · proof_path · status(PENDING|VERIFIED|REJECTED|REFUNDED) · kind(ADVANCE|INSTALLMENT|FULL|REFUND) · verified_by · verified_at · notes · paid_at`

### `vendor_wallets` (10) · `wallet_transactions` (13) · `vendor_payouts` (12)
- The wallet ledger is **append-only**: every row carries `balance_after`, so the balance can always be reconstructed.
- `wallet_transactions.source ∈ {BOOKING_PAYMENT, COMMISSION, PAYOUT, REFUND, ADJUSTMENT, SUBSCRIPTION}`
- `vendor_payouts.status ∈ {REQUESTED, PROCESSING, PAID, REJECTED}`

### `api_tokens` (NEW, 13)
`user_id · name · token_hash(uniq, sha256) · prefix · abilities(json) · last_used_at · expires_at · revoked_at · last_used_ip`
**Only the hash is stored.** The plain token is returned once at creation.

---

## 5. Workspace & trust schema

| Table | Purpose |
|---|---|
| `customer_events` | One wedding/function per row: name, type, date, city, venue, guests, budget, status |
| `customer_event_tasks` | Checklist items with due dates and `PENDING/IN_PROGRESS/DONE` |
| `customer_event_vendors` | Shortlist linking an event to a vendor + optional service booking |
| `support_tickets` | `ticket_no` (unique) · user · tenant · subject · category(BOOKING/PAYMENT/DISPUTE/VERIFICATION/GENERAL) · priority · status · assigned_to · resolved_at |
| `ticket_messages` | Thread replies with `is_staff_reply` flag and optional attachment |
| `reviews` | Now polymorphic: `reviewable_type`/`reviewable_id`, `hall_id` relaxed to nullable, `is_verified_booking`, `vendor_reply`, moderation columns |
| `platform_settings` | Key/value JSON store — commission default, advance percent, and every future platform knob |

---

## 6. Migrations added in this cycle

| File | Effect |
|---|---|
| `2026_12_05_000001_create_api_tokens_table.php` | New `api_tokens` table (hash-only bearer tokens) |
| `2026_12_06_000001_create_vendor_locations_and_harden_references.php` | New `vendor_locations`; adds `photographer_packages.tenant_id`; relaxes `reviews.hall_id` to nullable |

Both are **additive and backward compatible** — no column was dropped, renamed or retyped, and no existing row was modified.

---

## 7. Known schema inconsistencies (documented, not hidden)

| Issue | Impact | Plan |
|---|---|---|
| `service_bookings.bookable_id` is uuid while `cart_items.bookable_id` is bigint | The two morph targets cannot point at the same model | Align `cart_items` to uuid in a future migration (cart tables are not yet routed) |
| `reviews.hall_id` cascades on hall delete | Deleting a hall removes its reviews | Switch to `nullOnDelete` when the hall-deletion flow is redesigned |
| `menu_combinations` has no model | Orphan table from a 2026-11-20 migration | Remove or model in a cleanup migration |
| 3 migrations are fully skipped by `hasTable` guards | Their intended columns never existed | Their features were re-implemented in the 2026-12-01 ecosystem migration |

---

## 8. Production notes

- **MySQL/PostgreSQL:** all migrations use portable Laravel schema builders; the `uuid` PKs and `json` columns are supported on both. `decimal(12,2)` avoids float drift.
- **Indexes:** every tenant-scoped table carries a `(tenant_id, …)` index, and `service_bookings` carries five composite indexes covering the four hot query paths.
- **Soft deletes:** `users` and `reviews` use `SoftDeletes`; audit trails survive account removal.
- **Timezone:** `Asia/Karachi` in `config/app.php`; PKR in `tenants.currency`.
