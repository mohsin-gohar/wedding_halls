# MISSING FEATURES & ROADMAP — Riwaj Grand

**Date:** 2026-11-16

## Phase 2 remainder — HR/calendar
| Feature | Status | Priority |
|---|---|---|
| Visual monthly attendance calendar (employee × day grid) | **Implemented this cycle** — 7 tests, browser verified | — |
| Leave overlay + holiday calendar + weekly offs | Implemented in `AttendanceCalendar`; shift roster still missing | Medium |
| Employee self check-in/check-out, correction approval workflow, old/new audit | Missing | High |
| Hold expiry column + scheduled release | Missing | Medium |
| Configurable venue shifts (use existing shift tables, resolve slug mismatch) | Backend-only/dead schema | Medium |
| Calendar week/day/agenda views, search, print/export | Missing | Medium |

## Phase 3 — payroll / finance
| Feature | Status |
|---|---|
| Attendance-aware deductions (absence, late, half-day, paid/unpaid leave) | Missing |
| Configurable OT rates, allowances, advances, loans | Partial (flat OT rate 250) |
| Payroll APPROVED state + finalization lock + PAID-record edit guard | Missing |
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
