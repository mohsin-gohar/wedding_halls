# COMPLETE FEATURE INVENTORY — Riwaj Grand

**Date:** 2026-11-16 · Evidence-based classification (source + tests + browser)

| Module | Classification | Evidence / note |
|---|---|---|
| Hall booking ERP (legacy `bookings`) | Fully implemented & verified | `BookingService` atomic reservation, tests + browser |
| Booking calendar — month grid | Implemented; conflict visibility fixed this cycle | `CalendarShiftGrid`, `CalendarIntegrityTest` (4 pass), browser verified |
| Booking calendar — week/day/agenda/export | Missing | Only `prevMonth/nextMonth/goToToday` exist |
| Configurable venue shifts (DB) | Backend-only / dead schema | `shift_definitions`, `venue_shifts` unused; duplicate migrations; slug mismatch |
| Hold expiry + auto-release | Missing | No expiry column; no scheduler; legend claim removed |
| Multi-vendor marketplace (RFQ→bid→award→booking→commission→wallet→payout) | Implemented & verified | EnterpriseExpansionTest etc. |
| Vendor media/portfolio | Implemented; uploads hardened this cycle | MIME+extension allow-lists, FileUploadSecurityTest |
| Customer event workspace / support center | Implemented & verified | Feature tests |
| White-label vendor sites | Implemented & verified | Feature tests |
| RBAC (110 permissions / 21 roles) | Implemented & verified at runtime | `PermissionMatrix::allPermissions()/allRoles()` |
| Two-factor authentication | Implemented & verified | google2fa + QR + recovery; prior cycle |
| REST API `/api/v1` + tokens | Implemented & verified | 401/403/token-revocation tests |
| Staff attendance daily register | Implemented; hardened this cycle | AttendanceCoreRulesTest (7 pass), browser verified |
| Leave management | Implemented; hardened this cycle | SoD + overlap + ON_LEAVE sync |
| Visual attendance/leave calendar, holidays, weekly offs | Implemented this cycle — `AttendanceCalendar`, 7 tests, browser verified | Only monthly **summary table** exists |
| Payroll generation + payslips | Implemented + Phase 3 hardened | Attendance-aware deductions, approval, finalization lock, breakdown payslips — 6 tests, browser verified |
| Inventory / expenses / kitchen KOT | Implemented | ErpStaffPayrollAndInventoryTest |
| File downloads (agreements, receipts, salary slips, payment proof) | Implemented + authorized | DocumentController |
| Notifications (in-app/email/WhatsApp) | Partial | Queues exist; live WhatsApp requires config |
| Gateway integrations | Requires external credentials | No live sandbox round-trip |
| Backup/restore, monitoring, error tracking | Missing | Documented blockers |
| Disputes, coupons, referrals, review moderation | Missing | Roadmap |
| Public SEO/design system | Implemented (premium tokens in use) | Browser verified homepage |

**Rule applied:** no module marked "complete" merely because a page renders or a button exists.
