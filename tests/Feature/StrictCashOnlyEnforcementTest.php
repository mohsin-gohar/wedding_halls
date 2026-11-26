<?php

namespace Tests\Feature;

use App\Livewire\ExpenseTracker;
use App\Livewire\StaffPayroll as StaffPayrollComponent;
use App\Livewire\VendorFinanceBoard;
use App\Models\Booking;
use App\Models\Expense;
use App\Models\Hall;
use App\Models\Payment;
use App\Models\ServiceBooking;
use App\Models\ServicePayment;
use App\Models\StaffPayroll;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VendorPayout;
use App\Services\PaymentService;
use App\Services\ServiceBookingService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class StrictCashOnlyEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function hall(): Hall
    {
        return Hall::query()->firstOrFail();
    }

    private function owner(): User
    {
        $owner = User::query()
            ->where('tenant_id', $this->hall()->tenant_id)
            ->where('role', 'ROLE_TENANT_OWNER')
            ->firstOrFail();

        $owner->assignRole('ROLE_TENANT_OWNER');
        $owner->givePermissionTo(['payments.record', 'payments.refund', 'payouts.request', 'wallet.view']);

        return $owner->fresh();
    }

    private function customer(): User
    {
        return User::query()->where('role', 'ROLE_CUSTOMER')->firstOrFail();
    }

    public function test_non_cash_methods_are_rejected_by_every_financial_model(): void
    {
        $tenantId = $this->hall()->tenant_id;
        $owner = $this->owner();
        $customer = $this->customer();
        $plan = SubscriptionPlan::query()->firstOrFail();

        $booking = Booking::query()->where('tenant_id', $tenantId)->firstOrFail();
        $serviceBooking = ServiceBooking::create([
            'tenant_id' => $tenantId,
            'customer_id' => $customer->id,
            'booking_reference' => 'RG-SB-STRICT-'.strtoupper(Str::random(5)),
            'event_date' => now()->addDays(60)->toDateString(),
            'quoted_amount' => 25000,
            'status' => 'CONFIRMED',
        ]);

        $marker = 'NON-CASH-'.strtoupper(Str::random(8));
        $attempts = [
            fn () => Payment::create([
                'tenant_id' => $tenantId,
                'booking_id' => $booking->id,
                'amount' => 1000,
                'payment_type' => 'TOKEN_ADVANCE',
                'payment_method' => 'BANK_TRANSFER',
                'receipt_no' => $marker,
            ]),
            fn () => ServicePayment::create([
                'tenant_id' => $tenantId,
                'service_booking_id' => $serviceBooking->id,
                'customer_id' => $customer->id,
                'amount' => 1000,
                'method' => 'CHEQUE',
                'status' => 'PENDING',
                'kind' => 'INSTALLMENT',
                'reference' => $marker,
            ]),
            fn () => SubscriptionPayment::create([
                'tenant_id' => $tenantId,
                'subscription_plan_id' => $plan->id,
                'billing_cycle' => 'MONTHLY',
                'amount' => 1000,
                'payment_method' => 'BANK_TRANSFER',
                'transaction_reference' => $marker,
                'status' => 'PENDING',
            ]),
            fn () => Expense::create([
                'tenant_id' => $tenantId,
                'category' => 'GENERATOR_DIESEL',
                'title' => $marker,
                'amount' => 1000,
                'expense_date' => now()->toDateString(),
                'payment_method' => 'CHEQUE',
                'receipt_number' => $marker,
                'created_by_user_id' => $owner->id,
            ]),
            fn () => VendorPayout::create([
                'tenant_id' => $tenantId,
                'amount' => 1000,
                'method' => 'BANK_TRANSFER',
                'reference' => $marker,
                'status' => 'REQUESTED',
                'requested_at' => now(),
            ]),
            fn () => StaffPayroll::create([
                'tenant_id' => $tenantId,
                'user_id' => $owner->id,
                'month_year' => now()->addYears(10)->format('Y-m'),
                'basic_salary' => 1000,
                'net_salary' => 1000,
                'status' => 'DRAFT',
                'payment_method' => 'BANK_TRANSFER',
                'notes' => $marker,
            ]),
        ];

        foreach ($attempts as $attempt) {
            try {
                $attempt();
                $this->fail('A non-cash financial write must be rejected.');
            } catch (\InvalidArgumentException $exception) {
                $this->assertStringContainsString('Only cash', $exception->getMessage());
            }
        }

        $this->assertDatabaseMissing('payments', ['receipt_no' => $marker]);
        $this->assertDatabaseMissing('service_payments', ['reference' => $marker]);
        $this->assertDatabaseMissing('subscription_payments', ['transaction_reference' => $marker]);
        $this->assertDatabaseMissing('expenses', ['receipt_number' => $marker]);
        $this->assertDatabaseMissing('vendor_payouts', ['reference' => $marker]);
        $this->assertDatabaseMissing('staff_payrolls', ['notes' => $marker]);
    }

    public function test_historical_method_labels_remain_readable_and_are_not_rewritten(): void
    {
        $serviceBooking = ServiceBooking::create([
            'tenant_id' => $this->hall()->tenant_id,
            'customer_id' => $this->customer()->id,
            'booking_reference' => 'RG-SB-LEGACY-'.strtoupper(Str::random(5)),
            'event_date' => now()->addDays(60)->toDateString(),
            'quoted_amount' => 25000,
            'status' => 'CONFIRMED',
        ]);

        $payment = ServicePayment::create([
            'tenant_id' => $serviceBooking->tenant_id,
            'service_booking_id' => $serviceBooking->id,
            'customer_id' => $serviceBooking->customer_id,
            'amount' => 1000,
            'method' => 'CASH',
            'status' => 'PENDING',
            'kind' => 'INSTALLMENT',
        ]);

        // Simulate an existing pre-cash-only row without touching application
        // enforcement, then prove normal reads/edits preserve its label.
        DB::table('service_payments')->where('id', $payment->id)->update(['method' => 'BANK_TRANSFER']);
        $legacy = $payment->fresh();

        $this->assertSame('BANK_TRANSFER', $legacy->method);

        $legacy->update(['notes' => 'Historical record preserved.']);
        $this->assertSame('BANK_TRANSFER', $legacy->fresh()->method);

        try {
            $legacy->update(['method' => 'CHEQUE']);
            $this->fail('A legacy row must not be converted to another non-cash method.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('Only cash', $exception->getMessage());
        }
    }

    public function test_services_reject_non_cash_methods_before_writing_anything(): void
    {
        $tenant = Tenant::query()->findOrFail($this->hall()->tenant_id);
        $owner = $this->owner();
        $customer = $this->customer();
        $plan = SubscriptionPlan::query()->firstOrFail();
        $booking = Booking::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $serviceBooking = ServiceBooking::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'booking_reference' => 'RG-SB-SVC-'.strtoupper(Str::random(5)),
            'event_date' => now()->addDays(60)->toDateString(),
            'quoted_amount' => 25000,
            'status' => 'CONFIRMED',
        ]);

        $marker = 'SVC-'.strtoupper(Str::random(8));

        $attempts = [
            fn () => app(ServiceBookingService::class)->recordServicePayment($serviceBooking, 1000, 'BANK_TRANSFER'),
            fn () => app(SubscriptionService::class)->renewSubscription($tenant, $plan, 'MONTHLY', 1000, 'CHEQUE', $marker),
            fn () => app(ServiceBookingService::class)->requestVendorPayout((string) $tenant->id, (int) $owner->id, 1000, 'WALLET'),
            fn () => app(PaymentService::class)->createPayment(
                (string) $tenant->id,
                (string) $booking->id,
                1000,
                'TOKEN_ADVANCE',
                'STRIPE',
                $marker,
                (string) $owner->id
            ),
        ];

        foreach ($attempts as $attempt) {
            try {
                $attempt();
                $this->fail('A non-cash service write must be rejected.');
            } catch (\InvalidArgumentException $exception) {
                $this->assertStringContainsString('Only cash', $exception->getMessage());
            }
        }

        $this->assertSame(0, ServicePayment::query()->where('service_booking_id', $serviceBooking->id)->count());
        $this->assertDatabaseMissing('subscription_payments', ['transaction_reference' => $marker]);
        $this->assertDatabaseMissing('payments', ['transaction_reference' => $marker]);
    }

    public function test_livewire_financial_forms_reject_non_cash_methods(): void
    {
        $owner = $this->owner();
        $tenantId = $owner->tenant_id;
        $payoutsBefore = VendorPayout::query()->where('tenant_id', $tenantId)->count();

        Livewire::actingAs($owner)
            ->test(ExpenseTracker::class)
            ->set('category', 'GENERATOR_DIESEL')
            ->set('title', 'Strict cash expense')
            ->set('amount', 1000)
            ->set('payment_method', 'BANK_TRANSFER')
            ->call('createExpense')
            ->assertHasErrors('payment_method');

        $payroll = StaffPayroll::create([
            'tenant_id' => $tenantId,
            'user_id' => $owner->id,
            'month_year' => now()->addYears(11)->format('Y-m'),
            'basic_salary' => 10000,
            'net_salary' => 10000,
            'status' => 'APPROVED',
            'payment_method' => 'CASH',
        ]);

        Livewire::actingAs($owner)
            ->test(StaffPayrollComponent::class)
            ->set('payingPayrollId', $payroll->id)
            ->set('payDate', now()->toDateString())
            ->set('payMethod', 'CHEQUE')
            ->call('markAsPaid')
            ->assertHasErrors('payMethod');

        Livewire::actingAs($owner)
            ->test(VendorFinanceBoard::class)
            ->set('payoutAmount', 1000)
            ->set('payoutMethod', 'BANK_TRANSFER')
            ->set('payoutAccountName', 'Cash counter handover')
            ->call('requestPayout')
            ->assertHasErrors('payoutMethod');

        $this->assertSame('APPROVED', $payroll->fresh()->status);
        $this->assertSame(
            $payoutsBefore,
            VendorPayout::query()->where('tenant_id', $tenantId)->count()
        );
    }

    public function test_editing_legacy_financial_rows_preserves_their_historical_method_labels(): void
    {
        $owner = $this->owner();
        $tenantId = $owner->tenant_id;

        $expense = Expense::create([
            'tenant_id' => $tenantId,
            'category' => 'OTHER',
            'title' => 'Legacy expense',
            'amount' => 1000,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'CASH',
            'created_by_user_id' => $owner->id,
        ]);
        DB::table('expenses')->where('id', $expense->id)->update(['payment_method' => 'BANK_TRANSFER']);

        Livewire::actingAs($owner)
            ->test(ExpenseTracker::class)
            ->call('openEditModal', (string) $expense->id)
            ->set('edit_title', 'Legacy expense notes updated')
            ->call('updateExpense')
            ->assertHasNoErrors();

        $this->assertSame(
            'BANK_TRANSFER',
            DB::table('expenses')->where('id', $expense->id)->value('payment_method')
        );

        Livewire::actingAs($owner)
            ->test(ExpenseTracker::class)
            ->call('openEditModal', (string) $expense->id)
            ->set('edit_payment_method', 'CHEQUE')
            ->call('updateExpense')
            ->assertHasErrors('edit_payment_method');

        $this->assertSame(
            'BANK_TRANSFER',
            DB::table('expenses')->where('id', $expense->id)->value('payment_method')
        );

        Livewire::actingAs($owner)
            ->test(ExpenseTracker::class)
            ->call('openEditModal', (string) $expense->id)
            ->set('edit_payment_method', 'CASH')
            ->call('updateExpense')
            ->assertHasNoErrors();

        $this->assertSame(
            'CASH',
            DB::table('expenses')->where('id', $expense->id)->value('payment_method')
        );

        $payroll = StaffPayroll::create([
            'tenant_id' => $tenantId,
            'user_id' => $owner->id,
            'month_year' => now()->addYears(12)->format('Y-m'),
            'basic_salary' => 10000,
            'net_salary' => 10000,
            'status' => 'DRAFT',
            'payment_method' => 'CASH',
        ]);
        DB::table('staff_payrolls')->where('id', $payroll->id)->update(['payment_method' => 'BANK_TRANSFER']);

        Livewire::actingAs($owner)
            ->test(StaffPayrollComponent::class)
            ->call('openEditModal', (string) $payroll->id)
            ->set('editBasicSalary', 12000)
            ->call('updatePayroll')
            ->assertHasNoErrors();

        $this->assertSame(
            'BANK_TRANSFER',
            DB::table('staff_payrolls')->where('id', $payroll->id)->value('payment_method')
        );

        Livewire::actingAs($owner)
            ->test(StaffPayrollComponent::class)
            ->call('openEditModal', (string) $payroll->id)
            ->set('editPaymentMethod', 'CHEQUE')
            ->call('updatePayroll')
            ->assertHasErrors('editPaymentMethod');

        $this->assertSame(
            'BANK_TRANSFER',
            DB::table('staff_payrolls')->where('id', $payroll->id)->value('payment_method')
        );

        Livewire::actingAs($owner)
            ->test(StaffPayrollComponent::class)
            ->call('openEditModal', (string) $payroll->id)
            ->set('editPaymentMethod', 'CASH')
            ->call('updatePayroll')
            ->assertHasNoErrors();

        $this->assertSame(
            'CASH',
            DB::table('staff_payrolls')->where('id', $payroll->id)->value('payment_method')
        );
    }
}
