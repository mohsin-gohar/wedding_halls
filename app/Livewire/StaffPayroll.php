<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\StaffAttendance;
use App\Models\StaffPayroll as PayrollModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Staff Payroll & Salary Slips — VenueCloud ERP'])]
class StaffPayroll extends Component
{
    public string $selectedMonth = '';
    public ?string $feedbackMessage = null;
    public ?string $errorMessage = null;

    // Edit Payroll Modal
    public bool $showEditModal = false;
    public ?string $editingPayrollId = null;
    public string $editingEmployeeName = '';
    public float $editBasicSalary = 35000;
    public float $editOvertimeHours = 0;
    public float $editOvertimeRate = 250;
    public float $editAllowances = 0;
    public float $editDeductions = 0;
    public float $editNetSalary = 35000;
    public string $editPaymentMethod = 'CASH';
    public string $editNotes = '';

    // Mark as Paid Modal
    public bool $showPayModal = false;
    public ?string $payingPayrollId = null;
    public string $payMethod = 'BANK_TRANSFER';
    public string $payDate = '';

    public function mount()
    {
        $user = Auth::user();
        if (!$user || (!$user->isTenantOwner() && !$user->isManager() && !$user->isSuperAdmin() && !$user->isAccountant())) {
            abort(403, 'Unauthorized. Only Venue Owners or Accountants have access to Payroll ERP.');
        }

        $this->selectedMonth = Carbon::today()->format('Y-m');
        $this->payDate = Carbon::today()->format('Y-m-d');
    }

    public function generateMonthlyPayroll()
    {
        $tenantId = Auth::user()->tenant_id;
        $staff = User::where('tenant_id', $tenantId)
            ->where('role', '!=', 'ROLE_CUSTOMER')
            ->get();

        $monthStart = Carbon::parse($this->selectedMonth . '-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $generatedCount = 0;
        foreach ($staff as $m) {
            // Check overtime hours from attendance
            $otHours = (float) StaffAttendance::where('tenant_id', $tenantId)
                ->where('user_id', $m->id)
                ->whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
                ->sum('overtime_hours');

            // Default basic salary based on role
            $defaultBasic = match ($m->role) {
                'ROLE_MANAGER' => 75000.00,
                'ROLE_CHEF' => 60000.00,
                'ROLE_ACCOUNTANT' => 50000.00,
                'ROLE_FRONT_DESK' => 38000.00,
                default => 32000.00,
            };

            $otRate = 250.00;
            $net = PayrollModel::calculateNet($defaultBasic, $otHours, $otRate, 0, 0);

            PayrollModel::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'user_id' => $m->id,
                    'month_year' => $this->selectedMonth,
                ],
                [
                    'basic_salary' => $defaultBasic,
                    'overtime_hours' => $otHours,
                    'overtime_rate' => $otRate,
                    'allowances' => 0.00,
                    'deductions' => 0.00,
                    'net_salary' => $net,
                    'status' => 'DRAFT',
                    'payment_method' => 'CASH',
                ]
            );
            $generatedCount++;
        }

        AuditLog::log(
            'PAYROLL_GENERATED',
            "Generated payroll for month [{$this->selectedMonth}] for {$generatedCount} staff member(s).",
            $tenantId,
            'StaffPayroll',
            $this->selectedMonth
        );

        $this->feedbackMessage = "Monthly payroll successfully compiled for " . Carbon::parse($this->selectedMonth . '-01')->format('F Y') . "!";
    }

    public function openEditModal(string $payrollId)
    {
        $record = PayrollModel::where('tenant_id', Auth::user()->tenant_id)->with('user')->findOrFail($payrollId);
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
        $this->showEditModal = true;
    }

    public function recalculateEditNet()
    {
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
        $this->validate([
            'editBasicSalary' => 'required|numeric|min:0',
            'editOvertimeHours' => 'required|numeric|min:0',
            'editOvertimeRate' => 'required|numeric|min:0',
            'editAllowances' => 'required|numeric|min:0',
            'editDeductions' => 'required|numeric|min:0',
        ]);

        $record = PayrollModel::where('tenant_id', Auth::user()->tenant_id)->findOrFail($this->editingPayrollId);
        $net = PayrollModel::calculateNet(
            $this->editBasicSalary,
            $this->editOvertimeHours,
            $this->editOvertimeRate,
            $this->editAllowances,
            $this->editDeductions
        );

        $record->update([
            'basic_salary' => $this->editBasicSalary,
            'overtime_hours' => $this->editOvertimeHours,
            'overtime_rate' => $this->editOvertimeRate,
            'allowances' => $this->editAllowances,
            'deductions' => $this->editDeductions,
            'net_salary' => $net,
            'payment_method' => $this->editPaymentMethod,
            'notes' => $this->editNotes,
        ]);

        $this->showEditModal = false;
        $this->feedbackMessage = "Salary details updated for [{$this->editingEmployeeName}].";
    }

    public function openPayModal(string $payrollId)
    {
        $this->payingPayrollId = $payrollId;
        $this->payMethod = 'BANK_TRANSFER';
        $this->payDate = Carbon::today()->format('Y-m-d');
        $this->showPayModal = true;
    }

    public function markAsPaid()
    {
        $record = PayrollModel::where('tenant_id', Auth::user()->tenant_id)->with('user')->findOrFail($this->payingPayrollId);
        
        $record->update([
            'status' => 'PAID',
            'paid_at' => Carbon::parse($this->payDate),
            'payment_method' => $this->payMethod,
        ]);

        // Auto-post expense to ERP ledger so net profit / loss includes payroll
        Expense::create([
            'tenant_id' => Auth::user()->tenant_id,
            'category' => 'STAFF_WAGES',
            'title' => "Salary Payout - {$record->user->name} ({$record->month_year})",
            'amount' => $record->net_salary,
            'expense_date' => $this->payDate,
            'payee' => $record->user->name,
            'payment_method' => $this->payMethod,
            'receipt_number' => 'SLIP-' . strtoupper(substr($record->id, 0, 8)),
            'notes' => "Monthly staff salary disbursed via {$this->payMethod}.",
            'created_by_user_id' => Auth::id(),
        ]);

        AuditLog::log(
            'PAYROLL_PAID',
            "Disbursed salary of PKR " . number_format($record->net_salary) . " to [{$record->user->name}].",
            Auth::user()->tenant_id,
            'StaffPayroll',
            $record->id
        );

        $this->showPayModal = false;
        $this->feedbackMessage = "Salary slip marked as PAID and auto-posted to ERP Expense Ledger.";
    }

    public function render()
    {
        $tenantId = Auth::user()->tenant_id;

        $payrolls = PayrollModel::where('tenant_id', $tenantId)
            ->where('month_year', $this->selectedMonth)
            ->with('user')
            ->get();

        $totalBasic = $payrolls->sum('basic_salary');
        $totalOvertime = $payrolls->sum(fn($p) => $p->overtime_hours * $p->overtime_rate);
        $totalAllowances = $payrolls->sum('allowances');
        $totalDeductions = $payrolls->sum('deductions');
        $totalNetPayroll = $payrolls->sum('net_salary');
        $totalPaid = $payrolls->where('status', 'PAID')->sum('net_salary');
        $totalPending = $totalNetPayroll - $totalPaid;

        return view('livewire.staff-payroll', [
            'payrolls' => $payrolls,
            'totalBasic' => $totalBasic,
            'totalOvertime' => $totalOvertime,
            'totalAllowances' => $totalAllowances,
            'totalDeductions' => $totalDeductions,
            'totalNetPayroll' => $totalNetPayroll,
            'totalPaid' => $totalPaid,
            'totalPending' => $totalPending,
        ]);
    }
}
