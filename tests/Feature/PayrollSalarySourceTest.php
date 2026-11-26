<?php

namespace Tests\Feature;

use App\Livewire\StaffPayroll;
use App\Models\PayrollPolicy;
use App\Models\StaffAttendance as AttendanceRecord;
use App\Models\StaffPayroll as PayrollModel;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PayrollSalarySourceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Salary Source Banquets',
            'slug' => 'salary-source',
            'email' => 'hr@salary-source.pk',
            'phone' => '+92 300 1112233',
            'city' => 'Karachi',
            'subscription_plan' => 'STARTER',
            'currency' => 'PKR',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);
        $this->owner = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'ROLE_TENANT_OWNER',
        ]);
    }

    public function test_payroll_is_not_generated_without_a_real_salary(): void
    {
        $unpaid = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'ROLE_CHEF',
            'status' => 'ACTIVE',
            'name' => 'No Salary Chef',
            'base_salary' => null,
        ]);

        Livewire::actingAs($this->owner)
            ->test(StaffPayroll::class)
            ->call('generateMonthlyPayroll')
            ->assertSee('No Salary Chef')
            ->assertSee('No amount was invented');

        $this->assertDatabaseMissing('staff_payrolls', [
            'user_id' => $unpaid->id,
        ]);
    }

    public function test_explicit_policy_salary_is_used_when_employee_salary_is_missing(): void
    {
        $employee = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'ROLE_FRONT_DESK',
            'status' => 'ACTIVE',
            'base_salary' => null,
        ]);

        Livewire::actingAs($this->owner)
            ->test(StaffPayroll::class)
            ->set('policyDefaultSalary', '41000')
            ->call('savePolicy')
            ->call('generateMonthlyPayroll');

        $this->assertSame(41000.00, (float) PayrollPolicy::where('tenant_id', $this->tenant->id)->firstOrFail()->default_basic_salary);
        $this->assertSame(41000.00, (float) PayrollModel::where('user_id', $employee->id)->firstOrFail()->basic_salary);
    }

    public function test_employee_salary_outranks_policy_and_recorded_overtime_is_not_replaced(): void
    {
        $employee = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'ROLE_FLOOR_STAFF',
            'status' => 'ACTIVE',
            'base_salary' => 36000,
        ]);
        PayrollPolicy::forTenant($this->tenant->id)->update(['default_basic_salary' => 90000]);

        AttendanceRecord::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $employee->id,
            'date' => Carbon::today()->startOfMonth()->addDays(2)->toDateString(),
            'status' => 'PRESENT',
            'check_in' => '18:00',
            'check_out' => '02:00',
            'overnight' => true,
            'overtime_hours' => 2,
            'source' => 'RECORDED',
            'created_by' => $this->owner->id,
        ]);

        Livewire::actingAs($this->owner)
            ->test(StaffPayroll::class)
            ->call('generateMonthlyPayroll');

        $payroll = PayrollModel::where('user_id', $employee->id)->firstOrFail();
        $this->assertSame(36000.00, (float) $payroll->basic_salary);
        $this->assertSame(2.00, (float) $payroll->overtime_hours);
        $this->assertSame(500.00, (float) $payroll->overtime_pay);
    }

    public function test_payroll_policy_rejects_values_outside_database_decimal_precision(): void
    {
        Livewire::actingAs($this->owner)
            ->test(StaffPayroll::class)
            ->set('policyOvertimeRate', 100000000)
            ->set('policyLateDeduction', 500)
            ->set('policyAbsenceDeduction', 1000)
            ->set('policyHalfDayDeduction', 500)
            ->set('policyUnpaidLeaveDeduction', 1000)
            ->set('policyDefaultSalary', '41000')
            ->call('savePolicy')
            ->assertHasErrors(['policyOvertimeRate']);

        $policy = PayrollPolicy::where('tenant_id', $this->tenant->id)->firstOrFail();
        $this->assertSame(250.00, (float) $policy->overtime_rate_per_hour);
        $this->assertNull($policy->default_basic_salary);
    }

    public function test_paid_payroll_is_not_regenerated(): void
    {
        $employee = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'ROLE_ACCOUNTANT',
            'status' => 'ACTIVE',
            'base_salary' => 50000,
        ]);
        $month = Carbon::today()->format('Y-m');
        $payroll = PayrollModel::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $employee->id,
            'month_year' => $month,
            'basic_salary' => 50000,
            'net_salary' => 50000,
            'status' => 'PAID',
            'payment_method' => 'CASH',
        ]);

        AttendanceRecord::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $employee->id,
            'date' => Carbon::today()->startOfMonth()->toDateString(),
            'status' => 'ABSENT',
            'created_by' => $this->owner->id,
        ]);

        Livewire::actingAs($this->owner)
            ->test(StaffPayroll::class)
            ->call('generateMonthlyPayroll');

        $payroll->refresh();
        $this->assertSame('PAID', $payroll->status);
        $this->assertSame(50000.00, (float) $payroll->net_salary);
        $this->assertSame(0, (int) $payroll->absent_days);
    }
}
