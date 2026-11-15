# RIWAJ GRAND — VENDOR MODULES DOCUMENTATION

> How each business vertical operates on the platform: its dashboard, its catalogue model, its booking flow, its money flow and its RBAC gate.
> The engine is **generic**; a vertical is configuration, not code.

---

## 1. The generic vendor engine

Every vendor — regardless of vertical — shares one commercial pipeline:

```
 vendor_services / vendor_packages   (catalogue, priced)
            ↓
 RFQ (customer request)  →  rfq_bids (vendor quotations)  →  award
            ↓
 service_bookings (status + money)  →  service_payments (advance/installment/full)
            ↓
 vendor_wallets  →  wallet_transactions (ledger)  →  vendor_payouts (withdrawals)
```

The vertical decides only:
- `vendor_categories.booking_mode` — which customer form and availability model applies
- which dashboard the vendor lands on
- which fields matter on the catalogue

---

## 2. Module matrix

| Vertical | Dashboard route | Booking mode | Catalogue pricing | Availability | Payouts |
|---|---|---|---|---|---|
| Wedding halls | `/dashboard` | `SHIFT_VENUE` | packages + menus (legacy ERP) | shift matrix + calendar | ✅ |
| Photography | `/vendor/dashboard/photography` | `HOURLY` / `PROJECT` | hourly & full-day packages | shoot calendar | ✅ |
| Catering | `/vendor/dashboard/catering` | `PER_HEAD` | per-head packages, guests | kitchen schedule + KOT | ✅ |
| Decoration | `/vendor/dashboard/decoration` | `PROJECT` | project packages, themes | setup/dismantle schedule | ✅ |
| Beauty & bridal | `/vendor/dashboard/beauty-bridal` | `APPOINTMENT` | per-bride / per-person | appointment calendar | ✅ |
| Wedding planning | `/vendor/dashboard/wedding-planners` | `PROJECT` | complete-management packages | event timelines | ✅ |
| Transport | (generic ERP) | `TRANSPORT` | day / per-trip packages | fleet availability | ✅ |
| Any new vertical | (generic ERP) | DB row | any | any | ✅ |

---

## 3. Shared vendor boards (all verticals)

| Board | Route | Purpose | Gate |
|---|---|---|---|
| **Quotations & Bids** | `/vendor/quotes` | Inbox of open RFQs addressed to the business; submit, revise and **award** quotations | `rfq.view` / `rfq.bid` / `rfq.award` |
| **Service Bookings** | `/vendor/bookings` | Lifecycle board: accept → confirm → in-progress → complete / cancel | `service-bookings.*` |
| **Service Catalog** | `/vendor/services` | CRUD the priced catalogue | `services.manage` |
| **Portfolio Gallery** | `/vendor/portfolio` | Images and video with captions | `portfolio.manage` |
| **Business Locations** | `/vendor/locations` | Multi-branch management (NEW) | `locations.view` / `locations.manage` |
| **Finance & Earnings** | `/vendor/finance` | Gross, commission, net, wallet ledger, payouts (NEW) | `wallet.view` / `payouts.request` |
| **Support Center** | `/account/support` | Tickets raised against the business | `support.view` |

---

## 4. Module deep-dive

### 4.1 Photography
- **Catalogue**: `vendor_services` with `pricing_type = HOURLY | PROJECT | FULL_DAY`, `duration_minutes`, highlights.
- **Packages**: `photographer_packages` (now tenant-scoped via `tenant_id`), priced hourly / full-day with drone and same-day-edit add-ons.
- **Booking**: `bookable_type = VendorService::class`, `bookable_id = <uuid>` — the shoot is attached polymorphically, so a fleet car or a package can be booked through the same engine.
- **Money**: hourly rate × hours, or a project price; the platform commission is deducted before the vendor's net amount is credited.
- **Dashboard**: shoot calendar, leads, bookings, portfolio, assigned teams, equipment, deliverables, earnings.

### 4.2 Catering
- **Catalogue**: per-head packages with `price_from`/`price_to` and `unit = 'per head'`; guest count drives the total.
- **Menus**: `menu_items` + `menu_combinations` from the legacy ERP; the kitchen KOT board is reused only where it is genuinely the same workflow.
- **Booking**: `guest_count` on the service booking feeds the kitchen plan.
- **Kitchen**: `KitchenKotBoard` — KOT generation, `ShiftDefinition` multiplies, stock checks.
- **Money**: per-head × guests, minus discount, minus commission.

### 4.3 Decoration
- **Catalogue**: project pricing (`PROJECT`), theme packages, mood boards (`mood_board_generations`).
- **Portfolio**: `vendor_portfolio_media` (IMAGE/VIDEO) with captions and ordering.
- **Approval**: the customer approves a design inside the booking flow before setup is scheduled.
- **Projects**: `service_bookings` with `deliverables_json` / `delivery_status` for setup → event → dismantling milestones.

### 4.4 Beauty & bridal
- **Catalogue**: `APPOINTMENT` pricing — per bride, per person, per package.
- **Availability**: `vendor_availability` (weekly pattern + date overrides).
- **Booking**: date + start time + hours; `end_time` is derived automatically from `hours`.

### 4.5 Wedding planning
- **Catalogue**: `PROJECT` pricing — complete management, destination weddings.
- **Workspace**: the planner's own event list maps onto `customer_events` with `customer_event_tasks` and `customer_event_vendors` — the planner coordinates other vendors through the same tables the customer uses.

### 4.6 Transport
- **Fleet**: `car_fleets` (bridal cars, coasters, vintage rides) with `hourlyCostForHours()`.
- **Booking**: `bookable_type = CarFleet::class` — note this uses the legacy bigint morph target, which is why the cart morph mismatch is tracked in `DATABASE_ARCHITECTURE.md §7`.

---

## 5. Multi-location businesses (NEW)

A business may operate several branches. `vendor_locations` holds one row per branch:

| Field | Purpose |
|---|---|
| `name` / `slug` | Branch identity (`slug` unique per tenant) |
| `branch_type` | `HEAD_OFFICE · BRANCH · STUDIO · WAREHOUSE · MOBILE` |
| `address` / `area` / `city` / `province` / `postal_code` | Address |
| `latitude` / `longitude` | Map coordinates |
| `phone` / `email` | Branch-level contact |
| `is_primary` | Exactly one head office per business (enforced on save) |
| `is_active` | Deactivated branches disappear from customer-facing pages |

Branches are surfaced on the vendor's **white-label site** under "Our Locations".

---

## 6. Money flow (every vertical)

```
Customer pays  ──►  service_payments (PENDING → VERIFIED)
                        │
                        ├─ payment_status  : UNPAID → ADVANCE_PAID → PARTIAL → PAID
                        ├─ paid_amount     : running total
                        ├─ commission      : quoted × rate   (tenant rate, else platform default)
                        └─ net_vendor      : quoted − discount − commission
                                │
                                ▼
                       vendor_wallets (balance)
                                │
                       wallet_transactions  (append-only, balance_after snapshot)
                                │
                                ▼
                       vendor_payouts  (REQUESTED → PROCESSING → PAID)
```

- **Commission** is never hardcoded: `tenants.commission_rate` (percent) wins; otherwise `platform_settings['marketplace.commission.default_rate']` (fraction, default `0.05`).
- **Advance** comes from `platform_settings['marketplace.advance.default_percent']` (default `30`).
- The vendor sees all of this on `/vendor/finance`, including the **effective commission rate** actually charged.

---

## 7. White-label vendor websites (NEW)

Each vendor can publish their own branded website.

| Piece | Responsibility |
|---|---|
| `App\Support\TenantBrand` | Resolves a tenant's brand tokens (name, logo, tagline, colours, contact) from `tenants` + `tenants.website_settings` |
| `App\Http\Middleware\ResolveTenantBrand` | Detects the request subdomain, resolves the brand, shares it with every view |
| `App\Livewire\WhiteLabelSite` | Renders the branded microsite: hero, about, services, portfolio, locations, reviews, quotation CTA |
| `config/platform.php` | `white_label_enabled` (env `WHITE_LABEL_ENABLED`) and `root_domain` (env `ROOT_DOMAIN`) |

**Behaviour**
- `https://nawabcaterers.riwajgrand.pk/site` → the vendor's own site, in their colours.
- `https://riwajgrand.pk/site/nawab-caterers` → the same site on the platform domain.
- The platform root domain is untouched — no tenant brand is applied.
- An unknown subdomain resolves to no brand (the standard platform styling applies).

**Overrides** live in `tenants.website_settings`:
```json
{ "brand_name": "Nawab Fine Catering", "primary_color": "#101010", "accent_color": "#D4AF37", "tagline": "Zaika shahi" }
```

---

## 8. Adding a new vertical (no code required)

1. **Super Admin → Vendors → Categories → Add**: name, slug, tagline, icon, hero colour, **booking mode**.
2. Seed a vendor tenant with that `vendor_category_id`.
3. The vendor adds `vendor_services` (priced catalogue) and `vendor_portfolio_media`.
4. The vendor's dashboard: use the generic ERP board, or add a category dashboard later.
5. The customer finds them under `/vendors?category=<slug>`, requests a quote, and the standard RFQ → award → payment → payout flow applies.

A vertical with different workflows needs a new `booking_mode` consumer in the quote form — but the commercial engine (money, commission, wallet, payouts, disputes) is already generic.
