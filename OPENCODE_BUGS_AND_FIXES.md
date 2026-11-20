> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# OPENCODE BUGS AND FIXES — Riwaj Grand

**Date:** 2026-11-16
**Branch:** `enterprise-hardening`
**Cycle:** Final QA bug-fix pass (post-enterprise-expansion hardening)

---

## 1. DEFECTS FIXED IN THIS CYCLE (2026-11-16)

### A. Application stability — crashes that produced HTTP 500s

| # | Defect | Root cause | Fix | File |
|---|---|---|---|---|
| 1 | Payment-proof download route was referenced but never registered | Route missing | Added `GET documents/vendor-payment-proof/{id}` → `DocumentController@vendorPaymentProof` | `routes/web.php`, `app/Http/Controllers/DocumentController.php` |
| 2 | Five vendor category dashboards crashed (`MultipleRootElements`) | `<x-layouts.app>` + `#[Layout]` double root | Removed redundant wrapper | 5 `app/Livewire/*Dashboard.php` + blades |
| 3 | Photographer dashboard crashed (Blade ParseError) | `@foreach` … `@empty` mismatch | `@forelse` / `@endforelse` | `vendor-photographer-dashboard.blade.php` |
| 4 | Portfolio upload/manage crashed (malformed Blade key + unsafe optional access) | `$item['caption )` typo, `$item['category']` | Fixed quoting/null-safety | `vendor-portfolio-manager.blade.php` |
| 5 | Quotation awarding crashed (`ServiceBookingService` unresolvable) | Missing `use App\Services\ServiceBookingService;` in `App\Livewire` namespace | Added import | `app/Livewire/VendorQuotesBoard.php` |
| 6 | Service-booking actions crashed on UUID keys (`int` typed params) | `ServiceBooking` uses UUID PKs | Typed params → `string` | `app/Livewire/VendorBookingsBoard.php` |
| 7 | Cancel booking action crashed (`->first;` missing parens) | Typo | Fixed | `app/Livewire/VendorBookingsBoard.php` |
| 8 | Cancel refusal used HTTP abort inside Livewire action | Not graceful | Inline `errorMessage` set | `app/Livewire/VendorBookingsBoard.php` |

### B. Two-factor authentication — was entirely non-functional

| # | Defect | Fix |
|---|---|---|
| 1 | `PragmaRX\Google2FA` package missing → `/2fa/*` always 500 | Installed `pragmarx/google2fa` |
| 2 | No `two_factor_*` columns / casts / recovery storage | Migration `2026_12_07_000001`, model casts (`encrypted`, `array`), hidden attributes |
| 3 | Setup QR never rendered | Installed `endroid/qr-code`; server-side SVG data-URI QR (`qrCodeDataUri` property) |
| 4 | Enable/verify/recovery/disable logic incomplete | Full flow + single-use recovery codes + password-gated disable |

### C. Security hardening (Phase 1)

| # | Defect | Fix | Regression tests |
|---|---|---|---|
| 1 | Vendor portfolio upload validated extension-only (`mimes:`) | MIME allow-list + extension allow-list + 50MB cap | `tests/Feature/FileUploadSecurityTest.php` (5 tests, all passing) |
| 2 | Hall/voice uploads extension-only (`mimes:`) | MIME allow-list + extension allow-list + 5MB cap | Covered by new security test + chatbot suite |
| 3 | Hall image upload lacked extension control | `image` + `mimes:jpg,jpeg,png,webp` | — |

### D. QA-harness defects

| # | Defect | Fix |
|---|---|---|
| 1 | Route-crawl PHP script treated `request()->route('policy')` as a route reference | Guarded with `Route::has()` + negative lookbehind |
| 2 | `@php` inline directive balance false positive | Inline `@php(...)` handling |
| 3 | Guest-persona assertions leaked auth state between tests | Split tests |
| 4 | Livewire `set()` after `call()` ordering mistakes | Corrected in tests |

---

## 2. DEFECTS REPRODUCED & VERIFIED FIXED (this cycle)

- ✅ `/2fa/setup`, `/2fa/verify` no longer 500 (full flow works, incl. QR + recovery)
- ✅ `/vendor/dashboard/{photography,catering,decoration,beauty-bridal,wedding-planners}` render
- ✅ `/vendor/portfolio` renders
- ✅ Vendor quotation → award → service-booking flow end-to-end
- ✅ Service booking lifecycle actions (confirm/start/complete/cancel) incl. graceful refusal
- ✅ Service catalogue create/toggle
- ✅ Support centre create/reply/status
- ✅ Customer event workspace full action set
- ✅ Vendor finance payout bounded
- ✅ File upload MIME rejection + acceptance + size cap
- ✅ Full regression suite green after fixes

---

## 3. DEFERRED ITEMS (documented, non-blocking)

- Live gateway sandbox round-trips (merchant credentials required)
- Backup automation, error tracking, external pentest
- Coupon/promotion engine, dispute entity, review moderation, mobile app, public-site art direction
- Monitoring dashboards

*Next: Phase 1 financial/tenant-isolation regression confirmation via full suite + Phase 2 incomplete features.*
