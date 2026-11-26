<?php

namespace App\Livewire;

use App\Livewire\StaffAttendance as AttendanceComponent;
use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\PayrollPolicy;
use App\Models\StaffAttendance;
use App\Models\StaffLeave;
use App\Models\StaffPayroll as PayrollModel;
use App\Models\User;
use App\Support\CashOnlyPayments;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Staff Payroll & Salary Slips — Riwaj Grand ERP'])]
class StaffPayroll extends Component
{
    public string $selectedMonth = '';

    public string $selectedStaffId = 'ALL';

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    // Edit Payroll Modal
    public bool $showEditModal = false;

    public ?string $editingPayrollId = null;

    public string $editingEmployeeName = '';

    public float $editBasicSalary = 0;

    public float $editOvertimeHours = 0;

    public float $editOvertimeRate = 250;

    public float $editAllowances = 0;

    public float $editDeductions = 0;

    public float $editNetSalary = 0;

    public string $editPaymentMethod = 'CASH';

    public string $editNotes = '';

    public bool $updateBaseSalaryOnProfile = false;

    // Mark as Paid Modal
    public bool $showPayModal = false;

    public ?string $payingPayrollId = null;

    public string $payMethod = CashOnlyPayments::CASH;

    public string $payDate = '';

    // Tenant-configurable deduction/OT policy (Phase 3)
    public float $policyOvertimeRate = 250;

    public float $policyLateDeduction = 500;

    public float $policyAbsenceDeduction = 1000;

    public float $policyHalfDayDeduction = 500;

    public float $policyUnpaidLeaveDeduction = 1000;

    public string $policyDefaultSalary = '';

    public function mount()
    {
        $this->authorizePayrollManager();
        $this->selectedMonth = Carbon::today()->format('Y-m');
        $this->payDate = Carbon::today()->format('Y-m-d');
        $this->loadPolicy();

        if (request()->has('staff_id')) {
            $this->selectedStaffId = (string) request()->query('staff_id');
        }
    }

    public function selectStaff(string $staffId): void
    {
        $this->selectedStaffId = $staffId;
    }

    private function authorizePayrollManager(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->isTenantOwner() && ! $user->isManager() && ! $user->isAccountant()) || ! $user->tenant_id) {
            abort(403, 'Unauthorized. Only Venue Owners or Accountants have access to Payroll ERP.');
        }
    }

    private function loadPolicy(): void
    {
        $policy = PayrollPolicy::forTenant(Auth::user()->tenant_id);
        $this->policyOvertimeRate = (float) $policy->overtime_rate_per_hour;
        $this->policyLateDeduction = (float) $policy->late_deduction_per_day;
        $this->policyAbsenceDeduction = (float) $policy->absence_deduction_per_day;
        $this->policyHalfDayDeduction = (float) $policy->half_day_deduction;
        $this->policyUnpaidLeaveDeduction = (float) $policy->unpaid_leave_deduction_per_day;
        $this->policyDefaultSalary = $policy->default_basic_salary !== null
            ? (string) $policy->default_basic_salary
            : '';
    }

    public function savePolicy(): void
    {
        $this->authorizePayrollManager();
        $this->validate([
            'policyOvertimeRate' => 'required|numeric|min:0',
            'policyLateDeduction' => 'required|numeric|min:0',
            'policyAbsenceDeduction' => 'required|numeric|min:0',
            'policyHalfDayDeduction' => 'required|numeric|min:0',
            'policyUnpaidLeaveDeduction' => 'required|numeric|min:0',
            'policyDefaultSalary' => 'nullable|numeric|min:1',
        ]);

        $tenantId = Auth::user()->tenant_id;
        PayrollPolicy::forTenant($tenantId)->update([
            'overtime_rate_per_hour' => $this->policyOvertimeRate,
            'late_deduction_per_day' => $this->policyLateDeduction,
            'absence_deduction_per_day' => $this->policyAbsenceDeduction,
            'half_day_deduction' => $this->policyHalfDayDeduction,
            'unpaid_leave_deduction_per_day' => $this->policyUnpaidLeaveDeduction,
            'default_basic_salary' => $this->policyDefaultSalary === '' ? null : $this->policyDefaultSalary,
            'updated_by' => Auth::id(),
        ]);

        AuditLog::log('PAYROLL_POLICY_UPDATED', 'Payroll deduction/OT policy updated.', $tenantId, 'PayrollPolicy', $tenantId);
        $this->feedbackMessage = 'Payroll policy saved. Future DRAFT payrolls use these rates.';
    }

    public function generateMonthlyPayroll()
    {
        $this->authorizePayrollManager();
        $this->errorMessage = null;
        validator(['selectedMonth' => $this->selectedMonth], ['selectedMonth' => 'required|date_format:Y-m'])->validate();
        $tenantId = Auth::user()->tenant_id;
        $policy = PayrollPolicy::forTenant($tenantId);
        $staffQuery = User::hallStaff($tenantId)
            ->where('status', '!=', 'SUSPENDED');
        if ($this->selectedStaffId !== 'ALL') {
            $staffQuery->whereKey($this->selectedStaffId);
        }
        $staff = $staffQuery->get();

        $monthStart = Carbon::parse($this->selectedMonth.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $generatedCount = 0;
        $lockedCount = 0;
        $skipped = [];
        foreach ($staff as $m) {
            $attendance = StaffAttendance::where('tenant_id', $tenantId)
                ->where('user_id', $m->id)
                ->whereDate('date', '>=', $monthStart->format('Y-m-d'))
                ->whereDate('date', '<=', $monthEnd->format('Y-m-d'))
                ->get();

            $counts = ['PRESENT' => 0, 'ABSENT' => 0, 'LATE' => 0, 'HALF_DAY' => 0, 'ON_LEAVE' => 0];
            foreach ($attendance as $row) {
                if (isset($counts[$row->status])) {
                    $counts[$row->status]++;
                }
            }
            $otHours = round((float) $attendance->sum('overtime_hours'), 2);

            // Approved UNPAID leave days inside this month (paid leave types
            // never reduce salary). Overlap is clamped to the payroll period.
            $unpaidLeaveDays = 0;
            StaffLeave::where('tenant_id', $tenantId)
                ->where('user_id', $m->id)
                ->where('status', 'APPROVED')
                ->where('leave_type', 'UNPAID')
                ->where('start_date', '<=', $monthEnd->format('Y-m-d'))
                ->where('end_date', '>=', $monthStart->format('Y-m-d'))
                ->get()
                ->each(function ($leave) use (&$unpaidLeaveDays, $monthStart, $monthEnd) {
                    $day = Carbon::parse($leave->start_date)->startOfDay()->max($monthStart);
                    $last = Carbon::parse($leave->end_date)->startOfDay()->min($monthEnd);
                    while ($day->lte($last)) {
                        $unpaidLeaveDays++;
                        $day->addDay();
                    }
                });

            $otRate = (float) $policy->overtime_rate_per_hour;
            $otPay = PayrollModel::calculateOvertimePay($otHours, $otRate);
            $lateDeduction = round($counts['LATE'] * (float) $policy->late_deduction_per_day, 2);
            $absenceDeduction = round($counts['ABSENT'] * (float) $policy->absence_deduction_per_day, 2);
            $halfDayDeduction = round($counts['HALF_DAY'] * (float) $policy->half_day_deduction, 2);
            $unpaidLeaveDeduction = round($unpaidLeaveDays * (float) $policy->unpaid_leave_deduction_per_day, 2);

            $existing = PayrollModel::where('tenant_id', $tenantId)
                ->where('user_id', $m->id)
                ->where('month_year', $this->selectedMonth)
                ->first();

            if ($existing) {
                // Approved/PAID payrolls are final: source attendance is locked.
                if ($existing->status !== 'DRAFT') {
                    $lockedCount++;

                    continue;
                }

                $bonuses = (float) $existing->bonuses;
                $advances = (float) $existing->advances;
                $other = (float) $existing->other_deductions;
                $allowances = (float) $existing->allowances;
                $totalDeductions = round($lateDeduction + $absenceDeduction + $halfDayDeduction + $unpaidLeaveDeduction + $advances + $other, 2);
                $net = PayrollModel::calculateNet((float) $existing->basic_salary, $otHours, $otRate, $allowances + $bonuses, $totalDeductions);

                $existing->update([
                    'overtime_hours' => $otHours,
                    'overtime_rate' => $otRate,
                    'present_days' => $counts['PRESENT'],
                    'absent_days' => $counts['ABSENT'],
                    'late_days' => $counts['LATE'],
                    'half_days' => $counts['HALF_DAY'],
                    'unpaid_leave_days' => $unpaidLeaveDays,
                    'overtime_pay' => $otPay,
                    'late_deduction' => $lateDeduction,
                    'absence_deduction' => $absenceDeduction,
                    'half_day_deduction' => $halfDayDeduction,
                    'unpaid_leave_deduction' => $unpaidLeaveDeduction,
                    'deductions' => $totalDeductions,
                    'net_salary' => $net,
                ]);
            } else {
                $defaultBasic = $this->resolvedBasicSalary($m, $policy);
                if ($defaultBasic === null) {
                    $skipped[] = $m->name;

                    continue;
                }
                $totalDeductions = round($lateDeduction + $absenceDeduction + $halfDayDeduction + $unpaidLeaveDeduction, 2);
                $net = PayrollModel::calculateNet($defaultBasic, $otHours, $otRate, 0, $totalDeductions);

                PayrollModel::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $m->id,
                    'month_year' => $this->selectedMonth,
                    'basic_salary' => $defaultBasic,
                    'overtime_hours' => $otHours,
                    'overtime_rate' => $otRate,
                    'allowances' => 0.00,
                    'deductions' => $totalDeductions,
                    'present_days' => $counts['PRESENT'],
                    'absent_days' => $counts['ABSENT'],
                    'late_days' => $counts['LATE'],
                    'half_days' => $counts['HALF_DAY'],
                    'unpaid_leave_days' => $unpaidLeaveDays,
                    'overtime_pay' => $otPay,
                    'late_deduction' => $lateDeduction,
                    'absence_deduction' => $absenceDeduction,
                    'half_day_deduction' => $halfDayDeduction,
                    'unpaid_leave_deduction' => $unpaidLeaveDeduction,
                    'net_salary' => $net,
                    'status' => 'DRAFT',
                    'payment_method' => 'CASH',
                ]);
            }
            $generatedCount++;
        }

        AuditLog::log(
            'PAYROLL_GENERATED',
            "Generated payroll for month [{$this->selectedMonth}] for {$generatedCount} staff member(s); {$lockedCount} finalised record(s) skipped.",
            $tenantId,
            'StaffPayroll',
            $this->selectedMonth
        );

        $this->feedbackMessage = 'Payroll compiled for '.Carbon::parse($this->selectedMonth.'-01')->format('F Y')
            ." ({$generatedCount} updated".($lockedCount ? ", {$lockedCount} finalised skipped" : '').').';
        if ($skipped !== []) {
            $this->errorMessage = 'No salary recorded for: '.implode(', ', $skipped).'. Set each employee base salary or an explicit payroll-policy salary. No amount was invented.';
        }
    }

    private function resolvedBasicSalary(User $employee, PayrollPolicy $policy): ?float
    {
        if ($employee->base_salary !== null && (float) $employee->base_salary > 0) {
            return round((float) $employee->base_salary, 2);
        }

        if ($policy->default_basic_salary !== null && (float) $policy->default_basic_salary > 0) {
            return round((float) $policy->default_basic_salary, 2);
        }

        return null;
    }

    public function openEditModal(string $payrollId)
    {
        $this->authorizePayrollManager();
        $record = PayrollModel::where('tenant_id', Auth::user()->tenant_id)->with('user')->findOrFail($payrollId);
        if ($record->status === 'PAID') {
            $this->errorMessage = 'A paid payroll cannot be edited.';

            return;
        }
        if ($record->status === 'APPROVED') {
            $this->errorMessage = 'This payroll is finalised. Reopen it to DRAFT before editing.';

            return;
        }
        $this->editingPayrollId = $record->id;
        $this->editingEmployeeName = $record->user->name ?? 'Staff';
        $this->editBasicSalary = (float) $record->basic_salary;
        $this->editOvertimeHours = (float) $record->overtime_hours;
        $this->editOvertimeRate = (float) $record->overtime_rate;
        $this->editAllowances = (float) $record->allowances;
        $this->editDeductions = (float) $record->deductions;
        $this->editNetSalary = (float) $record->net_salary;
        $this->editPaymentMethod = $record->payment_method ?? 'CASH';
        $this->editNotes = $record->notes ?? '';
        $this->updateBaseSalaryOnProfile = false;
        $this->showEditModal = true;
    }

    public function recalculateEditNet()
    {
        $this->authorizePayrollManager();
        $this->validate([
            'editOvertimeHours' => 'required|numeric|min:0|max:'.AttendanceComponent::MAX_OVERTIME_HOURS,
        ]);
        $this->editNetSalary = PayrollModel::calculateNet(
            $this->editBasicSalary,
            $this->editOvertimeHours,
            $this->editOvertimeRate,
            $this->editAllowances,
            $this->editDeductions
        );
    }

    public function updatePayroll()
    {
        $this->authorizePayrollManager();
        $record = PayrollModel::where('tenant_id', Auth::user()->tenant_id)
            ->with('user')->findOrFail($this->editingPayrollId);
        $originalMethod = (string) ($record->payment_method ?? '');
        $allowedMethods = $originalMethod === CashOnlyPayments::CASH
            ? CashOnlyPayments::METHODS
            : [$originalMethod, CashOnlyPayments::CASH];

        $this->validate([
            'editBasicSalary' => 'required|numeric|min:0',
            'editOvertimeHours' => 'required|numeric|min:0|max:'.AttendanceComponent::MAX_OVERTIME_HOURS,
            'editOvertimeRate' => 'required|numeric|min:0',
            'editAllowances' => 'required|numeric|min:0',
            'editDeductions' => 'required|numeric|min:0',
            'editPaymentMethod' => ['required', Rule::in($allowedMethods)],
        ]);

        if ($record->status === 'PAID') {
            $this->showEditModal = false;
            $this->errorMessage = 'A paid payroll cannot be edited. Use a separate adjustment entry.';

            return;
        }
        if ($record->status === 'APPROVED') {
            $this->showEditModal = false;
            $this->errorMessage = 'This payroll is finalised. Reopen it to DRAFT before editing.';

            return;
        }

        $net = PayrollModel::calculateNet(
            $this->editBasicSalary,
            $this->editOvertimeHours,
            $this->editOvertimeRate,
            $this->editAllowances,
            $this->editDeductions
        );

        $update = [
            'basic_salary' => $this->editBasicSalary,
            'overtime_hours' => $this->editOvertimeHours,
            'overtime_rate' => $this->editOvertimeRate,
            'overtime_pay' => PayrollModel::calculateOvertimePay($this->editOvertimeHours, $this->editOvertimeRate),
            'allowances' => $this->editAllowances,
            'deductions' => $this->editDeductions,
            'net_salary' => $net,
            'notes' => $this->editNotes,
        ];

        // Preserve historical labels on unrelated edits; only an explicit
        // conversion to cash may rewrite the method field.
        if ($this->editPaymentMethod === CashOnlyPayments::CASH && $originalMethod) {
            $update['payment_method'] = CashOnlyPayments::CASH;
        }

        $record->update($update);

        if ($this->updateBaseSalaryOnProfile && $record->user) {
            $record->user->update(['base_salary' => $this->editBasicSalary]);
        }

        $this->showEditModal = false;
        $this->feedbackMessage = "Salary details updated for [{$this->editingEmployeeName}].";
    }

    public function openPayModal(string $payrollId)
    {
        $this->authorizePayrollManager();
        $record = PayrollModel::where('tenant_id', Auth::user()->tenant_id)->findOrFail($payrollId);
        if ($record->status === 'PAID') {
            $this->errorMessage = 'This payroll entry has already been paid.';

            return;
        }
        if ($record->status !== 'APPROVED') {
            $this->errorMessage = 'Approve the payroll before it can be paid.';

            return;
        }
        $this->payingPayrollId = $record->id;
        $this->payMethod = CashOnlyPayments::CASH;
        $this->payDate = Carbon::today()->format('Y-m-d');
        $this->showPayModal = true;
    }

    public function markAsPaid()
    {
        $this->authorizePayrollManager();
        $record = PayrollModel::where('tenant_id', Auth::user()->tenant_id)->with('user')->findOrFail($this->payingPayrollId);
        $this->validate([
            'payDate' => 'required|date',
            'payMethod' => CashOnlyPayments::validationRule(),
        ]);
        $tenantId = Auth::user()->tenant_id;
        $payDate = $this->payDate;
        $payMethod = $this->payMethod;

        try {
            $outcome = DB::transaction(function () use ($tenantId, $payDate, $payMethod) {
                $locked = PayrollModel::where('tenant_id', $tenantId)
                    ->whereKey($this->payingPayrollId)
                    ->lockForUpdate()
                    ->with('user')
                    ->firstOrFail();

                if ($locked->status === 'PAID') {
                    return 'already';
                }
                if ($locked->status !== 'APPROVED') {
                    return 'not-approved';
                }

                $locked->update([
                    'status' => 'PAID',
                    'paid_at' => Carbon::parse($payDate),
                    'payment_method' => $payMethod,
                ]);

                Expense::create([
                    'tenant_id' => $tenantId,
                    'payroll_id' => $locked->id,
                    'category' => 'STAFF_WAGES',
                    'title' => 'Salary Payout - '.($locked->user->name ?? 'Staff')." ({$locked->month_year})",
                    'amount' => $locked->net_salary,
                    'expense_date' => $payDate,
                    'payee' => $locked->user->name ?? 'Staff',
                    'payment_method' => $payMethod,
                    'receipt_number' => 'PAYROLL-'.$locked->id,
                    'notes' => "Monthly staff salary disbursed via {$payMethod}.",
                    'created_by_user_id' => Auth::id(),
                ]);

                AuditLog::log(
                    'PAYROLL_PAID',
                    'Disbursed salary of PKR '.number_format((float) $locked->net_salary).' to ['.($locked->user->name ?? 'Staff').'].',
                    $tenantId,
                    'StaffPayroll',
                    $locked->id
                );

                return 'paid';
            });
        } catch (UniqueConstraintViolationException) {
            $this->showPayModal = false;
            $this->errorMessage = 'This payroll already has a disbursement. No second payment was recorded.';

            return;
        }

        $this->showPayModal = false;
        if ($outcome === 'already') {
            $this->errorMessage = 'This payroll entry has already been paid.';

            return;
        }
        if ($outcome === 'not-approved') {
            $this->errorMessage = 'Approve the payroll before it can be paid. A draft was not paid.';

            return;
        }

        $this->feedbackMessage = 'Salary slip marked as PAID and auto-posted to ERP Expense Ledger.';
    }

    public function markAsApproved(string $payrollId): void
    {
        $this->authorizePayrollManager();
        $tenantId = Auth::user()->tenant_id;
        $record = PayrollModel::where('tenant_id', $tenantId)->findOrFail($payrollId);

        if ($record->status === 'PAID') {
            $this->errorMessage = 'A paid payroll cannot be re-approved.';

            return;
        }
        if ($record->status === 'APPROVED') {
            $this->errorMessage = 'This payroll is already approved.';

            return;
        }

        $record->update([
            'status' => 'APPROVED',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        AuditLog::log('PAYROLL_APPROVED', "Payroll [{$payrollId}] approved; attendance period locked.", $tenantId, 'StaffPayroll', $payrollId);
        $this->feedbackMessage = 'Payroll approved. Source attendance for this month is now locked.';
    }

    public function reopenPayroll(string $payrollId): void
    {
        $this->authorizePayrollManager();
        $tenantId = Auth::user()->tenant_id;
        $record = PayrollModel::where('tenant_id', $tenantId)->findOrFail($payrollId);

        if ($record->status === 'PAID') {
            $this->errorMessage = 'A paid payroll cannot be reopened. Use a separate adjustment entry.';

            return;
        }

        $record->update([
            'status' => 'DRAFT',
            'approved_by' => null,
            'approved_at' => null,
        ]);

        AuditLog::log('PAYROLL_REOPENED', "Payroll [{$payrollId}] reopened to DRAFT by an authorized manager.", $tenantId, 'StaffPayroll', $payrollId);
        $this->feedbackMessage = 'Payroll reopened to draft; attendance period unlocked.';
    }

    public function render()
    {
        $this->authorizePayrollManager();
        $tenantId = Auth::user()->tenant_id;

        $payrollQuery = PayrollModel::where('tenant_id', $tenantId)
            ->where('month_year', $this->selectedMonth)
            ->with(['user' => fn ($query) => $query->withTrashed()]);
        if ($this->selectedStaffId !== 'ALL') {
            $payrollQuery->where('user_id', $this->selectedStaffId);
        }
        $payrolls = $payrollQuery->get();
        $staffMembers = User::hallStaff($tenantId)
            ->where('status', '!=', 'SUSPENDED')
            ->orderBy('name')
            ->get();

        $totalBasic = $payrolls->sum('basic_salary');
        $totalOvertime = $payrolls->sum(fn ($p) => $p->overtime_hours * $p->overtime_rate);
        $totalAllowances = $payrolls->sum('allowances');
        $totalDeductions = $payrolls->sum('deductions');
        $totalNetPayroll = $payrolls->sum('net_salary');
        $totalPaid = $payrolls->where('status', 'PAID')->sum('net_salary');
        $totalPending = $totalNetPayroll - $totalPaid;

        return view('livewire.staff-payroll', [
            'payrolls' => $payrolls,
            'policy' => PayrollPolicy::forTenant($tenantId),
            'totalBasic' => $totalBasic,
            'totalOvertime' => $totalOvertime,
            'totalAllowances' => $totalAllowances,
            'totalDeductions' => $totalDeductions,
            'totalNetPayroll' => $totalNetPayroll,
            'totalPaid' => $totalPaid,
            'totalPending' => $totalPending,
            'staffMembers' => $staffMembers,
        ]);
    }
}
