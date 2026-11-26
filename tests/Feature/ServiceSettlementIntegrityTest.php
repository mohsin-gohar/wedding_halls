<?php

namespace Tests\Feature;

use App\Exceptions\ServiceBookingException;
use App\Models\ServiceBooking;
use App\Models\ServicePayment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VendorPayout;
use App\Models\VendorWallet;
use App\Models\WalletTransaction;
use App\Services\ServiceBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceSettlementIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_settlement_credits_gross_posts_a_real_commission_debit_and_requests_net_payout(): void
    {
        [$tenant, $customer] = $this->makeFixtures(10.0);
        $wallet = $this->makeWallet($tenant, 0);
        $booking = $this->makeBooking($tenant, $customer, 1000, 100, 900);

        // A rate change after booking must not rewrite the settled amounts.
        $tenant->commission_rate = 50;
        $tenant->save();

        app(ServiceBookingService::class)->recordServicePayment($booking, 1000, 'CASH', 'FULL');

        $this->assertSame('900.00', (string) $wallet->fresh()->balance);

        $credit = WalletTransaction::query()
            ->where('vendor_wallet_id', $wallet->id)
            ->where('source', 'BOOKING_PAYMENT')
            ->where('type', 'CREDIT')
            ->firstOrFail();
        $this->assertSame('1000.00', (string) $credit->amount);
        $this->assertSame('1000.00', (string) $credit->balance_after);

        $commission = WalletTransaction::query()
            ->where('vendor_wallet_id', $wallet->id)
            ->where('source', 'COMMISSION')
            ->where('type', 'DEBIT')
            ->firstOrFail();
        $this->assertSame('100.00', (string) $commission->amount);
        $this->assertSame('900.00', (string) $commission->balance_after);
        $this->assertSame((string) $booking->id, (string) $commission->service_booking_id);

        // The last ledger balance must equal the stored wallet balance.
        $latest = WalletTransaction::query()
            ->where('vendor_wallet_id', $wallet->id)
            ->latest('created_at')
            ->firstOrFail();
        $this->assertSame((string) $wallet->fresh()->balance, (string) $latest->balance_after);

        $this->assertDatabaseHas('vendor_payouts', [
            'tenant_id' => $tenant->id,
            'amount' => 900,
            'status' => 'REQUESTED',
        ]);
    }

    public function test_wallet_debit_rejects_another_tenants_wallet(): void
    {
        [$tenantA, $customerA] = $this->makeFixtures(10.0);
        [$tenantB] = $this->makeFixtures(10.0);

        $foreignWallet = $this->makeWallet($tenantB, 500);
        $payout = VendorPayout::create([
            'tenant_id' => $tenantA->id,
            'amount' => 100,
            'method' => 'CASH',
            'status' => 'REQUESTED',
            'requested_at' => now(),
        ]);

        // The service resolves the wallet from the payout's tenant, so the
        // foreign wallet is never selected. Force the mismatch through a
        // payout whose tenant has no wallet to prove the guard is enforced.
        try {
            app(ServiceBookingService::class)->processVendorPayout($payout, 1);
            $this->fail('A payout without a wallet in its own tenant must not be processed.');
        } catch (ServiceBookingException $e) {
            $this->assertStringContainsString('No primary vendor wallet', $e->getMessage());
        }

        $this->assertSame('500.00', (string) $foreignWallet->fresh()->balance);
        $this->assertSame('REQUESTED', $payout->fresh()->status);
        $this->assertSame(0, WalletTransaction::query()->where('vendor_wallet_id', $foreignWallet->id)->count());
    }

    public function test_payout_cannot_overdraw_the_wallet_and_is_not_marked_paid_on_failure(): void
    {
        [$tenant] = $this->makeFixtures(10.0);
        $wallet = $this->makeWallet($tenant, 50);

        $payout = VendorPayout::create([
            'tenant_id' => $tenant->id,
            'amount' => 100,
            'method' => 'CASH',
            'status' => 'REQUESTED',
            'requested_at' => now(),
        ]);

        try {
            app(ServiceBookingService::class)->processVendorPayout($payout, 1);
            $this->fail('A payout exceeding the wallet balance must be rejected.');
        } catch (ServiceBookingException $e) {
            $this->assertStringContainsString('insufficient balance', $e->getMessage());
        }

        $this->assertSame('50.00', (string) $wallet->fresh()->balance);
        $this->assertSame('REQUESTED', $payout->fresh()->status);
        $this->assertSame(0, WalletTransaction::query()->where('type', 'DEBIT')->where('source', 'PAYOUT')->count());
    }

    public function test_cash_refund_is_recorded_even_when_the_vendor_wallet_has_no_credit(): void
    {
        [$tenant, $customer] = $this->makeFixtures(10.0);
        $wallet = $this->makeWallet($tenant, 0);
        $booking = $this->makeBooking($tenant, $customer, 1000, 100, 900);
        $booking->update(['paid_amount' => 500, 'payment_status' => 'PARTIAL']);

        // The customer's cash refund is an obligation independent of the
        // vendor wallet balance. A never-settled partial payment must still be
        // refundable; only the (zero) wallet reversal is skipped.
        $cancelled = app(ServiceBookingService::class)->cancelServiceBooking($booking->id, null, $tenant->id);

        $this->assertSame('CANCELLED', $cancelled->status);
        $this->assertSame('0.00', (string) $cancelled->paid_amount);
        $this->assertSame('REFUNDED', $cancelled->payment_status);
        $this->assertSame('0.00', (string) $wallet->fresh()->balance);
        $this->assertSame(1, ServicePayment::query()->where('kind', 'REFUND')->count());
        $this->assertSame(0, WalletTransaction::query()->where('source', 'REFUND')->count());
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'SERVICE_BOOKING_CANCELLED',
            'entity_id' => (string) $booking->id,
        ]);
    }

    public function test_failed_commission_debit_rolls_back_wallet_credit_and_booking_payment(): void
    {
        [$tenant, $customer] = $this->makeFixtures(10.0);
        $wallet = $this->makeWallet($tenant, 0);
        // A corrupted commission snapshot larger than the gross forces the
        // commission debit to fail after the gross has been credited.
        $booking = $this->makeBooking($tenant, $customer, 1000, 2000, -1000);

        try {
            app(ServiceBookingService::class)->recordServicePayment($booking, 1000, 'CASH', 'FULL');
            $this->fail('A settlement whose commission debit fails must not partially commit.');
        } catch (ServiceBookingException $e) {
            $this->assertStringContainsString('insufficient balance', $e->getMessage());
        }

        $booking->refresh();
        $this->assertSame('0.00', (string) $booking->paid_amount);
        $this->assertSame('UNPAID', $booking->payment_status);
        $this->assertSame('0.00', (string) $wallet->fresh()->balance);
        $this->assertSame(0, ServicePayment::query()->count());
        $this->assertSame(0, WalletTransaction::query()->count());
        $this->assertSame(0, VendorPayout::query()->count());
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function makeFixtures(float $commissionRate): array
    {
        $tenant = Tenant::create([
            'name' => 'Settlement Integrity '.uniqid(),
            'slug' => 'settlement-'.uniqid(),
            'email' => 'settlement@riwaj.pk',
            'phone' => '+92 300 9998877',
            'city' => 'Karachi',
            'subscription_plan' => 'STARTER',
            'currency' => 'PKR',
            'verification_status' => 'APPROVED',
            'is_active' => true,
            'commission_rate' => $commissionRate,
        ]);

        $customer = User::factory()->create([
            'role' => 'ROLE_CUSTOMER',
            'status' => 'ACTIVE',
        ]);

        return [$tenant, $customer];
    }

    private function makeWallet(Tenant $tenant, float $balance): VendorWallet
    {
        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_TENANT_OWNER',
            'status' => 'ACTIVE',
        ]);

        return VendorWallet::create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'wallet_provider' => 'BANK_TRANSFER',
            'account_id' => 'SB-'.uniqid(),
            'account_name' => 'Settlement Account',
            'balance' => $balance,
            'is_primary' => true,
        ]);
    }

    private function makeBooking(Tenant $tenant, User $customer, float $quoted, float $commission, float $net): ServiceBooking
    {
        return ServiceBooking::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'booking_reference' => ServiceBooking::generateReference(),
            'event_name' => 'Integrity Test Event',
            'event_date' => now()->addDays(30)->toDateString(),
            'quoted_amount' => $quoted,
            'discount_amount' => 0,
            'commission_amount' => $commission,
            'net_vendor_amount' => $net,
            'advance_amount' => $quoted,
            'status' => 'CONFIRMED',
            'payment_status' => 'UNPAID',
        ]);
    }
}
