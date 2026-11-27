# Final Acceptance Report — Riwaj Grand (Independent Re-Audit)

**Branch:** `audit/riwaj-grand-repair-20261126`
**Base / merge-base with `main`:** `df8de43717415f3c7f47d50845f114502f437037`
**GitHub `main` baseline:** `07bc5fe397fbb0fd7de05678d68830fc027a8872`
**Final SHA:** `f83728308a0717380e6c55f13cd471de894b7c3e`
**Audit date:** 2026-11-27
**Tracked files:** 617 (review coverage: `FILE_BY_FILE_REVIEW_MATRIX.md`)
**No changes to `main`; no force-push, reset, deployment or production-data change.**

## 1. CI evidence (authoritative, GitHub-confirmed)

| Run | Head | Conclusion | Details |
|---|---|---|---|
| [45868534419](https://github.com/mohsin-gohar/wedding_halls/actions/runs/45868534419) | `3b819d0` | **FAILURE** | Static quality gates FAILED at **Composer security audit** (3 vulnerable packages: `symfony/http-kernel` CVE-2026-133743, `symfony/mailer` CVE-2026-133751/133755, `symfony/routing` CVE-2026-133746/133754); NPM audit/build/Docker checks skipped. MySQL test suite FAILED at "Run tests". Playwright browser QA **PASSED**. Deploy job SKIPPED (intentionally disabled). |
| [45875899779](https://github.com/mohsin-gohar/wedding_halls/actions/runs/45875899779) | `5330962` | FAILURE | (intermediate head; same audit era) |
| [45880136572](https://github.com/mohsin-gohar/wedding_halls/actions/runs/45880136572) | `f837283` | **SUCCESS** | All jobs green on the final code: static quality gates (PHP lint, Pint, **Composer audit: 0 advisories**, npm audit, Vite build, Docker config/build/smoke), MySQL test suite, Playwright browser QA; deploy intentionally skipped. |

Advisories were fixed by updating the lockfile to patched Symfony releases (`composer update symfony/http-kernel symfony/mailer symfony/routing --with-dependencies`); no advisory was suppressed and no security check removed.

## 2. Test evidence (executed, not estimated)

| Gate | Result | Artifact |
|---|---|---|
| Final full MySQL suite on `f837283`-equivalent code | **605 passed, 3 skipped, 0 failed (3,129 assertions)** | `full_suite_final.out` |
| Prior full suite (before final corrections) | 602 passed, 3 skipped, 0 failed (3,115 assertions) | `full_suite_repair3.out` |
| New onboarding/utility/GMV/refund regression batch | **8 passed** (initial utility expectation corrected, final re-run 1/1) | `correction_tests.out`, `utility_test.out` |
| Composer audit | **0 advisories**, exit 0 | `composer audit --locked` |
| NPM audit / `npm run build` | build exit 0 (CI npm audit passed on final head) | CI run 45880136572 |
| Pint / PHP lint / `git diff --check` | all exit 0 | local + CI |
| Playwright | CI job **PASS** on final head; local rounds 4/6 passed login and responsive suites when rate-limit windows were clear | CI run 45880136572 |

The earlier documented baseline "444 tests / 2,602 assertions" is superseded: it applies to an older head and does **not** cover the later changes. The placeholder in the previous `FINAL_ACCEPTANCE_REPORT.md` is now resolved by the 605/3,129 result above.

## 3. Claim-versus-evidence verification (independent)

| Claim in earlier docs | Verdict | Evidence |
|---|---|---|
| "Full suite green" (old reports, 444 tests) | Stale | Superseded by 605 tests on current head; old number not valid for new changes |
| "Composer audit passes" | FALSE at `3b819d0`, TRUE now | CI run 45868534419 failed; lockfile updated; CI run 45880136572 green |
| "Production ready" | NOT VERIFIED externally | Docker/Redis/mail/host/restore drill not executed locally; deployment job disabled |
| "Cash-only preserved" | TRUE | `CASH_ONLY_PAYMENTS.md`, cash guard layers, cash-only suites green |
| "No critical security issue" | TRUE for app code after fixes | JSON-LD XSS, open redirect, tenant leak, RBAC drift fixed; remaining items are documented policy/infra risks |
| "All business workflows complete" | PARTIAL | Hall refund UI, source-linked correction UI, utility historical backfill remain (see §7) |

## 4. Defects found and fixed (this independent pass)

**Confirmed from CI:** vulnerable Symfony lockfile versions (patched); 24 failing MySQL tests on `3b819d0` (fixed progressively → 0 on final head).

**Product defects:** complimentary onboarding 30-day vs 12-month mismatch → unified on `config('platform.free_access_days')`; utility tracker not linking expense → atomic, idempotent `expense_id` linkage; HOLD value counted as GMV → excluded from analytics GMV/inflow; service refund reversal/wallet/payout/audit defects; concurrent cancellation double refund; API customer read denial; RBAC drift (accountant attendance, floor-staff bookings, HR role assignment); stored XSS in JSON-LD; open redirect after 2FA; cross-tenant walk-in hall leak; status checks in login/reset; undefined `assertExpenseCanBeVoided()`; merge-broken Blade markup; test fixture inconsistencies.

## 5. Browser QA scope

CI Playwright job on `f837283`: **PASS**. Local real-Chromium verification covered public pages and discovered links, responsive widths (320/360/375/390/430/768/1024/1280/1440/1920), guest RBAC rejection, Super Admin / Tenant Owner / Customer login + dashboards, hidden-hall and customer own-record API behavior, POST-only logout (`GET /logout` → 405). Personas exercised via feature tests additionally: Manager, Accountant, Front Desk, Chef, HR Manager, Vendor, support roles.

## 6. Final status by category

| Category | Status |
|---|---|
| PHP lint / Pint / diff hygiene / build / Composer audit | **PASS** |
| Full MySQL suite (isolated DB, clean migrations) | **PASS** — 605 passed, 3 skipped, 3,129 assertions |
| CI (GitHub, final head) | **PASS** — run 45880136572 |
| Playwright browser QA | **PASS** (CI) / partial local (rate-limiter windows) |
| Cross-tenant & RBAC regression tests | **PASS** |
| Financial integrity regression tests | **PASS** |
| Production deployment / staging restore drill / live mail/WhatsApp | **NOT VERIFIED** — owner infrastructure and approval required |
| Docker runtime on this workstation | **NOT RUN** — no daemon; CI container checks passed |
| Load/Lighthouse testing | **NOT RUN** — tooling unavailable |

## 7. Remaining gaps and accepted risks

1. Financial FK `restrictOnDelete()` hardening and unique indexes for booking/PO references need a staging duplicate audit + migration rehearsal before production.
2. Platform-support global API read and Livewire component traffic controls remain **ACCEPTED RISK** pending owner product decision; this Livewire version has no `/livewire/update` route to throttle, so per-component guards remain the control. Wildcard API token self-issuance is now restricted to Super Admin (regression-tested).
3. Upload-limit mismatch across standalone Nginx (32m), PHP (70m) and Livewire (50MB) remains a deployment-config item.
4. Now completed with regression tests: authorized-staff CASH refund UI (calendar booking modal, cancelled bookings only, over-refund guarded, refund voucher), append-only source-linked expense corrections (payroll/PO/utility), historical utility `expense_id` backfill, unified 30-day onboarding, HOLD excluded from GMV, utility expenses no longer silently rewritten.
5. Production deploy / staging restore drill / live mail and WhatsApp delivery / real secrets: **NOT VERIFIED** (owner infrastructure and approval required).
6. No 100% claim: host-level items unverified.

## 8. Deliverables

`COMPLETE_REPOSITORY_AUDIT.md`, `FILE_BY_FILE_REVIEW_MATRIX.md`, `SECURITY_AND_TENANT_ISOLATION_REPORT.md`, `BUSINESS_MODULE_COMPLETION_REPORT.md`, `DATABASE_AND_FINANCIAL_INTEGRITY_REPORT.md`, `TESTING_AND_BROWSER_QA_REPORT.md`, `PRODUCTION_DEPLOYMENT_READINESS.md`, this report. All are committed on the repair branch.

## 9. Continuation update (2026-11-27, HEAD after 732621b)

**Additional safe fixes applied on this pass:**

- Upload-size consistency: standalone Nginx `client_max_body_size` aligned to 64m (matches Docker Nginx and PHP limits). Livewire temporary upload middleware set to `throttle:10,1`.
- New migration `2026_12_23_000001_harden_financial_foreign_keys_and_unique_indexes.php`:
  - Deterministic de-duplication + unique index `purchase_orders_tenant_po_unique` on `(tenant_id, po_number)`.
  - RestrictOnDelete applied to remaining financial FKs on expenses, staff_payrolls, purchase_orders, service_payments, vendor_payouts (where the constraint exists). Irreversible for data safety, matching prior cash-only pattern.
- Wildcard API token issuance remains Super-Admin-only (prior commit). Platform-support global read continues as accepted risk pending owner product decision; no breaking change made.
- Production Redis/queues/scheduler/mail/WhatsApp: configuration examples and deploy scripts reviewed and left intact. Real credentials, Redis daemon, queue workers, and live webhook secrets require owner hosting access and are **NOT VERIFIED**.

**Test gates this session:** Not executed. Sandbox lacks MySQL, Composer, Docker daemon, and package-install permissions. No results fabricated. Prior CI on earlier head remains the last green evidence. Owner must re-run full MySQL suite, Pint, Composer/NPM audits, Vite build, Docker smoke, and Playwright on this head before any production promotion.

**Genuine remaining external blockers:** staging duplicate/orphan audit + migration rehearsal for the new FK/index migration; production Redis/queue/scheduler/mail/WhatsApp secrets and host access; full CI re-run confirmation.

## 10. Final CI verification (2026-11-27, HEAD fd15b25)

**CI run on exact HEAD `fd15b25016abfbd6775cccb2a4ff7b7a38a46b68`:**  
https://github.com/mohsin-gohar/wedding_halls/actions/runs/45949436835

| Job | Result | Notes |
|---|---|---|
| Static quality gates | **FAILURE** | Pint style issue in new migration only (class_definition / fully_qualified_strict_types / unary_operator_spaces / braces_position / not_operator_with_successor_space). PHP lint passed. Composer/NPM/Docker checks skipped after Pint fail. |
| MySQL test suite | **SUCCESS** | Full suite green (migrations including the new financial FK/index migration applied cleanly; tests passed). |
| Playwright browser QA | **SUCCESS** | Browser suite green. |
| Deploy | SKIPPED | Intentionally disabled. |

**Style fix applied in follow-up:** migration rewritten for Pint compliance; booking-reference unique index `(tenant_id, booking_reference)` added after deterministic de-duplication (column already existed via `2026_10_10_000002`). Migration remains irreversible for data safety; recovery is full restore from backup + re-seed of de-duplicated references if needed. Staging rehearsal still required before production.

**External blockers unchanged:** Redis/queue/scheduler/mail/WhatsApp live credentials and host access; staging forward/rollback rehearsal of the irreversible migration; full green CI on the style-fixed head.

## 11. Style-fixed CI (2026-11-27, HEAD bc3f64a)

**CI run:** https://github.com/mohsin-gohar/wedding_halls/actions/runs/45950831948  
**HEAD:** `bc3f64af9bce27570f59713dd116e692f4f58886`

| Job | Status (as of last poll) | Evidence |
|---|---|---|
| Static quality gates | **SUCCESS** | PHP lint, Pint, Composer audit (0 advisories), NPM audit, Vite production build, Docker config/build/smoke all green. |
| MySQL test suite | IN PROGRESS | Migrations (including new FK/unique indexes) succeeded; tests running. Prior run on fd15b25 was full success. |
| Playwright browser QA | IN PROGRESS | Browser suite running. Prior run on fd15b25 was full success. |
| Deploy | SKIPPED | Intentionally disabled. |

**Migration safety:** New migration applied cleanly in CI MySQL containers (forward path verified). Irreversible by design; recovery strategy is full database restore from backup plus re-application of deterministic de-duplication if any legacy references were rewritten. Staging rehearsal with real data still required before production.

**Remote HEAD verified:** `bc3f64af9bce27570f59713dd116e692f4f58886` on `origin/audit/riwaj-grand-repair-20261126`.

## 12. Final production-readiness pass (2026-11-27, HEAD cb6ad80)

**CI run on exact latest HEAD `cb6ad80ea7bc722041a1ae8809b5e0b43c9dcd0b`:**  
https://github.com/mohsin-gohar/wedding_halls/actions/runs/45951164982

| Job | Status (as of last poll) | Notes |
|---|---|---|
| Static quality gates | In progress (Pint, Composer audit, NPM audit, Vite build already green; Docker validation running) | All prior static checks green on this head |
| MySQL test suite | In progress | Migrations (including 2026_12_23 financial FK + unique indexes) succeeded; tests running. Prior full suite on fd15b25 was green. |
| Playwright browser QA | In progress | Suite running. Prior full suite on fd15b25 was green. |
| Deploy | SKIPPED | Intentionally disabled |

**Financial migration final review:**  
`2026_12_23_000001_harden_financial_foreign_keys_and_unique_indexes.php`  
- Deterministic de-duplication of legacy `(tenant_id, po_number)` and `(tenant_id, booking_reference)` duplicates before adding unique indexes.  
- `restrictOnDelete()` applied to remaining financial FKs where constraints exist.  
- Forward path verified in CI MySQL containers. Irreversible by design.  
- **Staging rehearsal commands (owner action required):**  
  1. Take encrypted backup: `AGE_RECIPIENT=... RCLONE_REMOTE=... ./deploy/backup-docker.sh`  
  2. Restore to isolated staging DB ending in `_restore_drill`: `AGE_IDENTITY=... RESTORE_DB_HOST=... RESTORE_DB_DATABASE=..._restore_drill RESTORE_CONFIRM=..._restore_drill ./deploy/restore-drill.sh /path/to/backup.sql.gz.age`  
  3. On staging only: `php artisan migrate --force` and verify unique indexes + restrict FKs.  
  4. Recovery: restore from the pre-migration backup. Never run on production until staging drill passes.

**Production configuration (reviewed, not executed):**  
Redis, queue workers (`supervisor-riwaj-grand-worker.conf`), scheduler (`schedule:run` cron), mail (SMTP placeholders in `.env.production.example`), and WhatsApp Cloud API (blank tokens keep outbound in log/sandbox mode) are documented. Live delivery and Redis daemon require owner hosting access and real secrets. No credentials were present in this environment.

**Browser QA:** Covered by CI Playwright job (public pages, responsive widths, role logins, RBAC rejection). Full manual multi-role responsive crawl of every form/button remains owner-side if additional coverage is desired.

**Genuine external blockers remaining:**  
- Staging restore-drill + migration rehearsal with real data (scripts provided).  
- Production Redis/queue/scheduler/mail/WhatsApp live secrets and host access.  
- Confirmation of final CI conclusion on this exact HEAD once the in-progress run completes.  
- Any production deploy or merge to `main`.

No production data modified. No merge or deploy performed. No test results fabricated.
