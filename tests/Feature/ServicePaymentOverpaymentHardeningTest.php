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

/**
 * Verification is the last financial gate. Two pending submissions created by
 * an older caller (or by staff) must never push paid_amount past the total.
 */
class ServicePaymentOverpaymentHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequential_verification_cannot_exceed_the_booking_balance(): void
    {
        [$tenant, $customer, $staff] = $this->fixtures();
        $wallet = $this->wallet($tenant, $staff);
        $booking = $this->booking($tenant, $customer, 1000);

        $first = $this->pendingPayment($booking, 800, 'CASH-OVER-A');
        $second = $this->pendingPayment($booking, 800, 'CASH-OVER-B');

        $service = app(ServiceBookingService::class);

        $this->assertTrue($service->verifyServicePayment($first, (int) $staff->id)['success']);

        try {
            $service->verifyServicePayment($second, (int) $staff->id);
            $this->fail('The second pending payment must not push paid_amount over the total.');
        } catch (ServiceBookingException $exception) {
            $this->assertStringContainsString('outstanding balance', $exception->getMessage());
        }

        $booking->refresh();

        $this->assertSame('800.00', (string) $booking->paid_amount);
        $this->assertSame('PARTIAL', $booking->payment_status);
        $this->assertSame('PENDING', $second->fresh()->status);
        $this->assertSame('0.00', (string) $wallet->fresh()->balance);
        $this->assertSame(0, WalletTransaction::query()->where('source', 'BOOKING_PAYMENT')->count());
        $this->assertSame(0, VendorPayout::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_refund_cannot_exceed_the_amount_already_paid(): void
    {
        [$tenant, $customer, $staff] = $this->fixtures();
        $booking = $this->booking($tenant, $customer, 1000);
        $booking->update(['paid_amount' => 500, 'payment_status' => 'PARTIAL']);

        $refund = ServicePayment::create([
            'tenant_id' => $tenant->id,
            'service_booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'amount' => 600,
            'method' => 'CASH',
            'status' => 'PENDING',
            'kind' => 'REFUND',
            'reference' => 'CASH-REFUND-OVER',
        ]);

        $this->expectException(ServiceBookingException::class);
        $this->expectExceptionMessage('Refund exceeds the amount already paid');

        app(ServiceBookingService::class)->verifyServicePayment($refund, (int) $staff->id);
    }

    public function test_direct_verified_payment_rejects_overpayment_and_leaves_no_row(): void
    {
        [$tenant, $customer] = $this->fixtures();
        $booking = $this->booking($tenant, $customer, 1000);

        try {
            app(ServiceBookingService::class)->recordServicePayment($booking, 1001, 'CASH', 'FULL');
            $this->fail('A direct verified payment must not exceed the booking total.');
        } catch (ServiceBookingException $exception) {
            $this->assertStringContainsString('outstanding balance', $exception->getMessage());
        }

        $this->assertSame(0, ServicePayment::query()->where('service_booking_id', $booking->id)->count());
        $this->assertSame('0.00', (string) $booking->fresh()->paid_amount);
    }

    public function test_direct_verified_payment_rejects_zero_invalid_kind_and_cancelled_booking(): void
    {
        [$tenant, $customer] = $this->fixtures();
        $booking = $this->booking($tenant, $customer, 1000);
        $service = app(ServiceBookingService::class);

        foreach ([
            fn () => $service->recordServicePayment($booking, 0, 'CASH'),
            fn () => $service->recordServicePayment($booking, 100, 'CASH', 'REFUND'),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('Invalid direct payment input must be rejected.');
            } catch (\InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }

        $booking->update(['status' => 'CANCELLED']);

        $this->expectException(ServiceBookingException::class);
        $this->expectExceptionMessage('not in a payable state');

        $service->recordServicePayment($booking->fresh(), 100, 'CASH');
    }

    public function test_refund_verification_updates_the_booking_payment_status(): void
    {
        [$tenant, $customer, $staff] = $this->fixtures();
        $booking = $this->booking($tenant, $customer, 1000);
        $booking->update(['paid_amount' => 1000, 'payment_status' => 'PAID']);

        $partial = ServicePayment::create([
            'tenant_id' => $tenant->id,
            'service_booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'amount' => 400,
            'method' => 'CASH',
            'status' => 'PENDING',
            'kind' => 'REFUND',
            'reference' => 'CASH-REFUND-PARTIAL',
        ]);

        app(ServiceBookingService::class)->verifyServicePayment($partial, (int) $staff->id);

        $booking->refresh();
        $this->assertSame('600.00', (string) $booking->paid_amount);
        $this->assertSame('PARTIAL', $booking->payment_status);

        $remaining = ServicePayment::create([
            'tenant_id' => $tenant->id,
            'service_booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'amount' => 600,
            'method' => 'CASH',
            'status' => 'PENDING',
            'kind' => 'REFUND',
            'reference' => 'CASH-REFUND-REMAINING',
        ]);

        app(ServiceBookingService::class)->verifyServicePayment($remaining, (int) $staff->id);

        $booking->refresh();
        $this->assertSame('0.00', (string) $booking->paid_amount);
        $this->assertSame('REFUNDED', $booking->payment_status);
    }

    /**
     * @return array{0: Tenant, 1: User, 2: User}
     */
    private function fixtures(): array
    {
        $tenant = Tenant::create([
            'name' => 'Overpayment Banquets '.uniqid(),
            'slug' => 'overpayment-'.uniqid(),
            'email' => 'overpayment@riwaj.pk',
            'verification_status' => 'APPROVED',
            'subscription_status' => 'ACTIVE',
            'is_active' => true,
        ]);

        $customer = User::factory()->create(['role' => 'ROLE_CUSTOMER', 'status' => 'ACTIVE']);
        $staff = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_TENANT_OWNER',
            'status' => 'ACTIVE',
        ]);

        return [$tenant, $customer, $staff];
    }

    private function wallet(Tenant $tenant, User $staff): VendorWallet
    {
        return VendorWallet::create([
            'tenant_id' => $tenant->id,
            'user_id' => $staff->id,
            'wallet_provider' => 'BANK_TRANSFER',
            'account_id' => 'WH-'.uniqid(),
            'account_name' => 'Overpayment Wallet',
            'balance' => 0,
            'is_primary' => true,
        ]);
    }

    private function booking(Tenant $tenant, User $customer, float $quoted): ServiceBooking
    {
        return ServiceBooking::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'booking_reference' => ServiceBooking::generateReference(),
            'event_name' => 'Overpayment Regression Event',
            'event_date' => now()->addDays(45)->toDateString(),
            'quoted_amount' => $quoted,
            'discount_amount' => 0,
            'status' => 'CONFIRMED',
            'payment_status' => 'UNPAID',
        ]);
    }

    private function pendingPayment(ServiceBooking $booking, float $amount, string $reference): ServicePayment
    {
        return ServicePayment::create([
            'tenant_id' => $booking->tenant_id,
            'service_booking_id' => $booking->id,
            'customer_id' => $booking->customer_id,
            'amount' => $amount,
            'method' => 'CASH',
            'status' => 'PENDING',
            'kind' => 'INSTALLMENT',
            'reference' => $reference,
        ]);
    }
}
