> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# OPENCODE SECURITY REMEDIATION — Riwaj Grand

**Date:** 2026-11-16
**Branch:** `enterprise-hardening` (safety backup branch created from `main`)
**Scope of this cycle's remediation:** File-upload validation hardening (Phase 1 security priority).

---

## 1. SECURITY FINDINGS FIXED THIS CYCLE

### F-1. Vendor portfolio uploads — extension-only validation (HIGH severity)

**File:** `app/Livewire/VendorPortfolioManager.php`

**Defect:** The upload validation rule was:

```php
'file' => ['required', 'file', 'mimes:jpeg,png,jpg,webp,mp4,mov,m4v', 'max:51200'],
```

The `mimes` rule validates by **file extension only**, which is trivially spoofable by renaming a malicious payload. A client could upload a PHP web shell renamed to `.jpg`.

**Fix applied:**

```php
$file = $this->validate([
    'file' => [
        'required',
        'file',
        // MIME type is authoritative; the extension must also match.
        'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/x-matroska',
        'mimes:jpeg,png,jpg,webp,mp4,mov,m4v',
        'max:51200', // 50MB
    ],
])['file'] ?? null;
```

Additionally the component was missing the Livewire file-upload property declaration, so the file was never bound into the component state:

```php
public $file = null;
```

### F-2. Hall chat voice uploads — extension-only validation (MEDIUM/HIGH)

**Files:** `app/Livewire/HallChat.php`, `app/Livewire/VenueChatWidget.php`

**Defect:** `'voiceRecording' => 'required|file|mimes:mp3,wav,ogg,m4a|max:5120'` — extension-only allow-list, no MIME validation, no `webm` coverage on the venue widget.

**Fix applied (both components):**

```php
'voiceRecording' => 'required|file|mimetypes:audio/mpeg,audio/mp4,audio/ogg,audio/wav,audio/m4a,audio/webm|mimes:mp3,wav,ogg,m4a,webm|max:5120', // 5MB
```

### F-3. Hall gallery image uploads — no extension control (MEDIUM)

**File:** `app/Livewire/HallMediaManager.php`

**Defect:** `'imageUploads.*' => 'image|max:5120'` — allowed any image MIME with no extension allow-list.

**Fix applied:**

```php
'imageUploads.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120', // 5MB per image
```

### F-4. Hall chat voice — vendor-context authorization check

While hardening the upload paths, the `HallChat::uploadVoice()` action was verified to still gate the actor (`isSuperAdmin()` or same-tenant hall owner) before persisting — no change required, behaviour confirmed correct.

---

## 2. REGRESSION TESTS ADDED (Phase 1)

**File:** `tests/Feature/FileUploadSecurityTest.php` — **5 tests / 14 assertions, ALL PASSING**

| # | Test | Result |
|---|---|---|
| 1 | vendor portfolio rejects disallowed MIME (`application/x-php` via renamed `.php`) | ✅ PASS |
| 2 | vendor portfolio rejects disallowed image MIME (`image/svg+xml`) | ✅ PASS |
| 3 | vendor portfolio accepts allowed image (JPEG) and persists record | ✅ PASS |
| 4 | vendor portfolio rejects oversized upload (60 MB > 50 MB cap) | ✅ PASS |
| 5 | hall chat voice upload rejects disallowed MIME (`text/plain`) | ✅ PASS |

Full run output:

```
PASS  Tests\Feature\FileUploadSecurityTest
✓ vendor portfolio rejects disallowed mime type
✓ vendor portfolio accepts allowed image and persists it
✓ vendor portfolio rejects oversized upload
✓ hall chat voice upload rejects disallowed mime type
✓ vendor portfolio rejects disallowed image mime type

  Tests:    5 passed (14 assertions)
  Duration: 23.24s
```

The security posture is now: **MIME type allow-list AND extension allow-list AND hard size cap** for all uploads, with `application/x-php`-style dangerous types rejected.

---

## 3. OTHER SECURITY REMEDIATION COMPLETED IN EARLIER CYCLES (verified this cycle)

| # | Remediation | Status |
|---|---|---|
| 1 | Payment-proof download route unregistered → guaranteed 500 | Fixed: `GET documents/vendor-payment-proof/{id}` + controller action added |
| 2 | 2FA feature incomplete / inert (no package, no columns, no secret) | Fixed: `pragmarx/google2fa` + `endroid/qr-code`, migration, model casts, setup/verify/recovery flow |
| 3 | Cross-tenant RBAC enforcement tests | Added across `EnterpriseExpansionTest`, `ChatTenantIsolationSecurityTest`, etc. |
| 4 | API bearer token lifecycle (issue/revoke) | Added `ApiTokenController` + `/api/v1/tokens` |

---

## 4. REMAINING SECURITY RISKS / GAPS (honest)

| Risk | Severity | Disposition |
|---|---|---|
| Live gateway sandbox round-trips not executed | Medium | Requires merchant credentials; blocked on external provider |
| Backup automation / restore scripts | Medium | Deferred to Phase 2 production hardening |
| Error tracking (Sentry/Bugsnag) not wired | Medium | Deferred |
| External penetration test | Medium | Deferred |
| Payment webhook signature + replay validation | Medium | Implemented earlier; live round-trip pending |
| Content moderation pipeline for uploads/reviews | Low | Deferred |

No critical or high-severity security findings remain open after this cycle.

---

## 5. VERIFICATION COMMANDS & OUTPUT

```bash
php artisan test tests/Feature/FileUploadSecurityTest.php
# → Tests: 5 passed (14 assertions)

php artisan test --compact
# → Tests: 1 skipped, 195 passed (1219 assertions) — final green run after all edits

composer audit
# → No security vulnerability advisories found.
```

---

*Next deliverable: `OPENCODE_RBAC_VERIFICATION.md`.*
