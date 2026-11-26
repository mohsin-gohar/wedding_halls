# Riwaj Grand — Master Audit & Remediation TODO

**Baseline branch:** `ai/riwaj-grand-hardening-20261124`  
**Baseline commit:** `8b8ad4975b2a3c9eadf0ebbbf8edc1d7b9146441`  
**Repository default branch:** `main` at `07bc5fe397fbb0fd7de05678d68830fc027a8872`  
**Audit date:** 2026-11-26  
**Purpose:** One master backlog for the complete code, workflow, security, UI, QA and production-readiness review. This is a working checklist, not a claim that every item is already defective.

## Rules of execution

- Work only on a dedicated audit/remediation branch. Never rewrite `main`, force-push, merge, deploy, or change production data without explicit approval.
- Preserve existing business behaviour and historical records. Use focused fixes and regression tests; avoid destructive migrations and broad rewrites.
- Every task must end with evidence: code diff, regression test, command output, browser proof, or owner-controlled infrastructure evidence.
- A feature is not complete merely because a route or button exists. Verify its full workflow, permissions, validation, persistence, failure states and tenant boundary.
- Distinguish **repository-verifiable** work from **owner/infrastructure-dependent** work. Never mark an external gate complete using mocks.
- Keep this file as the single task ledger. Update checkboxes and evidence links as work is completed.

## Current repository facts captured at baseline

- Laravel 12 / PHP 8.2+, Livewire 4, Vite 6, Tailwind 4, Alpine, MySQL 8, Spatie Permission.
- Recursive repository tree contains **582 files / 62 directories** at the baseline commit.
- Major code inventory: 61 Livewire components, 66 models, 10 services, 70 migrations, 76 feature tests, 3 unit-test files, 2 browser-test files, 82 Blade view files.
- Product scope in README: public venue marketplace, vendor marketplace/RFQ/bids/awards/service bookings, customer event workspace, vendor ERP/finance, hall ERP, HR/payroll/inventory/kitchen, 21 roles / 110 permissions, REST API, WhatsApp RSVP.
- README declares cash-only payments; all new payment writes must remain manual cash. Do not reintroduce online gateways.
- Existing reports contain different historical test counts and feature claims. `DOCS_STATUS.md` says `FINAL_REPOSITORY_AUDIT.md`, `IMPLEMENTATION_COMPLETION_REPORT.md`, and `PRODUCTION_READINESS_REPORT.md` are authoritative; historical documents must not be treated as current evidence.
- The latest hardening-branch changes add pricing validation, API read permissions, explicit WhatsApp RSVP intent, and RFQ bid confidentiality/lifecycle checks. Their new tests have not yet been shown passing on the latest head.
- GitHub currently has an open draft PR #4 whose head is `ops/production-readiness-20261124`, not this audit branch. The hardening branch is **107 commits ahead and 1 behind** `main`; do not merge/rebase blindly.

## P0 — Immediate security and data exposure

- [ ] **SEC-001 — Tighten booking document authorization.** `DocumentController::authorizeBookingAccess()` currently permits any same-tenant user to read booking agreements, quotations and invoices. Require the appropriate booking/document permission in addition to tenant ownership; retain Super Admin global access and a customer’s own-booking access.
- [ ] **SEC-002 — Tighten payment receipt authorization.** `authorizePaymentAccess()` currently permits any same-tenant user to read receipts. Require explicit financial permission (at minimum `payments.view`) plus tenant match; retain Super Admin and the linked customer’s own receipt.
- [ ] **SEC-003 — Add document authorization regression suite.** The expected `tests/Feature/DocumentAuthorizationTest.php` path returned 404 on this branch. Create coverage for guest, unrelated tenant, same-tenant owner, manager, accountant, front desk, chef/floor/service staff, platform finance, moderator, support, Super Admin and customer owner. Assert both HTML and PDF download paths.
- [ ] **SEC-004 — Audit every document endpoint.** Review KOT, salary slip and vendor payment proof against least-privilege permissions; test private storage, traversal, missing files, cross-tenant IDs and PDF/HTML parity.
- [ ] **SEC-005 — Full RBAC route/action audit.** Compare all 21 roles and 110 permissions against route middleware, Livewire actions, API abilities, policies and document controllers. Find missing checks, overly broad role aliases and permission drift.
- [ ] **SEC-006 — Tenant isolation sweep.** Test every read/write path for cross-tenant IDOR, null tenant IDs, global scopes, nested relationships, exports, search, notifications, chats, uploads and background jobs.
- [ ] **SEC-007 — Sensitive data exposure sweep.** Search logs, exceptions, flash/session payloads, API resources, exports and browser HTML for passwords, tokens, private file paths, customer PII and payment proofs.
- [ ] **SEC-008 — Authentication and account lifecycle.** Verify login throttling, registration, email verification, password reset, session regeneration, logout, inactive/suspended accounts, 2FA/recovery codes and API token revocation/expiry.

## P1 — Booking, money and marketplace correctness

- [ ] **DOM-001 — Hall availability and booking concurrency.** Verify all slot types, FULL_DAY, buffer rules, timezone/date boundaries, holds, cancellations, rebooking, concurrent requests and unique-index behaviour on MySQL 8.
- [ ] **DOM-002 — Booking lifecycle.** Verify inquiry → pending/hold → confirmed → completed/cancelled/expired transitions, audit history, customer notifications, refunds policy (cash-only), and who may perform each transition.
- [ ] **DOM-003 — RFQ lifecycle.** Verify target-vendor visibility, bid edits, closing, deadline races, award locking, losing bids, customer acceptance/rejection, cancellation and idempotency.
- [ ] **DOM-004 — Vendor bid confidentiality.** Run the newly added competitor-bid isolation tests on latest branch head; confirm no Blade/Livewire payload, eager-loaded relation, event or API response leaks competitor identity or amount.
- [ ] **DOM-005 — Service booking lifecycle.** Verify direct booking, RFQ award, confirmation, completion, cancellation, disputes and cross-tenant ownership end to end.
- [ ] **FIN-001 — Cash-only policy audit.** Prove all new payment entry points reject non-cash methods; customers can only submit pending records; authorized staff alone verify physical cash. Preserve legacy historical method labels.
- [ ] **FIN-002 — Financial arithmetic and ledger.** Review every monetary calculation for integer paisa/decimal precision, rounding, negative values, overpayment, partial payment, balance, commissions, wallet, payouts and reconciliation.
- [ ] **FIN-003 — Idempotency and concurrency.** Test duplicate requests, replay with changed amount/type, simultaneous verification, payout reservation, rollback and audit-log uniqueness.
- [ ] **FIN-004 — Subscription lifecycle.** Verify trial, complimentary onboarding, activation, expiry, grace period, suspension, renewal, manual payment approval, scheduler execution and tenant access lock.
- [ ] **VND-001 — Vendor service catalogue.** Run latest pricing validation tests for blank names, NaN/INF, negative amounts, range ordering, required base price and currency normalization.
- [ ] **VND-002 — Vendor portfolio, documents and locations.** Verify MIME/extension/size validation, private/public storage boundaries, deletion, ownership and multi-location scoping.
- [ ] **VND-003 — Vendor finance.** Verify customer payment submission, cash verification, ledger settlement, commission, wallet, payout request/approval/payment and role segregation.
- [ ] **VND-004 — Vendor onboarding and moderation.** Verify category-driven registration, duplicate prevention, KYC/document review, activation/suspension, marketplace visibility and owner invitation safety.
- [ ] **CUS-001 — Customer workspace.** Verify multi-function events, task checklist, vendor shortlist, budgets, booking links, ownership, sharing/privacy and support requests.
- [ ] **MSG-001 — WhatsApp RSVP.** Run latest webhook/simulator tests; verify signature validation, duplicate webhook idempotency, explicit YES/NO intent, ambiguous-message clarification, bounded guest counts, rate limits, provider timeout/failure handling and no PII/token logging.
- [ ] **MSG-002 — Notifications.** Verify in-app/email/WhatsApp templates, queue retries, failed jobs, deduplication, tenant routing, opt-outs and delivery status. External delivery remains a separate live test.

## P1 — Hall ERP, HR and operations

- [ ] **ERP-001 — Calendar and shifts.** Verify month/week/day/agenda expectations, shift configuration, drag/drop if present, availability consistency, exports, filters and booking conflict visibility. Identify dead schema or UI promises.
- [ ] **ERP-002 — Customer CRM.** Verify create/update/search/merge, duplicate phone/email handling, tenant scoping, consent and export.
- [ ] **ERP-003 — Staff and roles.** Verify invitation, role assignment, least privilege, deactivation, tenant transfer, password handover and audit history.
- [ ] **ERP-004 — Attendance and leave.** Verify check-in/out, duplicate punches, overnight shifts, holidays, weekly offs, leave approval, corrections, timezone and payroll integration.
- [ ] **ERP-005 — Payroll.** Verify draft → approved → paid locking, attendance deductions, overtime, advances, payslips, rounding, payment atomicity and separation of duties.
- [ ] **ERP-006 — Inventory and procurement.** Verify stock movement, negative stock prevention, decimal quantities, purchase orders, supplier lifecycle, receiving, adjustment and audit logs.
- [ ] **ERP-007 — Kitchen/KOT.** Verify KOT creation, status transitions, duplicate prevention, customer denial, operational role access, printing and booking linkage.
- [ ] **ERP-008 — Floor plans and seating.** Verify hall ownership, booking linkage, seat/table capacity, saved layouts, legacy-plan restrictions and concurrent edits.
- [ ] **ERP-009 — Expenses and utility/fuel tracking.** Verify category, amount, attachment, approval, reports, date filters and tenant isolation.

## P1 — Platform, APIs and integrations

- [ ] **PLAT-001 — Super Admin dashboard.** Verify every KPI against database truth, filters, exports, moderation, subscription controls, vendor approval and audit trails.
- [ ] **PLAT-002 — Support center.** Verify ticket create/reply/assign/close, attachment security, customer/vendor/platform visibility and SLA behaviour.
- [ ] **API-001 — API contract audit.** Compare every `/api/v1` route with API docs, bearer abilities, role permissions, tenant/customer scoping, validation, response schemas and status codes.
- [ ] **API-002 — API negative/security tests.** Test unauthenticated, expired/revoked token, insufficient ability, wrong tenant, wrong customer, suspended tenant, malformed UUID, mass assignment and throttling.
- [ ] **API-003 — Latest API authorization regression tests.** Run new Finance/Moderator/Support booking and service-booking read-scope tests on current head.
- [ ] **INT-001 — WhatsApp integration configuration.** Validate Meta webhook verification, app secret/signature, API version, phone-number ID, token expiry and safe operational diagnostics.
- [ ] **INT-002 — Email integration.** Validate SMTP configuration, sender identity, queue delivery, TLS, retry and bounce handling with an owner-approved test mailbox.
- [ ] **INT-003 — File storage.** Verify public media vs private documents, local and S3-compatible disks if supported, signed/download URLs, upload limits, cleanup and backup coverage.

## P2 — UI/UX, accessibility and browser behaviour

- [ ] **UI-001 — Route and page inventory.** Generate the complete named-route/page matrix, including public, customer, tenant owner, each staff persona, vendor and platform roles.
- [ ] **UI-002 — Interactive controls audit.** Exercise every button, link, dropdown, modal, tab, filter, search, form, pagination, upload and destructive confirmation; ensure no dead controls or fake success states.
- [ ] **UI-003 — Persona browser journeys.** Run real browser workflows for guest, customer, tenant owner, manager, accountant, front desk, chef, floor staff, service staff, vendor owner/manager, platform support, finance, moderator and Super Admin.
- [ ] **UI-004 — Responsive coverage.** Expand current public-only responsive proof to authenticated portals and high-risk ERP pages at 320, 360, 390, 768, 1024, 1440 and 1920 widths.
- [ ] **UI-005 — Browser console/network proof.** Fail on uncaught page errors, console errors, failed first-party requests, broken images, missing assets, hydration/Livewire errors and unexpected 4xx/5xx.
- [ ] **UI-006 — Accessibility.** Check keyboard navigation, focus visibility, labels, form errors, contrast, semantic headings, dialogs, screen-reader names and reduced-motion behaviour.
- [ ] **UI-007 — UX consistency.** Review loading/empty/error/success states, destructive actions, validation feedback, pagination, mobile navigation, print layouts and Urdu/English copy consistency.
- [ ] **UI-008 — Design system.** Verify reusable tokens/components and prevent inconsistent one-off styling; preserve Riwaj Grand luxury visual identity without harming usability.

## P2 — Quality, performance and maintainability

- [ ] **QA-001 — Run full latest-head CI.** Execute PHP syntax, Pint, Composer audit, npm audit, Vite build, route/cacheability checks and complete MySQL 8 test suite. Capture exact commit SHA and workflow URLs.
- [ ] **QA-002 — Run browser suite on latest head.** Execute Playwright with MySQL seeded data and upload screenshots, traces and report artifacts.
- [ ] **QA-003 — Test reliability.** Run critical concurrency/idempotency tests repeatedly and in parallel; identify flaky tests and order dependence.
- [ ] **QA-004 — Migration audit.** Review all 70 migrations for duplicate/contradictory schema, rollback safety, FK/index coverage, MySQL 8 compatibility and clean-install reproducibility.
- [ ] **QA-005 — Seeder/factory audit.** Verify idempotent seeders, role/permission completeness, demo-only credentials, realistic persona fixtures and no production secrets.
- [ ] **QA-006 — Static analysis.** Add/verify PHPStan or Larastan baseline, dead-code detection, dependency/license checks and secret scanning where compatible.
- [ ] **PERF-001 — Database query audit.** Inspect N+1, missing indexes, unbounded lists, expensive dashboard aggregates, search, pagination and query plans using representative MySQL data.
- [ ] **PERF-002 — Frontend performance.** Measure production bundle, route payloads, image sizes, caching, lazy loading, Core Web Vitals and Lighthouse on representative pages.
- [ ] **PERF-003 — Load and resilience.** Establish baseline and load tests for availability, booking contention, marketplace search, login and API rate limits.
- [ ] **PERF-004 — Caching correctness.** Audit tenant-aware cache keys, invalidation, stale dashboard values, lock store consistency and multi-instance behaviour.
- [ ] **CODE-001 — Large component review.** Review oversized Livewire components for duplicated business logic, transaction boundaries, validation and service extraction; refactor only where tests protect behaviour.
- [ ] **CODE-002 — Dependency lifecycle.** Verify supported PHP/Node/Laravel versions, lockfile reproducibility, Composer/npm vulnerabilities and upgrade policy.

## P1/P2 — Deployment and production operations (external gates are not code-only)

- [ ] **OPS-001 — Deployment target decision.** Confirm Hostinger Shared/Cloud or Docker VPS as the actual target; validate instructions against that exact environment.
- [ ] **OPS-002 — Environment and secrets.** Validate production `.env` template, APP_KEY generation, APP_DEBUG=false, HTTPS/cookies, DB least privilege, Redis, mail, WhatsApp and secret rotation.
- [ ] **OPS-003 — Database migration rehearsal.** Run clean install and upgrade on isolated staging MySQL 8; prove backups and rollback/recovery plan before production migration.
- [ ] **OPS-004 — Queue and scheduler.** Verify worker process, retry/backoff, failed-job alerting, scheduler cron, overlapping-run protection and restart behaviour.
- [ ] **OPS-005 — TLS/DNS/security headers.** Verify real domain DNS, apex/www, certificate renewal, HSTS, trusted proxy, HTTPS redirects, CSP and security headers.
- [ ] **OPS-006 — Backup and restore drill.** Create encrypted DB + uploaded-file backup, copy off-site, verify checksum, restore into a separate staging database/storage target and record measured recovery time.
- [ ] **OPS-007 — Monitoring and incident response.** Configure uptime, error tracking, queue depth, disk/DB health, alert routing, log retention, incident contacts and runbooks.
- [ ] **OPS-008 — Live email/WhatsApp smoke.** Only with owner-approved recipient and credentials; verify delivery, provider errors, webhook round-trip and opt-out handling.
- [ ] **OPS-009 — Production smoke test.** After explicit deployment approval, verify public discovery, auth, booking, cash receipt, private documents, vendor flow, admin controls, scheduler and rollback readiness.
- [ ] **OPS-010 — Disaster recovery and security sign-off.** Record access review, secret rotation, restore evidence, RPO/RTO, incident response and final owner acceptance.

## P2 — Documentation and release hygiene

- [ ] **DOC-001 — Reconcile audit documents.** Remove contradictory active claims; clearly banner historical snapshots and point to the one master ledger.
- [ ] **DOC-002 — Reconcile README and feature inventory.** Ensure every advertised feature is wired, permissioned and verified; classify partial, unavailable and external-dependent features honestly.
- [ ] **DOC-003 — API documentation parity.** Ensure every endpoint, permission, request/response and cash-only rule matches implementation.
- [ ] **DOC-004 — Operator runbooks.** Document install, deploy, rollback, backups, restore, scheduler, queue, monitoring, secrets and support escalation.
- [ ] **DOC-005 — Release process.** Define versioning, changelog, migration notes, PR review, required CI checks and release acceptance.
- [ ] **DOC-006 — Branch/PR reconciliation.** Compare the 107-ahead/1-behind hardening branch with `main` and the separate PR #4 head; identify unique commits and overlapping changes, then propose a safe integration plan. No automatic merge/rebase.
- [ ] **DOC-007 — Final acceptance report.** Publish exact commit, task ledger status, tests and browser artifacts, unresolved risks, external blockers and owner sign-off.

## Completion gates

A task may be checked only when its acceptance criteria are met and evidence is recorded. A phase may close only when all its tasks are complete or explicitly classified as an owner-approved non-goal. The project may be called **repository release-ready** only after all repository-verifiable P0/P1 gates pass. It may be called **production-ready** only after the external OPS gates and owner acceptance are complete.

**Current state:** backlog created from repository structure, current README/config/routes/tests, authoritative status docs and targeted code inspection. This is not yet a full line-by-line review of all 582 files; the remaining tasks deliberately track that work rather than pretending it has already happened.


## Work log — 2026-11-26 (verification pending)

The following remediation work has been committed to the audit branch. These entries remain **unchecked** until the full latest-head MySQL test suite and browser/CI gates pass.

- Document access now checks tenant scope plus `documents.view`, `invoices.view`, or `payments.view`; platform finance has cross-tenant access only for its explicit financial permissions. KOT requires `kitchen.view`; salary slips require `payroll.view` except the employee's own slip. Payment proofs are restricted to platform finance with `payments.view`, tenant owners/accountants with `payments.view`, or the paying customer.
- Added `tests/Feature/DocumentAuthorizationTest.php` for agreement, quotation, invoice, receipt, PDF, KOT, salary slip, platform-finance scope and cross-tenant denial.
- Staff payroll ERP now requires `payroll.manage`; staff management requires `staff.manage`; expense ERP requires `expenses.view/manage`; inventory ERP requires `inventory.view/manage`. Sensitive vendor boards re-check their view permission during render as well as mount.
- HR managers can manage operational employee roles but cannot provision finance, inventory-manager, general-manager or HR-manager privileges. Target-role checks protect edit/archive actions.
- Removed the destructive permanent staff purge path and its UI controls. Staff removal now uses the existing soft-archive workflow; payroll, attendance, leave and correction rows remain in the database, and HR relations resolve soft-deleted users for historical records.
- Added regression coverage in `PayrollPaymentIntegrityTest`, `ErpStaffPayrollAndInventoryTest`, and `ExpenseAuthorizationTest` for denied role escalation, retained paid payroll history, inventory permission boundaries, expense role boundaries and payroll access.
- CI workflow push trigger was widened from `main` only to all branches so future branch pushes can run the same quality, MySQL and Playwright gates. GitHub Actions has not yet reported a run for these commits; no test pass is claimed.

**Verification gate:** run PHP lint, Pint, full MySQL suite and Playwright against the exact latest branch head. Review any failures and correct them before checking these tasks complete.
