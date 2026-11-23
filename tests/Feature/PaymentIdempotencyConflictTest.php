<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\Payment;
use App\Models\ServiceBooking;
use App\Models\ServicePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Idempotency-key semantics for cash financial writes.
 *
 * A replayed reference may only return the original row when the payload is
 * byte-identical. Reusing a reference with a different amount or type must be
 * rejected and must never be reported as a successful duplicate.
 */
class PaymentIdempotencyConflictTest extends TestCase
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
        $owner->givePermissionTo(['payments.record', 'payments.refund']);

        return $owner->fresh();
    }

    private function customer(): User
    {
        return User::query()->where('role', 'ROLE_CUSTOMER')->firstOrFail();
    }

    private function booking(float $total = 20000): Booking
    {
        $hall = $this->hall();

        return Booking::create([
            'tenant_id' => $hall->tenant_id,
            'hall_id' => $hall->id,
            'booking_reference' => 'BK-'.now()->format('Ym').'-'.strtoupper(Str::random(5)),
            'customer_id' => $this->customer()->id,
            'customer_name' => 'Idempotency Conflict Customer',
            'customer_phone' => '+92 300 1112244',
            'event_type' => 'Barat',
            'event_date' => now()->addDays(160)->toDateString(),
            'slot_type' => 'EVENING',
            'guest_count' => 200,
            'grand_total' => $total,
            'advance_paid' => 0,
            'balance_due' => $total,
            'status' => 'CONFIRMED',
            'pipeline_stage' => 'CONFIRMED',
        ]);
    }

    private function serviceBooking(float $quoted = 1000): ServiceBooking
    {
        return ServiceBooking::create([
            'tenant_id' => $this->hall()->tenant_id,
            'customer_id' => $this->customer()->id,
            'booking_reference' => ServiceBooking::generateReference(),
            'event_name' => 'Idempotency Conflict Service',
            'event_date' => now()->addDays(60)->toDateString(),
            'quoted_amount' => $quoted,
            'discount_amount' => 0,
            'status' => 'CONFIRMED',
            'payment_status' => 'UNPAID',
        ]);
    }

    public function test_reusing_a_reference_with_a_different_amount_is_rejected(): void
    {
        $booking = $this->booking();
        $owner = $this->owner();

        $this->actingAs($owner)->postJson('/api/v1/payments/record', [
            'booking_id' => $booking->id,
            'amount' => 5000,
            'payment_type' => 'TOKEN_ADVANCE',
            'transaction_reference' => 'CASH-CONFLICT-AMOUNT',
        ])->assertCreated();

        $this->actingAs($owner)->postJson('/api/v1/payments/record', [
            'booking_id' => $booking->id,
            'amount' => 6000,
            'payment_type' => 'TOKEN_ADVANCE',
            'transaction_reference' => 'CASH-CONFLICT-AMOUNT',
        ])->assertStatus(422)
            ->assertJsonPath(
                'message',
                'This payment reference was already used for a different payment amount or type. Please use a new reference.'
            );

        $this->assertSame(1, Payment::query()->where('booking_id', $booking->id)->count());
        $this->assertEquals(5000.0, (float) $booking->fresh()->advance_paid);
    }

    public function test_reusing_a_reference_with_a_different_payment_type_is_rejected(): void
    {
        $booking = $this->booking();
        $owner = $this->owner();

        $this->actingAs($owner)->postJson('/api/v1/payments/record', [
            'booking_id' => $booking->id,
            'amount' => 5000,
            'payment_type' => 'TOKEN_ADVANCE',
            'transaction_reference' => 'CASH-CONFLICT-TYPE',
        ])->assertCreated();

        $this->actingAs($owner)->postJson('/api/v1/payments/record', [
            'booking_id' => $booking->id,
            'amount' => 5000,
            'payment_type' => 'SECOND_INSTALLMENT',
            'transaction_reference' => 'CASH-CONFLICT-TYPE',
        ])->assertStatus(422);

        $this->assertSame(1, Payment::query()->where('booking_id', $booking->id)->count());
        $this->assertEquals(5000.0, (float) $booking->fresh()->advance_paid);
    }

    public function test_refund_reference_conflict_is_rejected(): void
    {
        $booking = $this->booking();
        $owner = $this->owner();

        $this->actingAs($owner)->postJson('/api/v1/payments/record', [
            'booking_id' => $booking->id,
            'amount' => 8000,
            'payment_type' => 'TOKEN_ADVANCE',
        ])->assertCreated();

        $this->actingAs($owner)->postJson('/api/v1/payments/refund', [
            'booking_id' => $booking->id,
            'amount' => 3000,
            'transaction_reference' => 'CASH-REFUND-CONFLICT',
        ])->assertCreated();

        $this->actingAs($owner)->postJson('/api/v1/payments/refund', [
            'booking_id' => $booking->id,
            'amount' => 1000,
            'transaction_reference' => 'CASH-REFUND-CONFLICT',
        ])->assertStatus(422);

        $this->assertSame(
            1,
            Payment::query()->where('booking_id', $booking->id)->where('payment_type', 'REFUND')->count()
        );
        $this->assertEquals(5000.0, (float) $booking->fresh()->advance_paid);
    }

    public function test_service_submission_reference_conflict_is_rejected(): void
    {
        $booking = $this->serviceBooking(1000);

        $this->actingAs($this->customer())
            ->postJson("/api/v1/service-bookings/{$booking->id}/payments", [
                'amount' => 500,
                'method' => 'CASH',
                'kind' => 'INSTALLMENT',
                'reference' => 'CASH-SERVICE-CONFLICT',
            ])->assertCreated();

        $this->actingAs($this->customer())
            ->postJson("/api/v1/service-bookings/{$booking->id}/payments", [
                'amount' => 600,
                'method' => 'CASH',
                'kind' => 'INSTALLMENT',
                'reference' => 'CASH-SERVICE-CONFLICT',
            ])->assertStatus(422)
            ->assertJsonPath(
                'message',
                'This payment reference was already used for a different amount or payment kind. Please use a new reference.'
            );

        $this->assertSame(1, ServicePayment::query()->where('service_booking_id', $booking->id)->count());
    }

    public function test_payment_proofs_are_read_from_the_private_disk(): void
    {
        $this->assertSame('local', config('filesystems.proofs_disk'));

        Storage::fake('local');

        $booking = $this->serviceBooking(5000);
        $payment = ServicePayment::create([
            'tenant_id' => $booking->tenant_id,
            'service_booking_id' => $booking->id,
            'customer_id' => $booking->customer_id,
            'amount' => 1000,
            'method' => 'CASH',
            'status' => 'PENDING',
            'kind' => 'ADVANCE',
            'proof_path' => 'service-proofs/private-proof.txt',
        ]);

        Storage::disk('local')->put('service-proofs/private-proof.txt', 'verified cash proof');

        $this->actingAs($this->customer())
            ->get(route('documents.vendor-payment-proof', $payment->id))
            ->assertOk();
    }

    public function test_non_finance_tenant_staff_cannot_download_payment_proofs(): void
    {
        Storage::fake('local');

        $booking = $this->serviceBooking(5000);
        $payment = ServicePayment::create([
            'tenant_id' => $booking->tenant_id,
            'service_booking_id' => $booking->id,
            'customer_id' => $booking->customer_id,
            'amount' => 1000,
            'method' => 'CASH',
            'status' => 'PENDING',
            'kind' => 'ADVANCE',
            'proof_path' => 'service-proofs/floor-staff-proof.txt',
        ]);

        Storage::disk('local')->put('service-proofs/floor-staff-proof.txt', 'verified cash proof');

        $floorStaff = User::factory()->create([
            'tenant_id' => $booking->tenant_id,
            'role' => 'ROLE_FLOOR_STAFF',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($floorStaff)
            ->get(route('documents.vendor-payment-proof', $payment->id))
            ->assertStatus(403);
    }
}
