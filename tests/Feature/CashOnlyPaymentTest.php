<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\Payment;
use App\Models\ServiceBooking;
use App\Models\ServicePayment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CashOnlyPaymentTest extends TestCase
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
        $customer = User::query()->where('role', 'ROLE_CUSTOMER')->firstOrFail();

        return $customer->fresh();
    }

    private function booking(float $total = 20000): Booking
    {
        $hall = $this->hall();

        return Booking::create([
            'tenant_id' => $hall->tenant_id,
            'hall_id' => $hall->id,
            'booking_reference' => 'BK-'.now()->format('Ym').'-'.strtoupper(Str::random(5)),
            'customer_id' => $this->customer()->id,
            'customer_name' => 'Cash Only Test Customer',
            'customer_phone' => '+92 300 1112233',
            'event_type' => 'Barat',
            'event_date' => now()->addDays(150)->toDateString(),
            'slot_type' => 'EVENING',
            'guest_count' => 200,
            'grand_total' => $total,
            'advance_paid' => 0,
            'balance_due' => $total,
            'status' => 'CONFIRMED',
            'pipeline_stage' => 'CONFIRMED',
        ]);
    }

    public function test_all_online_gateway_endpoints_are_gone(): void
    {
        $payload = [
            'booking_id' => (string) Str::uuid(),
            'amount' => 1000,
            'payment_method' => 'STRIPE',
        ];

        $this->postJson('/api/v1/payments/initiate', $payload)->assertNotFound();
        $this->postJson('/api/v1/webhooks/stripe', $payload)->assertNotFound();
        $this->postJson('/api/v1/webhooks/jazzcash', $payload)->assertNotFound();
        $this->postJson('/api/v1/webhooks/easypaisa', $payload)->assertNotFound();
        $this->postJson('/api/v1/payments/webhook/stripe', $payload)->assertNotFound();
    }

    public function test_customer_cannot_record_or_self_credit_a_cash_payment(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->customer())->postJson('/api/v1/payments/record', [
            'booking_id' => $booking->id,
            'amount' => 5000,
            'payment_type' => 'TOKEN_ADVANCE',
            'transaction_reference' => 'SELF-CREDIT',
        ])->assertForbidden();

        $this->assertSame(0, Payment::query()->where('booking_id', $booking->id)->count());
        $this->assertEquals(0.0, (float) $booking->fresh()->advance_paid);
        $this->assertEquals(20000.0, (float) $booking->fresh()->balance_due);
    }

    public function test_authorized_staff_records_cash_and_updates_the_ledger(): void
    {
        $booking = $this->booking();
        $owner = $this->owner();

        $response = $this->actingAs($owner)->postJson('/api/v1/payments/record', [
            'booking_id' => $booking->id,
            'amount' => 7500.50,
            'payment_type' => 'TOKEN_ADVANCE',
            'transaction_reference' => 'CASH-RCPT-001',
            'notes' => 'Counted at reception.',
        ]);

        $response->assertCreated()->assertJsonPath('success', true);

        $payment = Payment::query()->where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame('CASH', $payment->payment_method);
        $this->assertSame('TOKEN_ADVANCE', $payment->payment_type);
        $this->assertEquals(7500.50, (float) $payment->amount);
        $this->assertSame((string) $owner->id, (string) $payment->received_by_user_id);

        $fresh = $booking->fresh();
        $this->assertEquals(7500.50, (float) $fresh->advance_paid);
        $this->assertEquals(12499.50, (float) $fresh->balance_due);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'Payment',
            'entity_id' => $payment->id,
            'user_id' => $owner->id,
            'action' => 'payments.record',
        ]);
        $this->assertNotNull($payment->receipt_no);
    }

    public function test_duplicate_cash_reference_is_idempotent(): void
    {
        $booking = $this->booking();
        $owner = $this->owner();
        $payload = [
            'booking_id' => $booking->id,
            'amount' => 5000,
            'payment_type' => 'SECOND_INSTALLMENT',
            'transaction_reference' => 'CASH-RCPT-DUP',
        ];

        $this->actingAs($owner)->postJson('/api/v1/payments/record', $payload)->assertCreated();
        $this->actingAs($owner)->postJson('/api/v1/payments/record', $payload)->assertCreated();

        $this->assertSame(1, Payment::query()->where('booking_id', $booking->id)->count());
        $this->assertEquals(5000.0, (float) $booking->fresh()->advance_paid);
    }

    public function test_overpayment_is_rejected(): void
    {
        $booking = $this->booking(1000);

        $this->actingAs($this->owner())->postJson('/api/v1/payments/record', [
            'booking_id' => $booking->id,
            'amount' => 1001,
            'payment_type' => 'TOKEN_ADVANCE',
        ])->assertStatus(422);

        $this->assertSame(0, Payment::query()->where('booking_id', $booking->id)->count());
    }

    public function test_authorized_staff_can_record_a_cash_refund_only(): void
    {
        $booking = $this->booking();
        $owner = $this->owner();

        $this->actingAs($owner)->postJson('/api/v1/payments/record', [
            'booking_id' => $booking->id,
            'amount' => 8000,
            'payment_type' => 'TOKEN_ADVANCE',
        ])->assertCreated();

        $this->actingAs($this->customer())->postJson('/api/v1/payments/refund', [
            'booking_id' => $booking->id,
            'amount' => 1000,
        ])->assertForbidden();

        $this->actingAs($owner)->postJson('/api/v1/payments/refund', [
            'booking_id' => $booking->id,
            'amount' => 3000,
            'transaction_reference' => 'CASH-REFUND-001',
        ])->assertCreated();

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'payment_type' => 'REFUND',
            'payment_method' => 'CASH',
            'transaction_reference' => 'CASH-REFUND-001',
        ]);

        $fresh = $booking->fresh();
        $this->assertEquals(5000.0, (float) $fresh->advance_paid);
        $this->assertEquals(15000.0, (float) $fresh->balance_due);
    }

    public function test_all_non_cash_method_labels_are_rejected_for_manual_service_payments(): void
    {
        $hall = $this->hall();
        $customer = $this->customer();

        $serviceBooking = ServiceBooking::create([
            'tenant_id' => $hall->tenant_id,
            'customer_id' => $customer->id,
            'booking_reference' => 'RG-SB-'.strtoupper(Str::random(6)),
            'event_date' => now()->addDays(90)->toDateString(),
            'quoted_amount' => 15000,
            'status' => 'CONFIRMED',
        ]);

        foreach (['BANK_TRANSFER', 'CHEQUE', 'CREDIT_LEDGER', 'JAZZCASH', 'EASYPAISA', 'CARD', 'STRIPE', 'WALLET'] as $method) {
            $this->actingAs($customer)->postJson(
                "/api/v1/service-bookings/{$serviceBooking->id}/payments",
                ['amount' => 1000, 'method' => $method]
            )->assertStatus(422)->assertJsonValidationErrors('method');
        }

        $this->actingAs($customer)->postJson(
            "/api/v1/service-bookings/{$serviceBooking->id}/payments",
            ['amount' => 1000, 'method' => 'CASH']
        )->assertCreated();

        $payment = ServicePayment::query()->where('service_booking_id', $serviceBooking->id)->firstOrFail();
        $this->assertSame('PENDING', $payment->status);
        $this->assertSame('CASH', $payment->method);
    }

    public function test_payment_service_rejects_non_cash_methods_in_code(): void
    {
        $booking = $this->booking();
        $owner = $this->owner();

        $this->expectException(\InvalidArgumentException::class);

        app(PaymentService::class)->createPayment(
            (string) $booking->tenant_id,
            (string) $booking->id,
            1000,
            'TOKEN_ADVANCE',
            'JAZZCASH',
            null,
            (string) $owner->id
        );
    }
}
