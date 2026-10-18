# Comprehensive Audit Findings & Technical Analysis

> **Document Version:** 1.0.0-GA  
> **Classification:** Confidential / Enterprise Technical Audit  
> **Repository:** `f:\laravelproject\Wedding_hall`

---

## Index of Findings

| ID | Title | Category | Severity | Confidence | Status |
|---|---|---|---|---|---|
| **DATA-01** | Cancelled & Inquiry Bookings Cause Database Deadlock on Re-booking | Database Integrity | **CRITICAL** | Confirmed | Open |
| **SEC-01** | Null `tenant_id` Grants Unrestricted Cross-Tenant ERP Access | Authorization / IDOR | **CRITICAL** | Confirmed | Open |
| **SEC-02** | Unauthenticated Phone Substring Enumeration in Public Guest RSVP | Information Leakage | **HIGH** | Confirmed | Open |
| **DATA-02** | Full Day Bookings Permit Overlapping Single Shift Insertions | Concurrency / DB | **HIGH** | Confirmed | Open |
| **FUNC-01** | Public Contact Form Silently Discards Inquiries Without Persistence | Incomplete Feature | **MEDIUM** | Confirmed | Open |
| **UI-01** | Case-Sensitivity Mismatch Renders 30-Day Heatmap Always Empty | UI/UX & Data Format | **MEDIUM** | Confirmed | Open |
| **FUNC-02** | Public Venues Date/Shift Filter Omits Overlapping Full Day Reservations | Business Logic | **MEDIUM** | Confirmed | Open |
| **FUNC-03** | Super Admin Vendor Registration Master Switch Not Respected by Auth | Business Logic | **MEDIUM** | Confirmed | Open |
| **PERF-01** | N+1 Query on Homepage & Unbounded Hall Loading on Availability Page | Performance | **MEDIUM** | Confirmed | Open |
| **SEC-03** | Unthrottled WhatsApp Webhook Endpoint Allows Resource Exhaustion | Denial of Service | **MEDIUM** | Confirmed | Open |
| **DATA-03** | String Slugs Passed to UUID Column Crash on PostgreSQL | Database Portability| **LOW** | Confirmed | Open |
| **OPS-01** | `SESSION_SECURE_COOKIE` Undocumented in `.env.example` Template | DevOps / Config | **LOW** | Confirmed | Open |

---

## Detailed Technical Findings

### [DATA-01] Cancelled & Inquiry Bookings Cause Database Deadlock on Re-booking
- **Severity:** **CRITICAL (P0)**
- **Confidence:** Confirmed
- **Category:** Database Schema & Integrity / Concurrency
- **Affected Files:**
  - `database/migrations/2026_10_09_000003_create_bookings_table.php#L53`
  - `app/Services/BookingService.php#L175-L186`
  - `app/Livewire/WalkInBookingModal.php#L440-L475`
- **Technical Explanation:**
  The migration declares:
  ```php
  $table->unique(['hall_id', 'event_date', 'slot_type'], 'uq_hall_event_slot');
  ```
  In MySQL, SQLite, and PostgreSQL, a multi-column unique constraint enforces uniqueness across all rows regardless of other column values.
  In `BookingService::slotConflictQuery()`:
  ```php
  return Booking::where('hall_id', $hallId)
      ->whereDate('event_date', $eventDate)
      ->whereIn('slot_type', $conflictingSlots)
      ->whereIn('status', ['HOLD', 'CONFIRMED']);
  ```
  When a customer cancels a booking, its status changes to `CANCELLED`. The row remains in the table. When a subsequent customer books that date and slot, `slotConflictQuery()` reports the slot as available. However, `Booking::create(...)` attempts an SQL `INSERT`, triggering an uncaught:
  `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry ... for key 'uq_hall_event_slot'`
  Additionally, public inquiries (`status = INQUIRY`) also insert a row with `slot_type`, preventing any other customer from inquiring or booking that slot.
- **Realistic Impact:** A single cancellation or inquiry permanently freezes that hall slot in the database.
- **Recommended Remediation:**
  1. Drop the hard unique constraint `uq_hall_event_slot`.
  2. Implement a filtered partial unique index in PostgreSQL (`CREATE UNIQUE INDEX ... WHERE status IN ('HOLD', 'CONFIRMED')`), or for cross-database compatibility (MySQL/SQLite), rely on the existing mutex lock in `BookingService::createReservation` which checks `slotConflictQuery()->lockForUpdate()`.
  3. Alternatively, when a booking is cancelled, update its slot to `CANCELLED_<uuid>` or soft-delete the row.

---

### [SEC-01] Null `tenant_id` Grants Unrestricted Cross-Tenant ERP Access
- **Severity:** **CRITICAL (P0)**
- **Confidence:** Confirmed
- **Category:** Broken Access Control / Multi-Tenant Isolation
- **Affected Files:**
  - `app/Livewire/FloorPlanDesigner.php#L20-L24, #L90-L94`
  - `app/Livewire/KitchenKotBoard.php#L21-L26, #L36-L42`
  - `app/Livewire/UtilityFuelTracker.php#L23-L27, #L67-L72`
  - `app/Livewire/WhatsAppRsvpManager.php#L26-L30, #L58-L62`
  - `app/Livewire/HallMediaManager.php#L110-L117, #L255-L270`
  - `routes/web.php#L139-L153`
- **Technical Explanation:**
  The vendor ERP routes in `routes/web.php` only enforce `Route::middleware('auth')`.
  In several Livewire components, tenant scoping is written conditionally:
  ```php
  // KitchenKotBoard.php
  $user = Auth::user();
  $query = KitchenKot::query();
  if ($user && $user->tenant_id) {
      $query->where('tenant_id', $user->tenant_id);
  }
  $kot = $query->findOrFail($kotId);
  $kot->update(['preparation_status' => $status]);
  ```
  ```php
  // FloorPlanDesigner.php
  $hall = Hall::where('id', $this->selectedHallId)
      ->when($user && $user->tenant_id, fn($q) => $q->where('tenant_id', $user->tenant_id))
      ->first();
  ```
  If an authenticated user has `tenant_id === null` (such as a registered customer account or an unassigned user), the `if ($user && $user->tenant_id)` condition evaluates to false. The query executes **completely unconstrained across all tenants**.
- **Realistic Impact:** Any customer account can navigate to `/kitchen-kots`, `/seating-planner`, `/utility-fuel`, or `/whatsapp-rsvps` and modify operational records, floor plans, and fuel logs belonging to other businesses.
- **Recommended Remediation:**
  1. Add a dedicated route middleware `tenant.staff` that verifies `$user && $user->tenant_id && !$user->isCustomer()`.
  2. In component `mount()`, explicitly abort with 403 if the user does not belong to a valid tenant:
     ```php
     if (!$user || !$user->tenant_id || $user->isCustomer()) {
         abort(403, 'Unauthorized. Tenant staff access required.');
     }
     ```
  3. Change queries from `if ($user->tenant_id)` to strictly enforce `where('tenant_id', $user->tenant_id)`.

---

### [SEC-02] Unauthenticated Phone Substring Enumeration in Public Guest RSVP
- **Severity:** **HIGH (P1)**
- **Confidence:** Confirmed
- **Category:** Information Disclosure / Sensitive Data Exposure
- **Affected Files:**
  - `app/Livewire/PublicGuestRsvp.php#L22-L30`
- **Technical Explanation:**
  ```php
  $cleanPhone = preg_replace('/[^0-9]/', '', $this->phone);
  $this->foundRsvp = GuestRsvp::with('booking.hall.venue')
      ->where(function ($q) use ($cleanPhone) {
          $q->where('phone', 'like', '%' . $this->phone . '%')
            ->orWhere('phone', 'like', '%' . $cleanPhone . '%');
      })
      ->first();
  ```
  Entering a single digit (e.g. `"3"`) causes SQL: `WHERE phone LIKE '%3%'`. This matches almost every Pakistani telephone number (`+923...`). The first matching guest record is returned and hydrated with `booking.hall.venue`. The UI renders the guest name, hall name, event date, and attendance count to unauthenticated public visitors.
- **Realistic Impact:** An attacker can iterate through digits 0-9 and scrape customer RSVP invitations, names, and wedding dates.
- **Recommended Remediation:**
  Require exact normalized phone number matching or enforce a strict minimum digit count (at least 10 digits):
  ```php
  $this->validate(['phone' => 'required|string|min:10|max:25']);
  $cleanPhone = preg_replace('/[^0-9]/', '', $this->phone);
  if (strlen($cleanPhone) < 10) {
      $this->feedbackMessage = 'Please enter a valid 10 or 11 digit mobile number.';
      return;
  }
  ```

---

### [DATA-02] Full Day Bookings Permit Overlapping Single Shift Insertions
- **Severity:** **HIGH (P1)**
- **Confidence:** Confirmed
- **Category:** Database Constraints / Business Logic
- **Affected Files:**
  - `database/migrations/2026_10_09_000003_create_bookings_table.php#L53`
  - `app/Services/BookingService.php#L178-L186`
- **Technical Explanation:**
  The unique key is `(hall_id, event_date, slot_type)`. If row 1 is `(hall-1, 2026-11-20, 'FULL_DAY')` and row 2 is `(hall-1, 2026-11-20, 'EVENING')`, both rows satisfy the unique index because `'FULL_DAY' != 'EVENING'`.
  While `BookingService::slotConflictQuery()` checks for this in PHP, if any transaction or external script bypasses the service, the database permits duplicate overlapping reservations.
- **Realistic Impact:** Inability of the database layer to enforce full-day exclusivity without application-level locks.
- **Recommended Remediation:**
  In `BookingService`, maintain the current distributed cache lock and add unit/integration assertions guaranteeing that full-day bookings reject any individual shift and vice-versa.

---

### [FUNC-01] Public Contact Form Silently Discards Inquiries Without Persistence
- **Severity:** **MEDIUM (P2)**
- **Confidence:** Confirmed
- **Category:** Functional Defect / Incomplete Feature
- **Affected Files:**
  - `app/Livewire/PublicContact.php#L27-L33`
- **Technical Explanation:**
  ```php
  public function submitInquiry()
  {
      $this->validate();
      // In a production environment, send notification to concierge desk
      $this->isSubmitted = true;
  }
  ```
  The contact message is validated, but never stored in any database table and no email is dispatched. The user is presented with a success message, but the data is lost immediately.
- **Realistic Impact:** Customer leads and corporate banquet inquiries are lost.
- **Recommended Remediation:**
  Create a migration for `contact_inquiries` table, store the inquiry record, and optionally trigger a notification or log entry.

---

### [UI-01] Case-Sensitivity Mismatch Renders 30-Day Heatmap Always Empty
- **Severity:** **MEDIUM (P2)**
- **Confidence:** Confirmed
- **Category:** UI/UX & Data Formatting
- **Affected Files:**
  - `app/Livewire/PublicVenues.php#L183-L187`
- **Technical Explanation:**
  The database stores `slot_type` as uppercase: `'AFTERNOON'`, `'EVENING'`, `'NIGHT'`.
  In `PublicVenues.php`:
  ```php
  $availability[$date] = [
      'afternoon' => (int) ($dayBookings->firstWhere('slot_type', 'afternoon')->booked_count ?? 0),
      'evening'   => (int) ($dayBookings->firstWhere('slot_type', 'evening')->booked_count ?? 0),
      'night'     => (int) ($dayBookings->firstWhere('slot_type', 'night')->booked_count ?? 0),
  ];
  ```
  Collection `firstWhere()` performs strict comparison (`===`). Since `'AFTERNOON' === 'afternoon'` is false, the expression always returns null and defaults to 0.
- **Realistic Impact:** The 30-day shift availability heatmap on `/venues` displays 0 booked shifts on every single day, giving users misleading availability data.
- **Recommended Remediation:**
  Change lookup to uppercase:
  ```php
  'afternoon' => (int) ($dayBookings->firstWhere('slot_type', 'AFTERNOON')->booked_count ?? 0),
  'evening'   => (int) ($dayBookings->firstWhere('slot_type', 'EVENING')->booked_count ?? 0),
  'night'     => (int) ($dayBookings->firstWhere('slot_type', 'NIGHT')->booked_count ?? 0),
  ```

---

### [FUNC-02] Public Venues Date/Shift Filter Omits Overlapping Full Day Reservations
- **Severity:** **MEDIUM (P2)**
- **Confidence:** Confirmed
- **Category:** Search & Filtering Logic
- **Affected Files:**
  - `app/Livewire/PublicVenues.php#L131-L140`
- **Technical Explanation:**
  ```php
  if ($this->shift !== 'ALL') {
      $bookingQuery->where('slot_type', $this->shift);
      $bookedHallIds = $bookingQuery->pluck('hall_id');
      $query->whereNotIn('id', $bookedHallIds);
  }
  ```
  If a customer searches for `AFTERNOON`, the query only looks for `slot_type = 'AFTERNOON'`. If a hall has a `FULL_DAY` reservation on that date, it is not matched, and the booked hall is incorrectly presented as available.
- **Realistic Impact:** Customers may see halls listed as available when they are already reserved for full-day events.
- **Recommended Remediation:**
  Update query to:
  ```php
  $bookingQuery->whereIn('slot_type', [$this->shift, 'FULL_DAY']);
  ```

---

### [FUNC-03] Super Admin Vendor Registration Master Switch Not Respected by Auth
- **Severity:** **MEDIUM (P2)**
- **Confidence:** Confirmed
- **Category:** Business Logic / Governance
- **Affected Files:**
  - `app/Livewire/Auth/Register.php#L38-L41`
  - `app/Livewire/AdminDashboard.php#L117`
- **Technical Explanation:**
  Super Admin dashboard provides a toggle switch to enable or disable public vendor self-registration via `Cache::get('platform_vendor_registration_open', true)`. However, `Register::register()` never checks this cache key.
- **Realistic Impact:** Platform administrators cannot pause vendor self-registration during onboarding halts or maintenance windows.
- **Recommended Remediation:**
  In `Register::register()` and `mount()`, check the cache key and abort or add a validation error if registration is disabled.

---

### [PERF-01] N+1 Query on Homepage & Unbounded Hall Loading on Availability Page
- **Severity:** **MEDIUM (P2)**
- **Confidence:** Confirmed
- **Category:** Performance & Scalability
- **Affected Files:**
  - `routes/web.php#L48-L54`
  - `app/Livewire/PublicAvailabilityChecker.php#L28-L33`
- **Technical Explanation:**
  1. `routes/web.php` loads featured halls: `Hall::where('is_active', true)->take(6)->get()`. In `welcome.blade.php`, `{{ $hall->venue->city }}` is accessed, triggering 6 lazy-loaded queries.
  2. `PublicAvailabilityChecker.php` executes `Hall::where('is_active', true)->whereHas('tenant', ...)->get()` without pagination, loading every venue in the platform on every date change.
- **Realistic Impact:** Increased database latency and query volume as catalog scales.
- **Recommended Remediation:**
  1. Eager load: `Hall::with(['venue', 'tenant'])->where(...)->take(6)->get()`.
  2. Add pagination or limit to `PublicAvailabilityChecker`.

---

### [SEC-03] Unthrottled WhatsApp Webhook Endpoint Allows Resource Exhaustion
- **Severity:** **MEDIUM (P2)**
- **Confidence:** Confirmed
- **Category:** Rate Limiting / Denial of Service
- **Affected Files:**
  - `routes/web.php#L184-L185`
  - `app/Http/Controllers/WhatsAppWebhookController.php#L33-L45`
- **Technical Explanation:**
  `/webhooks/whatsapp` is exempt from CSRF and does not have the `throttle` middleware attached. An attacker can flood this endpoint with requests, forcing continuous HMAC calculation and database queries.
- **Realistic Impact:** High CPU load and potential service degradation during flood attacks.
- **Recommended Remediation:**
  Attach `middleware('throttle:120,1')` to the webhook route in `routes/web.php`.

---

### [DATA-03] String Slugs Passed to UUID Column Crash on PostgreSQL
- **Severity:** **LOW (P3)**
- **Confidence:** Confirmed
- **Category:** Database Portability
- **Affected Files:**
  - `app/Livewire/PublicHallDetails.php#L38-L40`
- **Technical Explanation:**
  ```php
  $this->hall = Hall::with(['venue', 'tenant'])
      ->where('slug', $identifier)
      ->orWhere('id', $identifier)
      ->firstOrFail();
  ```
  If `$identifier` is a human-readable slug (e.g. `'royal-palace'`), in PostgreSQL passing a non-UUID string to a UUID column generates `SQLSTATE[22P02]: Invalid text representation`.
- **Realistic Impact:** Crashes on PostgreSQL production environments.
- **Recommended Remediation:**
  ```php
  $this->hall = Hall::with(['venue', 'tenant'])
      ->where('slug', $identifier)
      ->when(Str::isUuid($identifier), fn($q) => $q->orWhere('id', $identifier))
      ->firstOrFail();
  ```

---

### [OPS-01] `SESSION_SECURE_COOKIE` Undocumented in `.env.example` Template
- **Severity:** **LOW (P3)**
- **Confidence:** Confirmed
- **Category:** DevOps & Configuration
- **Affected Files:**
  - `.env.example#L30-L35`
  - `config/session.php#L172`
- **Technical Explanation:**
  `config/session.php` reads `env('SESSION_SECURE_COOKIE')`, but `.env.example` does not list this key.
- **Realistic Impact:** Production environments behind proxies might leave this unset, relying on auto-detection.
- **Recommended Remediation:**
  Add `SESSION_SECURE_COOKIE=true` to `.env.example` with a production deployment note.
