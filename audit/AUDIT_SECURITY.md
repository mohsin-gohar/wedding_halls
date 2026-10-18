# Defensive Application Security & OWASP Top 10 Audit

> **Document Version:** 1.0.0-GA  
> **Evaluation Framework:** OWASP Top 10 (2021/2026), NIST SP 800-53, Multi-Tenant SaaS Isolation Guidelines  
> **Target:** EventHub Platform

---

## 1. OWASP Top 10 Assessment Matrix

| OWASP Category | Risk Level | Status in Codebase | Findings & Technical Notes |
|---|---|---|---|
| **A01: Broken Access Control** | **HIGH** | **Action Required** | Found in 5 ERP components when `tenant_id` is null (`SEC-01`). Document downloads strictly secured. |
| **A02: Cryptographic Failures** | **LOW** | **PASS** | Passwords hashed with Bcrypt (cost 12), webhook signatures verified with HMAC-SHA256. |
| **A03: Injection (SQL / Command)** | **LOW** | **PASS** | Eloquent ORM & PDO prepared statements used everywhere; no raw unescaped SQL concatenated. |
| **A04: Insecure Design** | **HIGH** | **Action Required** | Hard unique index prevents re-booking cancelled dates (`DATA-01`). |
| **A05: Security Misconfiguration** | **LOW** | **PASS** | Baseline security headers middleware active (`X-Frame-Options`, `CSP`, `X-Content-Type-Options`, `HSTS`). |
| **A06: Vulnerable & Outdated Components** | **LOW** | **PASS** | Laravel 12.69.3, PHP 8.2, Livewire 4.4, Dompdf 3.1. Zero high CVEs in lockfile. |
| **A07: Identification & Authentication Failures**| **LOW** | **PASS** | Rate limited logins (5/min), session fixation protection (`session()->regenerate()`), suspended checks. |
| **A08: Software & Data Integrity Failures** | **LOW** | **PASS** | Meta WhatsApp webhook signed with SHA256 HMAC and timing-safe `hash_equals`. |
| **A09: Security Logging & Monitoring Failures** | **LOW** | **PASS** | `AuditLog` records sensitive operations, logins tracked with `last_login_at`, page views recorded. |
| **A10: Server-Side Request Forgery (SSRF)** | **LOW** | **PASS** | Outbound HTTP restricted to official Meta Graph API endpoint (`https://graph.facebook.com`). |

---

## 2. In-Depth Vulnerability Analysis

### Vulnerability Analysis: `SEC-01` — Multi-Tenant Isolation & IDOR Bypass
- **Vulnerability Class:** CWE-639 (Authorization Bypass Through User-Controlled Key)
- **Affected Endpoints:**
  - `GET /kitchen-kots` (`KitchenKotBoard`)
  - `GET /seating-planner` (`FloorPlanDesigner`)
  - `GET /utility-fuel` (`UtilityFuelTracker`)
  - `GET /whatsapp-rsvps` (`WhatsAppRsvpManager`)
  - `GET /hall/media` (`HallMediaManager`)
- **Mechanics:**
  In Laravel Livewire, component methods are invocable via POST requests to `/livewire-2f35f136/update`.
  When a method like `updateStatus($kotId, $status)` or `saveFloorPlan()` is triggered:
  ```php
  $user = Auth::user();
  $query = KitchenKot::query();
  if ($user && $user->tenant_id) {
      $query->where('tenant_id', $user->tenant_id);
  }
  $kot = $query->findOrFail($kotId);
  $kot->update(['preparation_status' => $status]);
  ```
  If `$user->tenant_id` is null (e.g., an account registered via `/customer/register` or a system user without a tenant foreign key), the condition `if ($user && $user->tenant_id)` skips scoping. As a result, `$query->findOrFail($kotId)` succeeds on **any tenant's KOT record**.
- **Defensive Remediation:**
  Enforce tenant presence and staff role at the controller/component level before any method executes:
  ```php
  // App\Http\Middleware\EnsureTenantStaff.php
  public function handle(Request $request, Closure $next)
  {
      $user = $request->user();
      if (!$user || !$user->tenant_id || $user->isCustomer()) {
          abort(403, 'Forbidden. Tenant staff credentials required.');
      }
      return $next($request);
  }
  ```
  Apply this middleware to the entire Vendor ERP route group in `routes/web.php`.

---

### Vulnerability Analysis: `SEC-02` — Information Disclosure via Unsanitized Phone Search
- **Vulnerability Class:** CWE-200 (Exposure of Sensitive Information to an Unauthorized Actor)
- **Affected Endpoint:** `GET /rsvp` (`PublicGuestRsvp`)
- **Mechanics:**
  The `searchInvitation()` method executes:
  ```php
  $this->foundRsvp = GuestRsvp::with('booking.hall.venue')
      ->where('phone', 'like', '%' . $this->phone . '%')
      ->first();
  ```
  Because the input is not validated for minimum length, an unauthenticated visitor submitting single-digit inputs (e.g. `0`, `1`, `3`) will match the first phone number in the database containing that digit. The component then loads the `GuestRsvp` model and renders:
  - Guest Name (`$rsvp->guest_name`)
  - Wedding Hall Name (`$rsvp->booking->hall->name`)
  - Venue Address & City (`$rsvp->booking->hall->venue->city`)
  - Event Date (`$rsvp->booking->event_date`)
  - Attendance Status & Headcount
- **Defensive Remediation:**
  1. Enforce strict phone validation: `min:10|max:20`.
  2. Perform normalized digit extraction (`preg_replace('/[^0-9]/', '', $input)`).
  3. Require at least 10 normalized digits before executing the database query.

---

### Vulnerability Analysis: `SEC-03` — Webhook Flood & Missing Rate Limiter
- **Vulnerability Class:** CWE-770 (Allocation of Resources Without Limits or Throttling)
- **Affected Endpoint:** `POST /webhooks/whatsapp`
- **Mechanics:**
  In `bootstrap/app.php`, CSRF verification is explicitly disabled for `webhooks/*`.
  In `routes/web.php`, the route is defined without any throttle middleware:
  ```php
  Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle']);
  ```
  Because the endpoint performs cryptographic hashing (`hash_hmac`), JSON payload decoding, and database lookups with `LIKE '%...%'`, a high-volume request flood can cause CPU spike and thread starvation in PHP-FPM.
- **Defensive Remediation:**
  Apply standard API rate limiting:
  ```php
  Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle'])
      ->middleware('throttle:120,1');
  ```

---

## 3. Session & Header Hardening Review

- **Security Headers Middleware (`App\Http\Middleware\SecurityHeaders`):**
  - `X-Content-Type-Options: nosniff` (Active)
  - `X-Frame-Options: SAMEORIGIN` (Active)
  - `Referrer-Policy: strict-origin-when-cross-origin` (Active)
  - `Permissions-Policy: camera=(), microphone=(), geolocation=()` (Active)
  - `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload` (Active over HTTPS)
  - `Content-Security-Policy`: Tuned for Livewire/Alpine (`'unsafe-inline' 'unsafe-eval'`) and Google Fonts.
- **CSRF Protection:**
  - Enabled across all web forms and Livewire requests via `@livewireStyles` and `meta[name="csrf-token"]`.
  - Only `webhooks/*` is exempted, matching standard webhook architectures.
- **Password Policies & Encryption:**
  - Minimum 6 characters required on registrations; default Laravel Bcrypt with cost 12.
  - Recommend increasing registration password minimum to 8 characters with at least one letter and one number for enterprise compliance.
