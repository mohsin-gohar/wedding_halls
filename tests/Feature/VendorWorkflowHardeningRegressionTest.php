<?php

namespace Tests\Feature;

use App\Exceptions\ServiceBookingException;
use App\Livewire\CustomerDashboard;
use App\Livewire\VendorFinanceBoard;
use App\Livewire\VendorPortfolioManager;
use App\Livewire\VendorQuotesBoard;
use App\Models\AuditLog;
use App\Models\RFQ;
use App\Models\RfqTarget;
use App\Models\ServiceBooking;
use App\Models\ServicePayment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VendorPayout;
use App\Models\VendorPortfolioMedia;
use App\Models\VendorWallet;
use App\Models\WalletTransaction;
use App\Services\ServiceBookingService;
use App\Support\PermissionMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Focused regressions for the vendor-workflow hardening pass:
 * staff-only cash settlement, payout balance guards, permission-aligned ERP
 * access, target-aware RFQ inboxes, correct media disk deletes and removal of
 * the duplicate vendor-side award path.
 */
class VendorWorkflowHardeningRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_verify_a_pending_cash_payment_and_settle_the_wallet(): void
    {
        [$tenant, $customer, $staff] = $this->makeTenantFixtures(10.0);
        $wallet = $this->makeWallet($tenant, $staff, 0);
        $booking = $this->makeBooking($tenant, $customer);

        $payment = ServicePayment::create([
            'tenant_id' => $tenant->id,
            'service_booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'amount' => 1000,
            'method' => 'CASH',
            'status' => 'PENDING',
            'kind' => 'FULL',
            'reference' => 'CASH-RECEIPT-001',
        ]);

        // Nothing may move before a staff member verifies physical cash.
        $this->assertSame('PENDING', $payment->fresh()->status);
        $this->assertSame('0.00', (string) $booking->fresh()->paid_amount);
        $this->assertSame('0.00', (string) $wallet->fresh()->balance);

        $this->actingAs($staff)
            ->post(route('vendor.payments.verify', $payment))
            ->assertRedirect();

        $this->assertSame('VERIFIED', $payment->fresh()->status);
        $this->assertSame((int) $staff->id, (int) $payment->fresh()->verified_by);
        $this->assertNotNull($payment->fresh()->verified_at);

        $booking->refresh();
        $this->assertSame('1000.00', (string) $booking->paid_amount);
        $this->assertSame('PAID', $booking->payment_status);

        $this->assertSame('900.00', (string) $wallet->fresh()->balance);
        $this->assertDatabaseHas('vendor_payouts', [
            'tenant_id' => $tenant->id,
            'amount' => 900,
            'status' => 'REQUESTED',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'SERVICE_PAYMENT_VERIFIED',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_customer_cannot_verify_a_service_payment(): void
    {
        [$tenant, $customer, $staff] = $this->makeTenantFixtures(10.0);
        $wallet = $this->makeWallet($tenant, $staff, 0);
        $booking = $this->makeBooking($tenant, $customer);

        $payment = ServicePayment::create([
            'tenant_id' => $tenant->id,
            'service_booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'amount' => 1000,
            'method' => 'CASH',
            'status' => 'PENDING',
            'kind' => 'FULL',
        ]);

        $this->actingAs($customer)
            ->post(route('vendor.payments.verify', $payment))
            ->assertRedirect(route('customer.dashboard'));

        $this->assertSame('PENDING', $payment->fresh()->status);
        $this->assertSame('0.00', (string) $booking->fresh()->paid_amount);
        $this->assertSame('0.00', (string) $wallet->fresh()->balance);
        $this->assertSame(0, AuditLog::query()->where('action', 'SERVICE_PAYMENT_VERIFIED')->count());
    }

    public function test_over_balance_payout_requests_and_processing_are_rejected(): void
    {
        [$tenant, , $staff] = $this->makeTenantFixtures(10.0);
        $wallet = $this->makeWallet($tenant, $staff, 50);

        try {
            app(ServiceBookingService::class)->requestVendorPayout((string) $tenant->id, (int) $staff->id, 100);
            $this->fail('A payout request exceeding the wallet balance must be rejected.');
        } catch (ServiceBookingException $e) {
            $this->assertStringContainsString('insufficient available balance', $e->getMessage());
        }

        $this->assertSame(0, VendorPayout::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame('50.00', (string) $wallet->fresh()->balance);

        // Direct creation bypasses the request guard; processing must still
        // refuse to overdraw and must never mark the payout as PAID.
        $payout = VendorPayout::create([
            'tenant_id' => $tenant->id,
            'amount' => 100,
            'method' => 'CASH',
            'status' => 'REQUESTED',
            'requested_at' => now(),
        ]);

        try {
            app(ServiceBookingService::class)->processVendorPayout($payout, (int) $staff->id);
            $this->fail('A payout exceeding the wallet balance must not be processed.');
        } catch (ServiceBookingException $e) {
            $this->assertStringContainsString('insufficient balance', $e->getMessage());
        }

        $this->assertSame('REQUESTED', $payout->fresh()->status);
        $this->assertSame('50.00', (string) $wallet->fresh()->balance);
    }

    public function test_accountant_can_open_finance_but_cannot_verify_payments(): void
    {
        [$tenant, $customer, $staff] = $this->makeTenantFixtures(10.0);
        $this->makeWallet($tenant, $staff, 100);
        $booking = $this->makeBooking($tenant, $customer);

        $payment = ServicePayment::create([
            'tenant_id' => $tenant->id,
            'service_booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'amount' => 500,
            'method' => 'CASH',
            'status' => 'PENDING',
            'kind' => 'INSTALLMENT',
        ]);

        $accountant = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_ACCOUNTANT',
            'status' => 'ACTIVE',
        ]);

        $this->assertSame(1, ServicePayment::query()
            ->where('tenant_id', $tenant->id)
            ->where('method', 'CASH')
            ->where('status', 'PENDING')
            ->count());

        $this->actingAs($accountant)
            ->get(route('vendor.finance'))
            ->assertOk();

        Livewire::actingAs($accountant)
            ->test(VendorFinanceBoard::class)
            ->assertDontSee('Verify cash')
            ->assertDontSee('Payments awaiting verification');

        $this->actingAs($accountant)
            ->post(route('vendor.payments.verify', $payment))
            ->assertForbidden();

        $this->assertSame('PENDING', $payment->fresh()->status);
        $this->assertSame('0.00', (string) $booking->fresh()->paid_amount);
        $this->assertFalse(PermissionMatrix::userCan($accountant, 'payments.verify'));
    }

    public function test_targeted_rfq_is_visible_only_to_the_targeted_vendor(): void
    {
        [$tenantA, $customer, $ownerA] = $this->makeTenantFixtures(10.0, 'Alpha Catering');
        [$tenantB, , $ownerB] = $this->makeTenantFixtures(10.0, 'Beta Events');

        $rfq = RFQ::create([
            'client_id' => $customer->id,
            'tenant_id' => $tenantB->id,
            'title' => 'Targeted Wedding Banquet RFQ',
            'event_date' => now()->addDays(60)->toDateString(),
            'city' => 'Karachi',
            'guest_count' => 300,
            'budget_range' => 'PKR 1,000,000 - 2,000,000',
            'requirements' => 'A detailed targeted catering requirement for the wedding reception.',
            'status' => 'OPEN',
        ]);

        RfqTarget::create([
            'rfq_id' => $rfq->id,
            'tenant_id' => $tenantA->id,
        ]);

        Livewire::actingAs($ownerA)
            ->test(VendorQuotesBoard::class)
            ->assertSee('Targeted Wedding Banquet RFQ');

        // The legacy tenant_id owner still sees the RFQ (backward compatible),
        // but an unrelated tenant that was never targeted must not.
        Livewire::actingAs($ownerB)
            ->test(VendorQuotesBoard::class)
            ->assertSee('Targeted Wedding Banquet RFQ');

        [$tenantC, , $ownerC] = $this->makeTenantFixtures(10.0, 'Gamma Decor');
        Livewire::actingAs($ownerC)
            ->test(VendorQuotesBoard::class)
            ->assertDontSee('Targeted Wedding Banquet RFQ');
    }

    public function test_portfolio_media_is_deleted_from_the_public_disk(): void
    {
        Storage::fake('public');

        [$tenant, , $owner] = $this->makeTenantFixtures(10.0);
        $path = 'vendor-portfolio/riwaj-test.jpg';
        Storage::disk('public')->put($path, 'fake-image-bytes');

        $media = VendorPortfolioMedia::create([
            'tenant_id' => $tenant->id,
            'type' => 'IMAGE',
            'path' => $path,
            'caption' => 'Test portfolio image',
        ]);

        Livewire::actingAs($owner)
            ->test(VendorPortfolioManager::class)
            ->call('removeMedia', $media->id)
            ->assertHasNoErrors();

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('vendor_portfolio_media', ['id' => $media->id]);
    }

    public function test_legacy_non_cash_payment_cannot_be_verified(): void
    {
        [$tenant, $customer, $staff] = $this->makeTenantFixtures(10.0);
        $this->makeWallet($tenant, $staff, 0);
        $booking = $this->makeBooking($tenant, $customer);

        // Simulate a historical row: new writes are blocked by the model guard,
        // so bypass only model events to seed the legacy label for this test.
        $payment = ServicePayment::withoutEvents(fn () => ServicePayment::create([
            'tenant_id' => $tenant->id,
            'service_booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'amount' => 1000,
            'method' => 'BANK_TRANSFER',
            'status' => 'PENDING',
            'kind' => 'FULL',
        ]));

        $this->actingAs($staff)
            ->post(route('vendor.payments.verify', $payment))
            ->assertStatus(422);

        $this->assertSame('PENDING', $payment->fresh()->status);
        $this->assertSame('0.00', (string) $booking->fresh()->paid_amount);
        $this->assertSame(0, AuditLog::query()->where('action', 'SERVICE_PAYMENT_VERIFIED')->count());
    }

    public function test_cross_tenant_payment_verification_is_rejected(): void
    {
        [$tenantA, , $staffA] = $this->makeTenantFixtures(10.0, 'Tenant A');
        [$tenantB, $customerB] = $this->makeTenantFixtures(10.0, 'Tenant B');
        $bookingB = $this->makeBooking($tenantB, $customerB);

        $payment = ServicePayment::create([
            'tenant_id' => $tenantB->id,
            'service_booking_id' => $bookingB->id,
            'customer_id' => $customerB->id,
            'amount' => 1000,
            'method' => 'CASH',
            'status' => 'PENDING',
            'kind' => 'FULL',
        ]);

        $this->actingAs($staffA)
            ->post(route('vendor.payments.verify', $payment))
            ->assertNotFound();

        $this->assertSame('PENDING', $payment->fresh()->status);
        $this->assertSame(0, AuditLog::query()->where('action', 'SERVICE_PAYMENT_VERIFIED')->count());
    }

    public function test_two_pending_cash_payments_on_one_booking_settle_only_once(): void
    {
        [$tenant, $customer, $staff] = $this->makeTenantFixtures(10.0);
        $wallet = $this->makeWallet($tenant, $staff, 0);
        $booking = $this->makeBooking($tenant, $customer);

        foreach (['CASH-A', 'CASH-B'] as $reference) {
            $payment = ServicePayment::create([
                'tenant_id' => $tenant->id,
                'service_booking_id' => $booking->id,
                'customer_id' => $customer->id,
                'amount' => 500,
                'method' => 'CASH',
                'status' => 'PENDING',
                'kind' => 'FULL',
                'reference' => $reference,
            ]);

            $result = app(ServiceBookingService::class)->verifyServicePayment($payment, (int) $staff->id);
            $this->assertTrue($result['success']);
        }

        $this->assertSame('1000.00', (string) $booking->fresh()->paid_amount);
        $this->assertSame('PAID', $booking->fresh()->payment_status);
        $this->assertSame('900.00', (string) $wallet->fresh()->balance);
        $this->assertSame(1, VendorPayout::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(1, WalletTransaction::query()
            ->where('service_booking_id', $booking->id)
            ->where('source', 'BOOKING_PAYMENT')
            ->count());
    }

    public function test_reverifying_a_cash_payment_is_a_no_op_with_one_audit_entry(): void
    {
        [$tenant, $customer, $staff] = $this->makeTenantFixtures(10.0);
        $wallet = $this->makeWallet($tenant, $staff, 0);
        $booking = $this->makeBooking($tenant, $customer);

        $payment = ServicePayment::create([
            'tenant_id' => $tenant->id,
            'service_booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'amount' => 1000,
            'method' => 'CASH',
            'status' => 'PENDING',
            'kind' => 'FULL',
        ]);

        $service = app(ServiceBookingService::class);
        $this->assertTrue($service->verifyServicePayment($payment, (int) $staff->id)['success']);
        $second = $service->verifyServicePayment($payment->fresh(), (int) $staff->id);

        $this->assertTrue($second['success']);
        $this->assertTrue($second['already_verified']);
        $this->assertSame(1, AuditLog::query()->where('action', 'SERVICE_PAYMENT_VERIFIED')->count());
        $this->assertSame(1, VendorPayout::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame('900.00', (string) $wallet->fresh()->balance);
    }

    public function test_outstanding_payout_requests_reserve_the_wallet_balance(): void
    {
        [$tenant, , $staff] = $this->makeTenantFixtures(10.0);
        $this->makeWallet($tenant, $staff, 100);

        $service = app(ServiceBookingService::class);
        $service->requestVendorPayout((string) $tenant->id, (int) $staff->id, 60);

        try {
            $service->requestVendorPayout((string) $tenant->id, (int) $staff->id, 60);
            $this->fail('Overlapping payout requests must not both pass the balance guard.');
        } catch (ServiceBookingException $e) {
            $this->assertStringContainsString('insufficient available balance', $e->getMessage());
        }

        $this->assertSame(1, VendorPayout::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_targeted_vendor_cannot_close_another_tenants_rfq(): void
    {
        [, , $ownerA] = $this->makeTenantFixtures(10.0, 'Tenant A');
        [$tenantB, $customerB] = $this->makeTenantFixtures(10.0, 'Tenant B');

        $rfq = RFQ::create([
            'client_id' => $customerB->id,
            'tenant_id' => $tenantB->id,
            'title' => 'RFQ owned by tenant B',
            'event_date' => now()->addDays(60)->toDateString(),
            'city' => 'Karachi',
            'guest_count' => 250,
            'budget_range' => 'PKR 500,000 - 1,000,000',
            'requirements' => 'A detailed requirement used to verify cross-tenant decline protection.',
            'status' => 'OPEN',
        ]);

        RfqTarget::create(['rfq_id' => $rfq->id, 'tenant_id' => $ownerA->tenant_id]);

        Livewire::actingAs($ownerA)
            ->test(VendorQuotesBoard::class)
            ->call('declineRfq', $rfq->id)
            ->assertStatus(404);

        $this->assertSame('OPEN', $rfq->fresh()->status);
    }

    public function test_vendor_side_duplicate_award_path_has_been_removed(): void
    {
        $this->assertFalse(method_exists(VendorQuotesBoard::class, 'awardQuote'));

        $blade = file_get_contents(resource_path('views/livewire/vendor-quotes-board.blade.php'));
        $this->assertStringNotContainsString('awardQuote(', $blade);

        // Awarding remains a customer-only action.
        $this->assertTrue(method_exists(CustomerDashboard::class, 'acceptQuote'));
    }

    /**
     * @return array{0: Tenant, 1: User, 2: User}
     */
    private function makeTenantFixtures(float $commissionRate, string $name = 'Hardening Banquets'): array
    {
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => 'hardening-'.uniqid(),
            'email' => 'hardening@riwaj.pk',
            'phone' => '+92 300 1112233',
            'city' => 'Karachi',
            'subscription_plan' => 'STARTER',
            'currency' => 'PKR',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);
        $tenant->commission_rate = $commissionRate;
        $tenant->save();

        $customer = User::factory()->create([
            'role' => 'ROLE_CUSTOMER',
            'status' => 'ACTIVE',
        ]);

        $staff = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_TENANT_OWNER',
            'status' => 'ACTIVE',
        ]);

        return [$tenant, $customer, $staff];
    }

    private function makeWallet(Tenant $tenant, User $owner, float $balance): VendorWallet
    {
        return VendorWallet::create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'wallet_provider' => 'BANK_TRANSFER',
            'account_id' => 'WH-'.uniqid(),
            'account_name' => 'Hardening Wallet',
            'balance' => $balance,
            'is_primary' => true,
        ]);
    }

    private function makeBooking(Tenant $tenant, User $customer): ServiceBooking
    {
        return ServiceBooking::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'booking_reference' => ServiceBooking::generateReference(),
            'event_name' => 'Hardening Regression Event',
            'event_date' => now()->addDays(45)->toDateString(),
            'quoted_amount' => 1000,
            'discount_amount' => 0,
            'commission_amount' => 100,
            'net_vendor_amount' => 900,
            'advance_amount' => 1000,
            'status' => 'CONFIRMED',
            'payment_status' => 'UNPAID',
        ]);
    }
}
