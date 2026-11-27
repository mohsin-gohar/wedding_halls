# File-by-File Review Matrix — Riwaj Grand (independent re-audit)

**Branch:** `audit/riwaj-grand-repair-20261126` · **Base:** `df8de437` · **Date:** 2026-11-26  
**Tracked files at latest verified HEAD (recursive Git tree):** 552

## 1. Review depth legend

- **D** — Deep review: file opened, purpose/control flow understood, defect or risk assessed, change/test evidence recorded.
- **A** — Automated verification only: covered by PHP lint, Pint, `npm run build`, route:list, or executed tests; **not** a claim of individual manual review.
- **N** — Not individually reviewed: listed by directory with reason. Excluded from manual review but **not** excluded from automated gates.

Generated directories (`vendor/`, `node_modules/`, `public/build/`, compiled views, caches) are untracked/ignored. Dependency versions and advisories were audited via `composer.lock` and `composer audit` (final result: 0 advisories).

## 2. Directory summary

| Directory | Tracked | Review coverage |
|---|---:|---|
| `(root)` | 35 | D: Composer/npm manifests and locks, PHPUnit/Playwright config, env examples, Docker files, README and current authoritative reports. Historical snapshots are recoverable from Git history, not the active tree. |
| `.github` | 1 | D: `ci.yml` (quality, MySQL, browser and manual-only deploy gates). |
| `app` | 178 | D: security-sensitive controllers, middleware, policies, domain services, permission/tenant support and components tied to confirmed fixes. A/N: remaining Livewire components were syntax-checked and exercised through CI tests/browser coverage, not individually deep-read. |
| `bootstrap` | 3 | D: application bootstrap, providers and cache/exception/rate-limit wiring. |
| `config` | 18 | D: platform, services, filesystems, Livewire, database, app and rate-limit configs. A: remaining framework configs. |
| `database` | 84 | D: recent financial/booking/attendance migrations, seeders and schema changes involved in fixes. A/N: older migrations covered by clean MySQL migrations and tests, not all individually reviewed line-by-line. |
| `deploy` | 15 | D: Nginx/Caddy, supervisor, backup/restore scripts, production checklist and operations guidance. |
| `docker` | 1 | D: container entrypoint helpers. |
| `public` | 14 | A: public entrypoints and static assets; build integrity covered by CI. Binary assets not manually reviewed. |
| `resources` | 86 | D: key layouts and defect-related Blade views. A/N: remaining templates covered by CI Blade compilation/browser tests, not individually read. |
| `routes` | 3 | D: web, API and console routes; 109 routes registered in local inspection. |
| `storage` | 10 | N: framework placeholders only; runtime logs/uploads are ignored and not committed. |
| `tests` | 104 | D: added/changed security, finance, tenancy and workflow regression tests. Full suite executed in CI; not every test file individually read. |


## 3. Files personally opened and changed this re-audit

`app/Livewire/AdminDashboard.php`, `UtilityFuelTracker.php`, `InventoryTracker.php`, `StaffManager.php`; `app/Services/PlatformAnalyticsService.php`, `ServiceBookingService.php`; `app/Support/PermissionMatrix.php`; `resources/views/livewire/inventory-tracker.blade.php`; `composer.lock`; three new regression tests; plus the eight report documents. Each change is backed by a test, an audit result, or both.

## 4. Unreviewed files and reasons

- Historical `*.md` reports were removed from the active tree; their original content remains recoverable from Git history and is not current source of truth.
- Remaining Livewire/Blade files (~250): covered by syntax lint, Blade compilation through the full web route crawl, Playwright page loads, and feature tests; not individually manually reviewed. This is explicitly **NOT** a 100% manual file-review claim.
- Binary/static assets under `public/`: excluded from source review; build integrity verified by `npm run build` and browser asset loads.
- `vendor/`, `node_modules/`, compiled caches: generated, untracked; audited through lockfiles, `composer audit` and `npm run build`.

## 5. Verification evidence for this matrix

`composer audit --locked` → 0 advisories; `npm run build` → exit 0; `vendor/bin/pint --test` → exit 0; PHP lint over 470 tracked PHP files; recursive Git tree count 552; full MySQL suite artifact `full_suite_repair*.out`.

## 6. Continuation (2026-11-27)

- New migration `database/migrations/2026_12_23_000001_harden_financial_foreign_keys_and_unique_indexes.php` deep-reviewed and added (FK restrict + unique PO/booking indexes with fail-closed duplicate checks).
- `config/livewire.php` and `deploy/nginx-riwaj-grand.conf` updated for upload consistency and rate limit (prior push corrected).
- No additional manual deep review of the remaining ~250 Livewire/Blade files; automated gates still pending local/CI re-run.

## 7. Final pass (2026-11-27, HEAD cb6ad80)

- Financial migration `2026_12_23_000001` deep-reviewed: fail-closed duplicate checks, unique indexes, restrictOnDelete, irreversibility documented with staging restore-drill commands.
- Production configs (Redis/queue/scheduler/mail/WhatsApp) reviewed; live verification blocked on owner credentials.
- CI on source HEAD `1b57773` completed successfully: run 45982335954 (609 MySQL tests passed, 3 skipped; Playwright 8/8; static/build/security/Docker gates green).


## 8. Latest verification and cleanup

- GitHub CI run [45982335954](https://github.com/mohsin-gohar/wedding_halls/actions/runs/45982335954) on source HEAD `1b57773`: MySQL **609 passed / 3 skipped / 3,153 assertions**, Playwright **8 passed**, static/security/build/Docker gates **PASS**; deploy skipped intentionally.
- Uploaded ZIP inspection: 477 tracked PHP files pass `php -l`; Laravel registered 109 routes.
- Three tracked generated artifacts (`$null`, `migration_status.txt`, `routes.json`) were identified as shell/test/route-list outputs with no application references and removed.
- Sixty-four superseded root/audit Markdown reports were removed from the active tree after confirming `DOCS_STATUS.md` marked them historical; their original content remains recoverable from Git history.
- ZIP also contained ignored local dependency trees, environment files, logs and a nested duplicate project snapshot. These are not application source and are not committed.
- No application runtime code, database migration, route, dependency lockfile or production data was changed in this cleanup.


## 9. Exact latest verification (2026-11-27)

- Verified HEAD: `846ac407f098d641add8cfeabdc9784fc8b87c8b`; GitHub CI run [46011895564](https://github.com/mohsin-gohar/wedding_halls/actions/runs/46011895564).
- Recursive Git tree: **552 tracked blobs** (non-truncated), including **470 PHP** files. Inventory count only; not a claim of individual manual review.
- MySQL: **611 passed, 3 skipped, 0 failed; 3,184 assertions**.
- Playwright: **8/8 passed**; artifact `playwright-artifacts` (ID 15204463518) includes responsive screenshots and role-dashboard captures.
- Static gates: PHP lint, Pint, Composer audit, NPM audit, Vite build, Docker configuration/build and Nginx runtime smoke all passed.
- Remaining manual-review limitation remains: not every Livewire/Blade/test file was individually deep-read. Production deployment, live secrets and staging restore drill remain unverified.

## 8. Phase 1 continuation — security-focused file review (2026-11-28)

The following additional files received a targeted manual review of the security-sensitive control flow and output contexts:

- app/Livewire/Auth/VerifyEmail.php and routes/web.php: signed verification route uses query parameters. Fixed initial mount to read token and email from the signed GET query; added valid-link and expired-link feature tests.
- app/Livewire/CustomerEventWorkspace.php, resources/views/livewire/customer-event-workspace.blade.php, and tests/Feature/CustomerEventWorkspaceTest.php: booking ownership, row locking, event linkage, vendor shortlist state, and manual status transitions. Made booking link/unlink atomic, prevented forged BOOKED state, preserved a booked shortlist on replay, and retained the booked state when another booking for the same vendor remains linked. Added regression coverage.
- app/Livewire/WalkInBookingModal.php and tests/Feature/WalkInBookingAuthorizationTest.php: public Livewire isStaff state must not be trusted for authorization. Centralized hall resolution, recomputes staff mode before scoped hall queries, and added a forged-staff-mode hall-selection regression test.
- app/Support/TenantBrand.php, resources/views/livewire/white-label-site.blade.php, and tests/Unit/TenantBrandTest.php: validated white-label colors as six-digit hex values and restricted logo/cover URLs to HTTP(S) or safe local media paths before rendering into CSS/image contexts.
- resources/views/livewire/admin-dashboard.blade.php, public-availability-checker.blade.php, hall-chat.blade.php, public-hall-details.blade.php, public-menus.blade.php, and welcome.blade.php: replaced raw Blade interpolation inside inline JavaScript strings/handlers with @js encoding.

### Evidence and limits

- Repair-branch commits for this continuation: afb9bc3, 29c5249, bcf4552, a2432cc, d7de480, ea21697, 6d3da33, c6fe47c, 8659d49, ae94378, 6fcb695, 27c513f, d6ea2bb, 2df3329, e627a17, 45db70d, 431e08b, eb2a011.
- The uploaded source archive snapshot (1b57773) passed PHP syntax lint for 477 tracked PHP files with zero parse errors. This is snapshot evidence, not verification of the newer repair-branch commits.
- The local PHPUnit runner could not start because the available PHP runtime lacks dom, mbstring, and xmlwriter. Local Pint is blocked by missing mbstring and xml; Vite build is blocked because the archive's node_modules lacks the Linux Rollup native package.
- CI was intentionally not inspected, triggered, or modified per the user's instruction.
- This is a targeted continuation, not a claim that every remaining PHP/Blade file has now received a line-by-line manual review. The original unreviewed remainder in sections 2 and 4 remains open until individually reviewed.

## 9. Phase 1 continuation — additional authorization and state-integrity fixes (2026-11-28)

Further review of middleware, policies, service classes and their callers identified and corrected:

- app/Livewire/Auth/TwoFactorAuthComponent.php: the post-verification redirect target was normalized only in mount, while Livewire public properties remain client-controlled. Re-normalize immediately before successful verification redirects; added an external-host tampering regression test.
- app/Http/Middleware/EnsureActiveAccount.php: changed the session guard from a deny-list of three statuses to default-deny unless status is exactly ACTIVE. This prevents PENDING and unknown future statuses from retaining an authenticated session; added a regression test.
- app/Policies/RFQPolicy.php: vendor bidding policy previously returned true for every tenant owner. It now requires rfq.bid permission and an RFQ owned by or explicitly targeted to that vendor tenant; added a targeted-vendor policy regression test.
- app/Services/NotificationService.php: notifications now inherit the recipient's tenant when omitted and reject a conflicting tenant for tenant-assigned recipients. Tenant-less customer recipients can still receive support notifications associated with a tenant ticket; added tenant-context tests.
- app/Livewire/WalkInBookingModal.php: public isStaff state is recalculated before hall-resolution queries, closing the state-tampering gap; added a hall-selection regression test.

### Evidence commits

- 107c460 — 2FA redirect revalidation
- 4fbb27c — 2FA redirect tampering regression
- 11d756a — active-account default-deny middleware
- 9933271 — pending-account session regression
- 44c265c — RFQ bid policy tenant targeting
- 1eb9e71 — explicit RFQ target regression
- 47a799d, 98ee402, 9ac8c70 — notification tenant context guard and customer-support compatibility
- 45db70d, eb2a011 — walk-in staff-mode revalidation and regression
- 4f010ac, 117ddb1 — notification tenant-context regression coverage

The new/updated tests have been committed but could not be executed in the current local runtime because required PHP extensions are absent. No CI action was taken. Full Phase 1 file-by-file review remains in progress; no 100% completion claim is made.

## 10. Phase 1 continuation — hall media and guest chat context (2026-11-28)

- app/Livewire/HallMediaManager.php: blocked hall creation without an assigned tenant, blocked uploads without a selected tenant-owned hall, stopped silent saves when no hall is selected, preserved the existing tenant name when the optional venue name is blank, and moved hall creation into a tenant-row-locked transaction so concurrent requests cannot exceed the plan's hall limit. Added a regression test for the orphan-hall case.
- app/Livewire/HallChat.php and app/Livewire/VenueChatWidget.php: locked the hall model and server-derived visitor IP/session properties so guests cannot forge another conversation's identity or switch the component to a hidden hall through Livewire property updates. Added a reflection-based regression test for both components.
- Also reviewed PublicGuestRsvp, WhatsAppRsvpManager, NotificationCenter, AttendanceCalendar, FloorPlanDesigner, HallReviews, DirectBooking, CalendarShiftGrid, and KitchenKotBoard for their current access and tenant-scope patterns.

Evidence commits: 7b96c6a, 7ba63ba, ceec92c, 7568408, 61790db, 6969975, d8451f4, 448d9f8, a7b4b05.

Full Phase 1 review remains open; these targeted checks do not mark the remaining unreviewed files complete. CI remains untouched.

## 11. Phase 1 continuation — marketplace stale-state and tenant-context guards (2026-11-28)

- app/Livewire/VendorQuoteRequest.php and app/Livewire/DirectBooking.php: locked the mounted vendor/category model context against Livewire client mutation.
- app/Livewire/VendorQuoteRequest.php: re-resolves the vendor through the marketplace-visible scope under the tenant row lock before creating an RFQ, so a vendor hidden or unapproved after form mount cannot receive a new quote request.
- app/Services/ServiceBookingService.php: direct booking now re-resolves the vendor through the marketplace-visible scope under lock before creating a booking. A stale open booking page cannot book a vendor after marketplace visibility is revoked.
- Added stale-page regression tests to VendorQuoteRequestTest and DirectServiceBookingTest.

Evidence commits: 83a5f92, 999d007, 689ee6a, 08f5acf, 256b434, ea82b62, cb80b28, 48214b7, a270b81, 770ea16, b21fa4f. Intermediate attribute-formatting commits were corrected before the final branch state.

CI remains untouched. Full Phase 1 review remains in progress.

## 12. Phase 1 continuation — media and catalogue input hardening (2026-11-28)

- app/Livewire/HallMediaManager.php: validates cover/gallery media URLs, limits gallery payload counts, and only accepts known video embed hosts or direct video files. This closes a path where a tenant editor could tamper with the public videos array and persist an arbitrary iframe destination. Added focused URL allow-list tests.
- app/Livewire/VendorServicesManager.php: added server-side length/type/range validation for service metadata and requires any selected vendor category to exist and remain active. Added regression tests for inactive categories and oversized metadata.
- app/Livewire/VendorPortfolioManager.php: bounds caption/category metadata, stores uploads under a tenant-specific directory, and deletes the stored file if the database row creation fails.

Evidence commits: 1fa89b5, 0a19393, 4ea94e1, 19d8cfb, dd6e3a3, 4be644a, a02d779, 3caecce, f9e16c0, ec4d8c0, 77e3261, bfdf638.

CI remains untouched. PHPUnit and build verification are still blocked in the local runtime; full Phase 1 review remains open.

## 13. Phase 1 continuation — public submission abuse controls (2026-11-28)

- app/Livewire/HallChat.php and app/Livewire/VenueChatWidget.php: added per-hall, per-user/visitor rate limits for text and voice submissions (10 text messages/minute; 5 voice uploads/10 minutes). Server-derived identity is used in the limiter key.
- app/Livewire/PublicHallDetails.php and app/Livewire/PublicContact.php: consume inquiry/contact rate limits before validation and persistence, so malformed requests also count against the limit; bounded the contact city field.
- Added PublicChatRateLimitTest for visitor chat message writes.

Evidence commits: c4f826a, 255326b, 87876a0, 1883bac, ec28448.

CI remains untouched. Local PHPUnit execution remains unavailable due missing PHP extensions.

## 14. Phase 1 continuation — public page context and vendor bid bounds (2026-11-28)

- app/Livewire/PublicHallDetails.php, PublicVendorProfile.php and PublicGuestRsvp.php: locked the mounted Hall, Tenant/category and invitation model properties so client-side Livewire updates cannot swap the record context after the server-side public visibility/phone lookup. Added reflection regression coverage.
- app/Livewire/VendorQuotesBoard.php: bounded bid values to the supported decimal range, rejects non-finite amounts, and caps proposals at 5,000 characters. Added regression coverage for overflow and oversized proposals.
- app/Livewire/PublicHallDetails.php and PublicContact.php inquiry throttles, plus HallChat/VenueChatWidget message/voice throttles, are recorded in section 13.

Evidence commits: b124a9c, 7f01ea7, df62bca, 7b88ebf, d31055f, 4ee7722, b33e036, 59105f6.

CI remains untouched. Full Phase 1 review remains in progress.

