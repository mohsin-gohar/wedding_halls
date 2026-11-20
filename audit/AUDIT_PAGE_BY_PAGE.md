> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# Comprehensive Page-by-Page & UI Section Audit Matrix

> **Document Version:** 1.0.0-GA  
> **Total Discovered Web Routes:** 65  
> **Design Tokens:** Obsidian Base (`#171513`), Champagne Gold (`#C6A15B`), Warm Off-White (`#F8F5EF`), Dark Surface (`#0E0D0C`)

---

## 1. Public Marketplace & Visitor Pages

### Page: Homepage (`/`)
- **Route Name:** `home`
- **Controller/Component:** View Closure (`routes/web.php:47`) -> `welcome.blade.php`
- **Layout:** `components.layouts.public`
- **Section-by-Section Audit:**
  - **Hero Section:** Opulent cinematic hero with dual CTAs (*Explore Venues*, *List Your Venue*), background video/imagery, verified stats counter. Functioning properly.
  - **Quick Venue Finder Form:** Inline city selector, guest counter, shift selector. Directs to `/venues` with query parameters. Functioning properly.
  - **Featured Ballrooms Matrix:** Grid displaying top 6 approved halls with capacity, rent, and city badges. **Warning:** Missing eager loading on `$hall->venue` causes N+1 queries (`PERF-01`).
  - **Catering & Bawarchi Showcase:** Mughal feasts & packages preview. Responsive grid. Functioning properly.
  - **Why EventHub Trust Bar:** Zero double-booking guarantee, verified marquees, legal contracts. High trust signals.
  - **Footer:** Full SECP disclaimer, currency in PKR, legal links. Functioning properly.
- **Responsive & Accessibility:** Verified mobile drawer, clean typography hierarchy, dark/light theme toggle.

---

### Page: Venues Directory (`/venues`)
- **Route Name:** `public.venues`
- **Controller/Component:** `App\Livewire\PublicVenues`
- **Layout:** `components.layouts.public`
- **Section-by-Section Audit:**
  - **Filter Bar:** Multi-attribute filtering by City, Search, Date, Shift, Min Capacity, Max Budget, AC, Generator, Bridal Room.
  - **30-Day Shift Heatmap Matrix:** Visual calendar showing shift load over the upcoming month. **Defect:** Case-sensitivity bug (`UI-01`) causes all counts to display 0.
  - **Date & Shift Filter:** Overlap query does not account for `FULL_DAY` reservations when filtering single shifts (`FUNC-02`).
  - **Venues Grid:** 9-per-page pagination with Livewire `WithPagination`, luxury card styling, hover zoom on images.
  - **Empty State:** Clean empty state illustration when search matches no venues with reset filters button.

---

### Page: Venue Details & Showcase (`/venues/{slug}` and `/hall/{slug}`)
- **Route Name:** `public.venue.details` / `hall.showcase`
- **Controller/Component:** `App\Livewire\PublicHallDetails`
- **Layout:** `components.layouts.public`
- **Section-by-Section Audit:**
  - **Hero & Media Gallery:** Interactive photo gallery with active thumbnail switcher, embedded video tours, capacity pill tags.
  - **Pricing & Rent Summary:** Transparent base rent in PKR, per-head catering packages, amenities icons (AC, Generator, Bridal room).
  - **Add-on Services Catalog:** Dynamic list of decor, sound, lighting, photography add-ons.
  - **Interactive 3-Month Booking Calendar:** Displays morning/afternoon/evening availability status with luxury badges.
  - **Direct Inquiry / Walk-in Booking Action:** Triggers global `WalkInBookingModal`.
  - **Portability Note:** Dual slug/UUID resolution crashes on PostgreSQL if slug passed to UUID column (`DATA-03`).

---

### Page: Public Marketplace (`/marketplace`)
- **Route Name:** `marketplace`
- **Controller/Component:** `App\Livewire\PublicMarketplace`
- **Layout:** `components.layouts.public`
- **Section-by-Section Audit:**
  - Dedicated marketplace view with city filters, capacity sliders, and paginated hall cards.
  - Proper scoping to approved and active tenants.

---

### Page: Packages & Menus (`/packages` and `/menus`)
- **Route Name:** `public.packages` / `public.menus`
- **Controller/Component:** `App\Livewire\PublicMenus`
- **Layout:** `components.layouts.public`
- **Section-by-Section Audit:**
  - Tabbed interface switching between *Wedding Packages* and *A-la-Carte Dishes*.
  - Package cards display per-head price in PKR, minimum guest count, and full dish breakdowns.
  - Filter by tenant packages or platform defaults.

---

### Page: Live Shift Availability Checker (`/check-availability`)
- **Route Name:** `public.availability`
- **Controller/Component:** `App\Livewire\PublicAvailabilityChecker`
- **Layout:** `components.layouts.public`
- **Section-by-Section Audit:**
  - Date picker with quick shortcut buttons (*Tomorrow*, *This Friday*, *This Saturday*, *This Sunday*, *Next Saturday*).
  - Availability grid indicating slot status (`AFTERNOON`, `EVENING`, `NIGHT`).
  - **Scalability Note:** Queries all halls in database without pagination (`PERF-01`).

---

### Page: Public Guest RSVP Portal (`/rsvp`)
- **Route Name:** `public.rsvp`
- **Controller/Component:** `App\Livewire\PublicGuestRsvp`
- **Layout:** `components.layouts.public`
- **Section-by-Section Audit:**
  - Phone search field allowing wedding guests to confirm attendance and guest headcount.
  - **Security Defect:** Loose `LIKE '%...%'` search allows unauthenticated enumeration with a single digit (`SEC-02`).
  - Response submission updates attendance count and notes properly once valid RSVP is identified.

---

### Page: Contact & Concierge (`/contact`)
- **Route Name:** `public.contact`
- **Controller/Component:** `App\Livewire\PublicContact`
- **Layout:** `components.layouts.public`
- **Section-by-Section Audit:**
  - Clean form collecting Name, Email, Phone, City, Event Date, and Message.
  - **Defect:** Form is validated, but message is never saved to database or emailed (`FUNC-01`).

---

### Page: About, FAQs & Policies (`/about`, `/faqs`, `/policies/{policy?}`)
- **Route Names:** `public.about`, `public.faqs`, `public.policies.*`
- **Controllers/Components:** `PublicAbout`, `PublicFaqs`, `PublicPolicies`
- **Layout:** `components.layouts.public`
- **Section-by-Section Audit:**
  - High aesthetic consistency, readable typography, legal contract disclaimers, cancellation rules, privacy policy.
  - Tabbed policy navigator (`terms`, `privacy`, `cancellation`, `refund`). All anchor links functioning.

---

## 2. Authentication & Guest Entry Points

### Page: Vendor Sign In (`/login`)
- **Route Name:** `login`
- **Component:** `App\Livewire\Auth\Login`
- **Layout:** `components.layouts.auth`
- **Section-by-Section Audit:**
  - Clean focused card, dark luxury theme, back to home arrow, password visibility toggle.
  - Rate limited (5 attempts per minute). Session regenerated upon login.
  - Smart role routing: Super Admin -> `/admin`, Hall Owner -> `/dashboard`, Customer -> `/customer/dashboard`.

---

### Page: Customer Sign In (`/customer/login`)
- **Route Name:** `customer.login`
- **Component:** `App\Livewire\CustomerLogin`
- **Layout:** `components.layouts.auth`
- **Section-by-Section Audit:**
  - Dedicated customer portal entrance. Clean branding. Rate limited (5 attempts per minute).
  - Suspended account gate. Redirects to `/customer/dashboard`.

---

### Page: Vendor Registration (`/register`)
- **Route Name:** `register`
- **Component:** `App\Livewire\Auth\Register`
- **Layout:** `components.layouts.auth`
- **Section-by-Section Audit:**
  - Atomic DB transaction creates Tenant, Venue, Hall, and User.
  - Password confirmation validation.
  - **Defect:** Does not check `platform_vendor_registration_open` master toggle (`FUNC-03`).

---

### Page: Customer Registration (`/customer/register`)
- **Route Name:** `customer.register`
- **Component:** `App\Livewire\CustomerRegister`
- **Layout:** `components.layouts.auth`
- **Section-by-Section Audit:**
  - Creates user with `ROLE_CUSTOMER`, auto-logins, redirects to customer dashboard.

---

## 3. Customer Portal

### Page: Customer Booking Desk (`/customer/dashboard` & `/customer/bookings`)
- **Route Names:** `customer.dashboard`, `customer.bookings`
- **Component:** `App\Livewire\CustomerDashboard`
- **Layout:** `components.layouts.public`
- **Section-by-Section Audit:**
  - Summary metrics: Active bookings, total spent (PKR), outstanding balance.
  - Booking list with status badges (`CONFIRMED`, `HOLD`, `INQUIRY`, `COMPLETED`, `CANCELLED`).
  - Booking details modal with printable agreement and payment receipts links.
  - Request cancellation inquiry action.
  - Strictly scoped to `customer_id === auth()->id() || customer_email === auth()->user()->email`. High security compliance.

---

## 4. Vendor ERP Management Suite (`/dashboard`, etc.)

| Page Route | Component | Role Gate in `mount()` | Tenant Isolation | Audit Status |
|---|---|---|---|---|
| `/dashboard` | `Dashboard` | Redirects customers | Scoped to `$user->tenant_id` | **PASS** |
| `/calendar` | `CalendarShiftGrid` | **Missing** (`SEC-01`) | Conditional `$user->tenant_id` | **FIX REQUIRED** |
| `/hall/media` | `HallMediaManager` | **Missing** (`SEC-01`) | Creates orphan if tenant null | **FIX REQUIRED** |
| `/expenses` | `ExpenseTracker` | Checked (`ROLE_OWNER`, etc.) | Strictly scoped | **PASS** |
| `/staff` | `StaffManager` | Checked (`ROLE_OWNER`, etc.) | Strictly scoped + Plan limits | **PASS** |
| `/staff/attendance` | `StaffAttendance` | Checked (`ROLE_OWNER`, etc.) | Strictly scoped | **PASS** |
| `/staff/payroll` | `StaffPayroll` | Checked (`ROLE_OWNER`, etc.) | Strictly scoped | **PASS** |
| `/inventory` | `InventoryTracker` | Checked (`ROLE_OWNER`, etc.) | Strictly scoped | **PASS** |
| `/catering-packages` | `PackageManager` | Checked (`ROLE_OWNER`, etc.) | Strictly scoped | **PASS** |
| `/kitchen-kots` | `KitchenKotBoard` | **Missing** (`SEC-01`) | Unconstrained if tenant null | **FIX REQUIRED** |
| `/utility-fuel` | `UtilityFuelTracker` | **Missing** (`SEC-01`) | Unconstrained if tenant null | **FIX REQUIRED** |
| `/seating-planner` | `FloorPlanDesigner` | **Missing** (`SEC-01`) | Can edit other tenants' plans | **FIX REQUIRED** |
| `/whatsapp-rsvps` | `WhatsAppRsvpManager` | **Missing** (`SEC-01`) | Unconstrained if tenant null | **FIX REQUIRED** |

---

## 5. Super Admin Console (`/admin`)

### Page: Super Admin Dashboard (`/admin` & `/admin/dashboard`)
- **Route Name:** `admin.dashboard`
- **Component:** `App\Livewire\AdminDashboard`
- **Layout:** `components.layouts.app`
- **Authorization:** `Auth::user()->isSuperAdmin() ? pass : abort(403)` (Strictly enforced)
- **Tabs Audited:**
  1. `vendors`: Provision new halls, manage vendor tiers, approve pending vendors, suspend accounts. Functioning properly.
  2. `tasks`: Platform administrative to-do list (onboarding follow-ups, payment checks, maintenance). Functioning properly.
  3. `traffic`: Live visitor sessions (15-min window), page views, system gates (marketplace on/off, registration on/off), announcement broadcaster. Functioning properly.
  4. `plans`: CRUD management of SaaS subscription plans (pricing, limits, feature flags). Functioning properly.
  5. `payments`: Review and approve/reject subscription payments with manual or online receipts. Functioning properly.
  6. `halls`: System-wide hall directory, edit capacities, base rents, and active states. Functioning properly.
  7. `bookings`: Global booking audit table across all tenants with filter by status. Functioning properly.
  8. `audit`: System audit logs capturing sensitive operations with timestamps and actor IDs. Functioning properly.

---

## 6. Official Printable & Downloadable Documents (`/documents/*`)

| Endpoint | Controller Method | PDF Download Mode | Authorization | Audit Status |
|---|---|---|---|---|
| `/documents/agreement/{id}` | `bookingAgreement` | `?pdf=1` (A4 Portrait) | Tenant Owner, Super Admin, Customer Owner | **PASS** |
| `/documents/receipt/{id}` | `paymentReceipt` | `?pdf=1` (80mm Thermal) | Tenant Owner, Super Admin, Customer Owner | **PASS** |
| `/documents/kot/{id}` | `kitchenKot` | `?pdf=1` (A4 Portrait) | Operational Staff, Chefs, Super Admin (Customer 403) | **PASS** |
| `/documents/quotation/{id}` | `bookingQuotation` | `?pdf=1` (A4 Portrait) | Tenant Owner, Super Admin, Customer Owner | **PASS** |
| `/documents/invoice/{id}` | `taxInvoice` | `?pdf=1` (A4 Portrait) | Tenant Owner, Super Admin, Customer Owner | **PASS** |
| `/documents/salary-slip/{id}` | `salarySlip` | `?pdf=1` (A4 Portrait) | Super Admin, Tenant Owner, Employee Self | **PASS** |
