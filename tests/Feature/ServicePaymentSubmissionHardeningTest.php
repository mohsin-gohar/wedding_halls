<?php

namespace Tests\Feature;

use App\Models\Hall;
use App\Models\ServiceBooking;
use App\Models\ServicePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Customer cash submissions must be race-safe, idempotent and unverified until
 * authorized staff physically confirm the cash.
 */
class ServicePaymentSubmissionHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function customer(): User
    {
        return User::query()->where('role', 'ROLE_CUSTOMER')->firstOrFail();
    }

    private function serviceBooking(?User $customer = null, float $amount = 1000): ServiceBooking
    {
        return ServiceBooking::create([
            'tenant_id' => Hall::query()->firstOrFail()->tenant_id,
            'customer_id' => ($customer ?? $this->customer())->id,
            'booking_reference' => ServiceBooking::generateReference(),
            'event_name' => 'Submission Hardening Event',
            'event_date' => now()->addDays(60)->toDateString(),
            'quoted_amount' => $amount,
            'discount_amount' => 0,
            'status' => 'CONFIRMED',
            'payment_status' => 'UNPAID',
        ]);
    }

    public function test_duplicate_reference_is_an_idempotent_replay(): void
    {
        $booking = $this->serviceBooking();
        $payload = [
            'amount' => 500,
            'method' => 'CASH',
            'kind' => 'INSTALLMENT',
            'reference' => 'CASH-SUBMISSION-IDEMPOTENT',
        ];

        $first = $this->actingAs($this->customer())
            ->postJson("/api/v1/service-bookings/{$booking->id}/payments", $payload);

        $second = $this->actingAs($this->customer())
            ->postJson("/api/v1/service-bookings/{$booking->id}/payments", $payload);

        $first->assertCreated();
        $second->assertOk()->assertJsonPath('replayed', true);

        $this->assertSame(1, ServicePayment::query()->where('service_booking_id', $booking->id)->count());
        $this->assertSame(
            (string) $first->json('payment.id'),
            (string) $second->json('payment.id')
        );
    }

    public function test_pending_submissions_reserve_the_outstanding_balance(): void
    {
        $booking = $this->serviceBooking();

        $this->actingAs($this->customer())
            ->postJson("/api/v1/service-bookings/{$booking->id}/payments", [
                'amount' => 600,
                'method' => 'CASH',
                'reference' => 'CASH-SUBMISSION-A',
            ])->assertCreated();

        // 600 is already awaiting verification; only 400 remains available.
        $this->actingAs($this->customer())
            ->postJson("/api/v1/service-bookings/{$booking->id}/payments", [
                'amount' => 600,
                'method' => 'CASH',
                'reference' => 'CASH-SUBMISSION-B',
            ])->assertStatus(422)
            ->assertJsonPath('message', 'Amount exceeds the outstanding balance of PKR 400.00.');

        $this->assertSame(1, ServicePayment::query()->where('service_booking_id', $booking->id)->count());
        $this->assertSame('0.00', (string) $booking->fresh()->paid_amount);
    }

    public function test_customer_cannot_submit_payment_for_another_customers_booking(): void
    {
        $other = User::factory()->create(['role' => 'ROLE_CUSTOMER', 'status' => 'ACTIVE']);
        $booking = $this->serviceBooking($other);

        $this->actingAs($this->customer())
            ->postJson("/api/v1/service-bookings/{$booking->id}/payments", [
                'amount' => 100,
                'method' => 'CASH',
                'reference' => 'CASH-FORGED-CUSTOMER',
            ])->assertForbidden();

        $this->assertSame(0, ServicePayment::query()->where('service_booking_id', $booking->id)->count());
    }

    public function test_submission_stays_pending_and_never_credits_the_booking(): void
    {
        $booking = $this->serviceBooking();

        $this->actingAs($this->customer())
            ->postJson("/api/v1/service-bookings/{$booking->id}/payments", [
                'amount' => 500,
                'method' => 'CASH',
                'reference' => 'CASH-PENDING-ONLY',
            ])->assertCreated()
            ->assertJsonPath('payment.status', 'PENDING');

        $booking->refresh();

        $this->assertSame('0.00', (string) $booking->paid_amount);
        $this->assertSame(1000.0, $booking->balanceDue());
        $this->assertSame('UNPAID', $booking->payment_status);
    }

    public function test_cancelled_booking_rejects_customer_payment_submission(): void
    {
        $booking = $this->serviceBooking();
        $booking->update(['status' => 'CANCELLED']);

        $this->actingAs($this->customer())
            ->postJson("/api/v1/service-bookings/{$booking->id}/payments", [
                'amount' => 100,
                'method' => 'CASH',
                'reference' => 'CASH-CANCELLED-BOOKING',
            ])->assertStatus(422)
            ->assertJsonPath('message', 'This service booking is no longer accepting payments.');

        $this->assertSame(0, ServicePayment::query()->where('service_booking_id', $booking->id)->count());
    }
}
