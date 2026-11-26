<?php

namespace Tests\Feature;

use App\Livewire\StaffPayroll;
use App\Models\Expense;
use App\Models\StaffPayroll as PayrollModel;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PayrollPaymentIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Payment Integrity Banquets',
            'slug' => 'payment-integrity',
            'email' => 'pay@integrity.pk',
            'phone' => '+92 300 4445566',
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
        $this->staff = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'ROLE_CHEF',
            'status' => 'ACTIVE',
            'base_salary' => 42000,
        ]);
    }

    private function payroll(string $status): PayrollModel
    {
        return PayrollModel::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->staff->id,
            'month_year' => Carbon::today()->format('Y-m'),
            'basic_salary' => 42000,
            'net_salary' => 42000,
            'status' => $status,
            'payment_method' => 'CASH',
        ]);
    }

    public function test_draft_payroll_cannot_be_paid_and_creates_no_expense(): void
    {
        $payroll = $this->payroll('DRAFT');

        Livewire::actingAs($this->owner)
            ->test(StaffPayroll::class)
            ->set('payingPayrollId', $payroll->id)
            ->set('payDate', Carbon::today()->toDateString())
            ->set('payMethod', 'CASH')
            ->call('markAsPaid')
            ->assertSee('Approve the payroll before it can be paid');

        $payroll->refresh();
        $this->assertSame('DRAFT', $payroll->status);
        $this->assertSame(0, Expense::where('payroll_id', $payroll->id)->count());
    }

    public function test_approved_payment_is_atomic_and_a_second_pay_does_not_duplicate(): void
    {
        $payroll = $this->payroll('APPROVED');

        $component = Livewire::actingAs($this->owner)->test(StaffPayroll::class);
        $component
            ->set('payingPayrollId', $payroll->id)
            ->set('payDate', Carbon::today()->toDateString())
            ->set('payMethod', 'CASH')
            ->call('markAsPaid');

        $component
            ->set('payingPayrollId', $payroll->id)
            ->call('markAsPaid')
            ->assertSee('already been paid');

        $payroll->refresh();
        $this->assertSame('PAID', $payroll->status);
        $this->assertSame(1, Expense::where('payroll_id', $payroll->id)->count());
        $this->assertSame(42000.00, (float) Expense::where('payroll_id', $payroll->id)->first()->amount);
    }

    public function test_orphan_expense_for_same_payroll_blocks_a_second_payment(): void
    {
        $payroll = $this->payroll('APPROVED');

        // Data anomaly: a wage expense already exists for this payroll.
        Expense::create([
            'tenant_id' => $this->tenant->id,
            'payroll_id' => $payroll->id,
            'category' => 'STAFF_WAGES',
            'title' => 'Duplicate anomaly',
            'amount' => 42000,
            'expense_date' => Carbon::today()->toDateString(),
        ]);

        Livewire::actingAs($this->owner)
            ->test(StaffPayroll::class)
            ->set('payingPayrollId', $payroll->id)
            ->set('payDate', Carbon::today()->toDateString())
            ->set('payMethod', 'CASH')
            ->call('markAsPaid')
            ->assertSee('already has a disbursement');

        $payroll->refresh();
        $this->assertSame('APPROVED', $payroll->status); // never marked PAID
        $this->assertSame(1, Expense::where('payroll_id', $payroll->id)->count());
    }

    public function test_editing_draft_payroll_updates_the_overtime_breakdown_with_net_pay(): void
    {
        $payroll = $this->payroll('DRAFT');

        Livewire::actingAs($this->owner)
            ->test(StaffPayroll::class)
            ->set('editingPayrollId', $payroll->id)
            ->set('editBasicSalary', 100.00)
            ->set('editOvertimeHours', 1.25)
            ->set('editOvertimeRate', 10.25)
            ->set('editAllowances', 20.00)
            ->set('editDeductions', 5.00)
            ->set('editPaymentMethod', 'CASH')
            ->call('updatePayroll')
            ->assertHasNoErrors();

        $payroll->refresh();
        $this->assertSame(12.81, (float) $payroll->overtime_pay);
        $this->assertSame(127.81, (float) $payroll->net_salary);
    }

    public function test_payroll_editor_rejects_money_values_above_decimal_storage_limit(): void
    {
        $payroll = $this->payroll('DRAFT');

        Livewire::actingAs($this->owner)
            ->test(StaffPayroll::class)
            ->set('editingPayrollId', $payroll->id)
            ->set('editBasicSalary', 10_000_000_000)
            ->set('editOvertimeHours', 0)
            ->set('editOvertimeRate', 0)
            ->set('editAllowances', 0)
            ->set('editDeductions', 0)
            ->set('editPaymentMethod', 'CASH')
            ->call('updatePayroll')
            ->assertHasErrors(['editBasicSalary']);

        $this->assertSame(42000.00, (float) $payroll->fresh()->basic_salary);
        $this->assertSame(42000.00, (float) $payroll->fresh()->net_salary);
    }

    public function test_approved_payroll_cannot_be_edited_and_keeps_its_final_amount(): void
    {
        $payroll = $this->payroll('APPROVED');

        Livewire::actingAs($this->owner)
            ->test(StaffPayroll::class)
            ->set('editingPayrollId', $payroll->id)
            ->set('editBasicSalary', 100.00)
            ->set('editOvertimeHours', 1.25)
            ->set('editOvertimeRate', 10.25)
            ->set('editAllowances', 20.00)
            ->set('editDeductions', 5.00)
            ->set('editPaymentMethod', 'CASH')
            ->call('updatePayroll')
            ->assertSee('finalised');

        $payroll->refresh();
        $this->assertSame('APPROVED', $payroll->status);
        $this->assertSame(42000.00, (float) $payroll->basic_salary);
        $this->assertSame(42000.00, (float) $payroll->net_salary);
    }

    public function test_only_approved_payroll_can_be_reopened_to_draft(): void
    {
        $draft = $this->payroll('DRAFT');

        Livewire::actingAs($this->owner)
            ->test(StaffPayroll::class)
            ->call('reopenPayroll', $draft->id)
            ->assertSee('Only an approved payroll can be reopened');

        $this->assertSame('DRAFT', $draft->fresh()->status);
    }

    public function test_paid_payroll_cannot_be_reopened_or_edited(): void
    {
        $payroll = $this->payroll('PAID');

        Livewire::actingAs($this->owner)
            ->test(StaffPayroll::class)
            ->call('reopenPayroll', $payroll->id)
            ->assertSee('cannot be reopened');

        Livewire::actingAs($this->owner)
            ->test(StaffPayroll::class)
            ->set('editingPayrollId', $payroll->id)
            ->set('editBasicSalary', 1)
            ->call('updatePayroll');

        $payroll->refresh();
        $this->assertSame('PAID', $payroll->status);
        $this->assertSame(42000.00, (float) $payroll->basic_salary);
    }
}
