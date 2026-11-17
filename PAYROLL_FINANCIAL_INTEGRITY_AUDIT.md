# PAYROLL & FINANCIAL INTEGRITY AUDIT — Riwaj Grand

**Date:** 2026-11-16

## Inspected
`app/Livewire/StaffPayroll.php`, `app/Models/StaffPayroll.php`, `app/Services/ServiceBookingService.php`, `app/Services/BookingService.php`, `app/Livewire/VendorFinanceBoard.php`, migration `2026_10_11_000001:85-94`, `DocumentController::salarySlip()`.

## Findings
| Area | State |
|---|---|
| Money columns | decimal(12,2) PKR throughout; `calculateNet()` uses `round(...,2)` (`StaffPayroll.php:52-57`) — decimal-safe persistence, float intermediate (accepted pattern in green tests) |
| Payment processing | `BookingService::recordPayment` inside `DB::transaction` (`:194`); idempotency via payment reference checks |
| Vendor commission | `ServiceBookingService.php:303-327`: tenant rate → platform setting default 5%; wallet ledger + payout bounded by balance (feature-tested) |
| Refund workflow | `ServiceBookingService::refund` reverses wallet ledger and sets `REFUNDED` — tested |
| Payroll ↔ attendance | **Partial**: only `overtime_hours` sum is read (`StaffPayroll.php:102-105`). No absence, late, half-day, paid/unpaid leave deductions. OT rate hardcoded `250.00` (`:116`) |
| Payroll finalization | DRAFT → PAID guarded against duplicate payment (`:260-265`), but `updatePayroll()` (`:198-235`) has **no status guard** — a PAID record's salary can be edited |
| Payslips | HTML/PDF generated on demand, authorized for owner/manager/accountant **and the employee themselves** (`DocumentController.php:205-209`) |
| Auto-posting | `markAsPaid()` creates `Expense` STAFF_WAGES + `AuditLog PAYROLL_PAID` (`:274-293`) |
| Gateway live round-trips | **Blocked** — no merchant sandbox credentials; no false claim made |

## Changes this cycle — Phase 3 implemented
- **Attendance-aware engine**: `StaffPayroll::generateMonthlyPayroll()` now counts PRESENT/ABSENT/LATE/HALF_DAY, sums OT, expands approved **UNPAID** leave days, and derives deductions from a **tenant-configurable `payroll_policies` table**. All money is `decimal(12,2)`, net via `calculateNet()` with `round(...,2)`; nothing trusts frontend totals.
- **New breakdown columns** on `staff_payrolls`: present/absent/late/half/unpaid-leave days, overtime_pay, each deduction, bonuses, advances, other_deductions, approved_by/at.
- **Approval + finalization**: `markAsApproved()` (DRAFT→APPROVED) and `reopenPayroll()` (authorized reopen), both audited; `updatePayroll()` rejects PAID/APPROVED; `markAsPaid()` keeps duplicate-payment guard + auto-expense posting.
- **Attendance lock**: `StaffAttendance::periodLocked()` blocks daily save, quick edit and leave approval for a month whose payroll is APPROVED/PAID. Reopening payroll unlocks it.
- **Payslips** now show the full attendance-derived breakdown.
- `tests/Feature/PayrollAttendanceIntegrationTest.php` → **6 passed (17 assertions)**; related suites → 26 passed (180 assertions).
- Browser QA (owner): Calculation Policy, compile payroll, Approve via confirm → "APPROVED (LOCKED)", salary slip with new breakdown — 0 console errors.

## Remaining (honest)
- Daily-wage/hourly-wage employee types, configurable bonus/allowance UI, advances/loans ledger, jurisdiction-specific tax/provident rules (deliberately not invented), payslip versioning, gateway sandbox.

## Verification
`php artisan test tests/Feature/ErpStaffPayrollAndInventoryTest.php` → 9 passed (payroll generation, auto-expense, salary-slip route).
