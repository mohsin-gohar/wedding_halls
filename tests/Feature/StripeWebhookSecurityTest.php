<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StripeWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);
    }

    public function test_signed_stripe_webhook_credits_the_booking(): void
    {
        [$booking, $tenant] = $this->makeBooking('RG-STRIPE-VALID');
        $body = $this->eventBody($booking->booking_reference, 'pi_valid_001', 5000000);
        $signature = $this->signature($body);

        $this->postRaw($body, $signature)
            ->assertOk()->assertJson(['status' => 'processed']);

        $this->assertSame(50000.0, (float) $booking->fresh()->advance_paid);
        $this->assertSame(700000.0, (float) $booking->fresh()->balance_due);
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'tenant_id' => $tenant->id,
            'payment_method' => 'STRIPE',
            'transaction_reference' => 'pi_valid_001',
            'amount' => 50000,
        ]);
    }

    public function test_tampered_stripe_payload_is_rejected(): void
    {
        [$booking] = $this->makeBooking('RG-STRIPE-TAMPER');
        $body = $this->eventBody($booking->booking_reference, 'pi_tampered', 5000000);
        $signature = $this->signature($body);

        // Keep the signature but change the signed bytes.
        $tampered = str_replace('5000000', '9000000', $body);

        $this->postRaw($tampered, $signature)->assertStatus(401);

        $this->assertSame(0.0, (float) $booking->fresh()->advance_paid);
        $this->assertSame(0, Payment::query()->where('booking_id', $booking->id)->count());
    }

    public function test_unsigned_stripe_payload_is_rejected(): void
    {
        [$booking] = $this->makeBooking('RG-STRIPE-UNSIGNED');
        $body = $this->eventBody($booking->booking_reference, 'pi_unsigned', 5000000);

        $this->postRaw($body, '')->assertStatus(401);

        $this->assertSame(0.0, (float) $booking->fresh()->advance_paid);
    }

    public function test_replayed_stripe_webhook_is_idempotent(): void
    {
        [$booking] = $this->makeBooking('RG-STRIPE-REPLAY');
        $body = $this->eventBody($booking->booking_reference, 'pi_replay_001', 5000000);
        $signature = $this->signature($body);

        $this->postRaw($body, $signature)
            ->assertOk()->assertJson(['status' => 'processed']);
        $this->postRaw($body, $signature)
            ->assertOk()->assertJson(['status' => 'already_processed']);

        $this->assertSame(50000.0, (float) $booking->fresh()->advance_paid);
        $this->assertSame(1, Payment::query()->where('booking_id', $booking->id)->count());
    }

    private function postRaw(string $body, string $signature)
    {
        return $this->call(
            'POST',
            '/api/v1/webhooks/stripe',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $body
        );
    }

    private function eventBody(string $reference, string $paymentIntentId, int $amount): string
    {
        return json_encode([
            'id' => 'evt_'.uniqid(),
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => $paymentIntentId,
                    'amount_received' => $amount,
                    'metadata' => ['booking_reference' => $reference],
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function signature(string $body): string
    {
        $timestamp = time();

        return 't='.$timestamp.',v1='.hash_hmac(
            'sha256',
            $timestamp.'.'.$body,
            (string) config('services.stripe.webhook_secret')
        );
    }

    /**
     * @return array{0: Booking, 1: Tenant}
     */
    private function makeBooking(string $reference): array
    {
        $hall = Hall::query()->firstOrFail();
        $tenant = Tenant::query()->findOrFail($hall->tenant_id);
        $customer = User::query()->where('role', 'ROLE_CUSTOMER')->first()
            ?? User::create([
                'name' => 'Stripe Webhook Customer',
                'email' => 'stripe.customer@example.test',
                'phone' => '+92 300 6667779',
                'role' => 'ROLE_CUSTOMER',
                'password' => Hash::make('secret123'),
            ]);

        $booking = Booking::create([
            'tenant_id' => $tenant->id,
            'hall_id' => $hall->id,
            'customer_id' => $customer->id,
            'booking_reference' => $reference,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'event_type' => 'Walima Reception',
            'event_date' => now()->addDays(45)->format('Y-m-d'),
            'slot_type' => 'EVENING',
            'guest_count' => 300,
            'per_head_rate' => 2000,
            'hall_rent' => 150000,
            'grand_total' => 750000,
            'advance_paid' => 0,
            'balance_due' => 750000,
            'status' => 'CONFIRMED',
            'pipeline_stage' => 'CONFIRMED',
        ]);

        return [$booking, $tenant];
    }
}
