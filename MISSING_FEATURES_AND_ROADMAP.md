> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# MISSING FEATURES & ROADMAP — Riwaj Grand

**Date:** 2026-11-16

## Phase 2 remainder — HR/calendar
| Feature | Status | Priority |
|---|---|---|
| Visual monthly attendance calendar (employee × day grid) | **Implemented this cycle** — 7 tests, browser verified | — |
| Leave overlay + holiday calendar + weekly offs | Implemented in `AttendanceCalendar`; **tenant-configurable weekly offs** now in UI (`weekly_off_days` on tenants) | — |
| Employee self check-in/check-out, correction approval workflow | Self-service still missing; **correction history implemented** (`attendance_corrections` table + `writeCorrection()` with reason) | Medium |
| Hold expiry column + scheduled release | **Implemented** — `ExpireBookingHoldsCommand`, scheduler `bookings:expire-holds` hourly, audit-logged | — |
| Configurable venue shifts | **Implemented** — `app/Support/ShiftWindows.php` (shift timings + buffer) | — |
| Calendar week/day/agenda views | **Implemented + browser verified** — MONTH / WEEK / DAY / AGENDA switch, distinct ranges; search/print/export still missing | Low |

## Phase 3 — payroll / finance
| Feature | Status |
|---|---|
| Attendance-aware deductions + approval + finalization lock | **Implemented this cycle** — 6 tests, browser verified |
| **DRAFT→APPROVED→PAID enforcement + atomic payment + PAID immutability** | **Fixed in 17 Nov repair cycle** — 4 tests (`PayrollPaymentIntegrityTest`), browser verified end-to-end |
| **360px payroll horizontal overflow** | **Fixed** — layout constrained (min-w-0, flex-wrap, scroll-contained tables); 0 page-breakers at 360px on 6 ERP pages (iframe methodology) |
| Salary source integrity (no invented defaults) | **Implemented** — compile refuses rows without explicit base salary / policy salary and lists them; never invents amounts (browser-verified message) |
| Daily wage / hourly wage, configurable bonus UI, advances/loans ledger | Missing |
| Tax/provident rules | Deliberately not invented (jurisdiction-specific business decision) |
| Dispute management entity | Missing |
| Coupon/promotion engine | Missing |
| Referral/loyalty programmes | Missing |
| Review moderation screen | Missing |

## Phase 4/5 — platform
- Monitoring, alerting, backup automation + tested restore, error tracking.
- CI/CD with working Pint, load tests, Lighthouse, remaining browser personas + mobile.
- Live gateway sandbox (credentials required), webhook replay testing.
- Multi-currency architecture, tax configuration, localization (Urdu/English), PWA readiness.

## Rule applied
Do not add features merely to increase counts; prioritize security, operational correctness, financial integrity, customer value. Every feature must connect DB + backend + authorization + validation + UI + audit + tests.

## Commands / evidence
See `ULTIMATE_ENTERPRISE_AUDIT.md` inventory and `COMPLETE_FEATURE_INVENTORY.md`.
