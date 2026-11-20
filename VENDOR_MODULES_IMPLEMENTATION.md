> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# Riwaj Grand — Vendor Modules Implementation

> Comprehensive specification for all vendor category modules in the multi-vendor wedding & event marketplace.

## 1. Wedding Venues & Accommodation (EXISTING — Preserved)

**Supported Types:**
- Wedding Halls
- Marriage Lawns
- Marquees
- Banquet Halls
- Farmhouses
- Hotels & Resorts
- Guest Houses
- Destination Wedding Venues
- Conference & Corporate Event Venues

**Existing Capabilities:**
- Tenant manages venues, locations, facilities, capacity, pricing, packages
- Availability calendar with single-host shift exclusivity
- Photos, videos, policies, bookings
- Kitchen KOT module (configurable via feature flag)
- Inventory, staff, payroll, expenses, floor plans
- Customer wishlist and reviews
- Walk-in booking modal with multi-step wizard

**Models:** `Tenant`, `Venue`, `Hall`, `Booking`, `Package`, `MenuItem`, `KitchenKot`, `InventoryItem`, `StaffAttendance`, `StaffPayroll`, `Expense`, `FloorPlan`, `GuestRsvp`, `HallChatMessage`, `HallView`, `HallWishlist`

---

## 2. Photography & Media Services

**Supported Types:**
- Wedding Photographers
- Wedding Videographers
- Cinematographers
- Drone Photography
- Pre-Wedding Shoots
- Bridal Photography
- Wedding Studios
- Album Designers
- Photo Editing Services
- Live Streaming Services
- Event Coverage Teams

**Required Functionality:**
- Photographer profiles and portfolios ✅ (`VendorPortfolioMedia`)
- Image and video galleries ✅ (`VendorPortfolioMedia`)
- Service packages and pricing ✅ (`VendorService`)
- Hourly and event-based packages ✅ (pricing_type: HOURLY, DAY)
- Shoot availability calendar ✅ (`VendorAvailability`)
- Booking requests and quotations ✅ (`ServiceBooking`, `RFQBid`)
- Advance payments and installments ✅ (`ServicePayment`)
- Event schedule and shoot details ✅ (`ServiceBooking` event fields)
- Team and equipment management ✅ (`User` staff + `CarFleet`)
- Contract and deliverables tracking ✅ (`deliverables_json`, `delivery_status`)
- Photo/video delivery status ✅ (`delivery_status`)
- Customer feedback and reviews ✅ (`Review` morphMany on Tenant)
- Vendor earnings and commission reports ✅ (`ServiceBookingService`, `VendorWallet`)

**Models:** `VendorService`, `VendorPortfolioMedia`, `ServiceBooking`, `ServicePayment`, `VendorAvailability`, `RFQBid`, `CarFleet`, `Review`, `WalletTransaction`, `VendorPayout`

**Dashboard:** `PhotographerDashboard` (Livewire) + `vendor-photographer-dashboard.blade.php`

**Booking Mode:** `HOURLY` / `DATE_EVENT`

---

## 3. Catering & Food Services

**Supported Types:**
- Wedding Caterers
- Corporate Event Caterers
- Private Chefs
- BBQ Services
- Live Food Stations
- Dessert Vendors
- Wedding Cake Businesses
- Bakeries
- Beverage Services
- Food Decoration Services

**Required Functionality:**
- Menu management ✅ (`MenuItem`, `VendorService`)
- Per-head pricing ✅ (`VendorService` pricing_type: PER_HEAD, price_from/to)
- Custom menus ✅ (`Booking.custom_menu_json`, `requirements_json`)
- Food packages ✅ (`Package`, `VendorPackage`)
- Guest count estimation ✅ (`ServiceBooking.guest_count`)
- Dietary preferences and special requirements ✅ (`ServiceBooking.requirements`)
- Food tasting requests ✅ (`RFQ` requirements)
- Kitchen preparation schedules ✅ (`KitchenKot` — reused from hall ERP)
- Ingredient and stock management ✅ (`InventoryItem`, `InventoryLog`)
- Catering quotations ✅ (`ServiceBooking`, `RFQBid`)
- Event delivery and service tracking ✅ (`ServiceBooking` status lifecycle)
- Catering staff assignment ✅ (`User` staff with `ROLE_CHEF`, `ROLE_FLOOR_STAFF`)
- Food cost and profit reports ✅ (`Expense`, `ServiceBookingService::getVendorBookingsStats`)

**Models:** `VendorService`, `MenuItem`, `Package`, `VendorPackage`, `ServiceBooking`, `ServicePayment`, `KitchenKot`, `InventoryItem`, `InventoryLog`, `User` (chef/staff), `Expense`, `Review`

**Dashboard:** `CatererDashboard` (Livewire) + `vendor-caterer-dashboard.blade.php`

**Booking Mode:** `PER_HEAD` / `DATE_EVENT`

---

## 4. Decoration & Event Production

**Supported Types:**
- Wedding Decorators
- Stage Designers
- Floral Designers
- Lighting Services
- Sound Systems
- LED Screens
- Furniture Rentals
- Event Equipment Rentals
- Tent and Marquee Decoration
- Theme Designers
- Fireworks and Special Effects (where legally permitted)

**Required Functionality:**
- Decoration portfolios ✅ (`VendorPortfolioMedia`)
- Theme and design galleries ✅ (`VendorPortfolioMedia`)
- Custom decoration packages ✅ (`VendorPackage`, `VendorService` pricing_type: PROJECT)
- Mood boards and inspiration collections ✅ (`AIMoodBoardService`, `MoodBoardGeneration`)
- Customer design approval ✅ (`ServiceBooking` status: QUOTE_SENT → ACCEPTED)
- Material and inventory management ✅ (`InventoryItem`)
- Team scheduling ✅ (`User` staff, `VendorAvailability`)
- Setup and dismantling schedules ✅ (`ServiceBooking` start_time/end_time)
- Quotation generation ✅ (`RFQBid`, `ServiceBooking`)
- Event task tracking ✅ (`CustomerEventTask`)
- Project cost and profitability reports ✅ (`ServiceBookingService`, `Expense`)

**Models:** `VendorService`, `VendorPortfolioMedia`, `VendorPackage`, `ServiceBooking`, `ServicePayment`, `InventoryItem`, `VendorAvailability`, `CustomerEventTask`, `AIMoodBoard`, `MoodBoardGeneration`, `Expense`, `Review`

**Dashboard:** `DecoratorDashboard` (Livewire) + `vendor-decorator-dashboard.blade.php`

**Booking Mode:** `PROJECT` / `CONSULTATION`

---

## 5. Beauty, Fashion & Bridal Services

**Supported Types:**
- Makeup Artists
- Bridal Salons
- Beauty Salons
- Mehndi Artists
- Bridal Designers
- Wedding Dress Boutiques
- Jewellery Businesses
- Bridal Accessories
- Groom Styling Services
- Hair Stylists

**Required Functionality:**
- Service catalogs ✅ (`VendorService`)
- Packages and pricing ✅ (`VendorService` pricing_type: APPOINTMENT)
- Portfolios and galleries ✅ (`VendorPortfolioMedia`)
- Appointment scheduling ✅ (`VendorAvailability`, `ServiceBooking` APPOINTMENT mode)
- Staff calendars ✅ (`User` staff, `VendorAvailability`)
- Booking deposits ✅ (`ServicePayment` ADVANCE kind)
- Customer preferences ✅ (`ServiceBooking.requirements_json`)
- Appointment reminders ✅ (`Notification`)
- Order and delivery tracking where relevant ✅ (`ServicePayment`, `deliverables_json`)

**Models:** `VendorService`, `VendorPortfolioMedia`, `ServiceBooking`, `ServicePayment`, `VendorAvailability`, `User` (staff), `Notification`, `Review`

**Dashboard:** `BeautyBridalDashboard` (Livewire) + `vendor-beauty-dashboard.blade.php`

**Booking Mode:** `APPOINTMENT`

---

## 6. Wedding Planning & Event Management

**Supported Types:**
- Wedding Planners
- Event Management Companies
- Corporate Event Planners
- Wedding Coordinators
- Destination Wedding Planners

**Required Functionality:**
- Event planning workspace ✅ (`CustomerEvent`, `CustomerEventTask`, `CustomerEventVendor`)
- Event budgets ✅ (`ServiceBooking.quoted_amount`, `CustomerEvent`)
- Vendor coordination ✅ (`CustomerEventVendor`, `RFQBid`)
- Task management ✅ (`CustomerEventTask`)
- Milestones and deadlines ✅ (`CustomerEventTask` due dates)
- Event timelines ✅ (`ServiceBooking` event_date, start_time/end_time)
- Guest management ✅ (`GuestRsvp`, `CustomerEvent`)
- Vendor quotations ✅ (`RFQBid`, `ServiceBooking`)
- Contracts and approvals ✅ (`DigitalContract`, `DocumentController`)
- Expense tracking ✅ (`Expense`, `PurchaseOrder`)
- Event progress reports ✅ (`ServiceBookingService::getVendorBookingsStats`)

**Models:** `VendorService`, `ServiceBooking`, `ServicePayment`, `CustomerEvent`, `CustomerEventTask`, `CustomerEventVendor`, `RFQ`, `RFQBid`, `DigitalContract`, `Expense`, `PurchaseOrder`, `Review`

**Dashboard:** `WeddingPlannerDashboard` (Livewire) + `vendor-planner-dashboard.blade.php`

**Booking Mode:** `CONSULTATION` / `PROJECT`

---

## 7. Entertainment, Transport & Other Services

**Supported Types:**
- DJs, Singers, Live Bands, Dhol Players, Performers
- Wedding Cars, Luxury Transport
- Bus and Coaster Rentals
- Valet Parking
- Security Services
- Event Cleaning Services
- Invitation Card Designers
- Printing Services
- Wedding Gift Suppliers

**Required Functionality:**
- Category-specific booking forms ✅ (booking_mode driven)
- Availability checking ✅ (`VendorAvailability`)
- Quotation and booking ✅ (`ServiceBooking`, `RFQBid`)
- Payments ✅ (`ServicePayment`)
- Reviews ✅ (`Review`)

**Models:** `VendorService`, `VendorPortfolioMedia`, `ServiceBooking`, `ServicePayment`, `VendorAvailability`, `CarFleet`, `Review`

**Booking Mode:** `DATE_EVENT`, `TRANSPORT`, `HOURLY`, `PROJECT`

**Category Management:** Super Admin adds new categories via `VendorCategory` DB model — no code changes required.

---

## 8. Vendor Financial Model

| Component | Model | Purpose |
|---|---|---|
| Platform Commission | `ServiceBooking.commission_amount` | % of booking value (default 5%) |
| Vendor Net Amount | `ServiceBooking.net_vendor_amount` | Quoted - commission |
| Advance Payment | `ServiceBooking.advance_amount` | Default 30% (configurable) |
| Wallet Balance | `VendorWallet.balance` | Vendor's available funds |
| Wallet Transactions | `WalletTransaction` | CREDIT/DEBIT audit trail |
| Payout Requests | `VendorPayout` | Vendor requests withdrawal |
| Payout Processing | `ServiceBookingService::processVendorPayout()` | Admin approves payout |

**Payment Flow:**
1. Customer accepts vendor quote → `ServiceBooking` created (ACCEPTED)
2. Customer pays advance → `ServicePayment` (ADVANCE, VERIFIED)
3. Booking confirmed → wallet credited with net vendor amount
4. Service completed → final payment collected
5. Vendor requests payout → `VendorPayout` (REQUESTED)
6. Admin approves → wallet debited, payout marked PAID
