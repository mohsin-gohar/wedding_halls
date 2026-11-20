<?php

namespace Tests\Feature;

use App\Livewire\StaffAttendance;
use App\Livewire\StaffPayroll;
use App\Models\PayrollPolicy;
use App\Models\StaffAttendance as AttendanceRecord;
use App\Models\StaffLeave;
use App\Models\StaffPayroll as PayrollModel;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase 3: Employee -> Shift -> Attendance -> Leave -> Overtime
 *       -> Payroll -> Approval -> Payment -> Payslip.
 */
class PayrollAttendanceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private User $staff;

    private string $month;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Payroll Integration Banquets',
            'slug' => 'payroll-integration',
            'email' => 'hr@payroll-integration.pk',
            'phone' => '+92 300 7778899',
            'city' => 'Karachi',
            'subscription_plan' => 'STARTER',
            'currency' => 'PKR',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);
        $this->owner = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'ROLE_TENANT_OWNER',
            'password' => Hash::make('password'),
        ]);
        $this->staff = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'ROLE_CHEF',
            'status' => 'ACTIVE',
            'base_salary' => 50000.00,
        ]);
        $this->month = Carbon::today()->format('Y-m');
    }

    private function attendance(string $date, string $status, float $ot = 0): void
    {
        AttendanceRecord::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->staff->id,
            'date' => $date,
            'status' => $status,
            'check_in' => $status === 'ABSENT' ? null : '09:00',
            'overtime_hours' => $ot,
            'created_by' => $this->owner->id,
        ]);
    }

    private function generate(): void
    {
        Livewire::actingAs($this->owner)
            ->test(StaffPayroll::class)
            ->set('selectedMonth', $this->month)
            ->call('generateMonthlyPayroll');
    }

    private function payroll(): PayrollModel
    {
        return PayrollModel::where('tenant_id', $this->tenant->id)
            ->where('user_id', $this->staff->id)
            ->where('month_year', $this->month)
            ->firstOrFail();
    }

    public function test_payroll_deductions_are_derived_from_attendance_and_policy(): void
    {
        $start = Carbon::today()->startOfMonth();
        $this->attendance($start->copy()->addDays(1)->format('Y-m-d'), 'ABSENT');
        $this->attendance($start->copy()->addDays(2)->format('Y-m-d'), 'ABSENT');
        $this->attendance($start->copy()->addDays(3)->format('Y-m-d'), 'LATE');
        $this->attendance($start->copy()->addDays(4)->format('Y-m-d'), 'HALF_DAY');
        $this->attendance($start->copy()->addDays(5)->format('Y-m-d'), 'PRESENT', 4.0);

        $this->generate();

        // defaults: absence 1000 x2, late 500, half day 500 => 3000; OT 4 x 250 = 1000
        $this->assertDatabaseHas('staff_payrolls', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->staff->id,
            'month_year' => $this->month,
            'present_days' => 1,
            'absent_days' => 2,
            'late_days' => 1,
            'half_days' => 1,
            'overtime_hours' => 4.00,
            'overtime_pay' => 1000.00,
            'absence_deduction' => 2000.00,
            'late_deduction' => 500.00,
            'half_day_deduction' => 500.00,
            'deductions' => 3000.00,
            'net_salary' => 48000.00,
        ]);
    }

    public function test_approved_unpaid_leave_reduces_net_salary(): void
    {
        $start = Carbon::today()->startOfMonth();
        StaffLeave::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->staff->id,
            'leave_type' => 'UNPAID',
            'start_date' => $start->copy()->addDays(6)->format('Y-m-d'),
            'end_date' => $start->copy()->addDays(7)->format('Y-m-d'),
            'days' => 2,
            'reason' => 'Personal matter without pay',
            'status' => 'APPROVED',
        ]);

        $this->generate();

        $this->assertDatabaseHas('staff_payrolls', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->staff->id,
            'unpaid_leave_days' => 2,
            'unpaid_leave_deduction' => 2000.00,
            'net_salary' => 48000.00,
        ]);
    }

    public function test_policy_rates_are_tenant_configurable(): void
    {
        Livewire::actingAs($this->owner)
            ->test(StaffPayroll::class)
            ->set('policyAbsenceDeduction', 1500)
            ->call('savePolicy')
            ->assertHasNoErrors();

        $this->assertSame(1500.00, (float) PayrollPolicy::where('tenant_id', $this->tenant->id)->firstOrFail()->absence_deduction_per_day);

        $this->attendance(Carbon::today()->startOfMonth()->addDays(1)->format('Y-m-d'), 'ABSENT');
        $this->generate();

        $this->assertSame(1500.00, (float) $this->payroll()->absence_deduction);
        $this->assertSame(48500.00, (float) $this->payroll()->net_salary);
    }

    public function test_approval_then_payment_flow_locks_edits(): void
    {
        $this->generate();
        $payroll = $this->payroll();

        Livewire::actingAs($this->owner)
            ->test(StaffPayroll::class)
            ->call('markAsApproved', $payroll->id);

        $payroll->refresh();
        $this->assertSame('APPROVED', $payroll->status);
        $this->assertNotNull($payroll->approved_at);
        $this->assertSame($this->owner->id, $payroll->approved_by);

        Livewire::actingAs($this->owner)
            ->test(StaffPayroll::class)
            ->set('editingPayrollId', $payroll->id)
            ->set('editBasicSalary', 1)
            ->call('updatePayroll');

        $payroll->refresh();
        $this->assertSame(50000.00, (float) $payroll->basic_salary); // unchanged

        Livewire::actingAs($this->owner)
            ->test(StaffPayroll::class)
            ->set('payingPayrollId', $payroll->id)
            ->set('payDate', Carbon::today()->format('Y-m-d'))
            ->set('payMethod', 'CASH')
            ->call('markAsPaid');

        $payroll->refresh();
        $this->assertSame('PAID', $payroll->status);
        $this->assertDatabaseHas('expenses', [
            'tenant_id' => $this->tenant->id,
            'category' => 'STAFF_WAGES',
            'amount' => $payroll->net_salary,
        ]);
    }

    public function test_finalised_payroll_locks_attendance_until_reopened(): void
    {
        $this->generate();
        $payroll = $this->payroll();

        Livewire::actingAs($this->owner)
            ->test(StaffPayroll::class)
            ->call('markAsApproved', $payroll->id);

        $date = Carbon::today()->format('Y-m-d');
        Livewire::actingAs($this->owner)
            ->test(StaffAttendance::class)
            ->set('selectedDate', $date)
            ->set('attendanceData', [
                $this->staff->id => [
                    'status' => 'PRESENT',
                    'check_in' => '09:00',
                    'check_out' => '18:00',
                    'overtime_hours' => 0,
                    'notes' => '',
                ],
            ])
            ->call('saveDailyAttendance');

        $this->assertDatabaseMissing('staff_attendances', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->staff->id,
            'date' => $date,
        ]);

        Livewire::actingAs($this->owner)
            ->test(StaffPayroll::class)
            ->call('reopenPayroll', $payroll->id);

        $this->assertSame('DRAFT', $payroll->fresh()->status);

        Livewire::actingAs($this->owner)
            ->test(StaffAttendance::class)
            ->set('selectedDate', $date)
            ->set('attendanceData', [
                $this->staff->id => [
                    'status' => 'PRESENT',
                    'check_in' => '09:00',
                    'check_out' => '18:00',
                    'overtime_hours' => 0,
                    'notes' => '',
                ],
            ])
            ->call('saveDailyAttendance')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('staff_attendances', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->staff->id,
            'status' => 'PRESENT',
        ]);
    }

    public function test_payroll_is_closed_to_customers(): void
    {
        $customer = User::factory()->create(['role' => 'ROLE_CUSTOMER']);

        Livewire::actingAs($customer)
            ->test(StaffPayroll::class)
            ->assertStatus(403);
    }
}
