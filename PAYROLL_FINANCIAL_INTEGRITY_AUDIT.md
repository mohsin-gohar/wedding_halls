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

## Changes this cycle
- Attendance writes (source of OT and future deductions) are now transactional, explicit, and validated — improving the reliability of payroll's only attendance input.
- Approved leave now produces `ON_LEAVE` attendance rows, enabling future unpaid-leave deduction logic.

## Open (Phase 3)
Attendance-aware deduction engine with configurable policies; approval state (`APPROVED` exists in schema but no code path sets it); lock source attendance period after finalization; payslip regeneration versioning; payment gateway sandbox test.

## Verification
`php artisan test tests/Feature/ErpStaffPayrollAndInventoryTest.php` → 9 passed (payroll generation, auto-expense, salary-slip route).
