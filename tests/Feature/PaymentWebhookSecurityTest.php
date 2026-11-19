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

/**
 * Phase 1/11 — gateway webhook hardening (fail-closed).
 *
 * Before hardening, JazzCash/Easypaisa webhooks were accepted on the
 * payload's success field alone (pp_ResponseCode=000 / resultCode=SUCCESS)
 * with no cryptographic verification — anyone able to reach the endpoint
 * could forge a "successful" payment and credit a booking.
 *
 * Now a webhook is only processed when the shared secret is configured and
 * the canonical sorted-string HMAC (sha256 for JazzCash, sha512 for
 * Easypaisa) reproduces the supplied hash, plus the gateway success code.
 */
class PaymentWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function venueAndHall(): array
    {
        $hall = Hall::query()->firstOrFail();
        $tenant = Tenant::query()->findOrFail($hall->tenant_id);

        return [$hall, $tenant];
    }

    private function makeBooking(Tenant $tenant, Hall $hall, string $reference): Booking
    {
        $customer = User::query()->where('role', 'ROLE_CUSTOMER')->first()
            ?? User::create([
                'name' => 'Webhook Customer',
                'email' => 'webhook.customer@example.test',
                'phone' => '+92 300 6667778',
                'role' => 'ROLE_CUSTOMER',
                'password' => Hash::make('secret123'),
            ]);

        return Booking::create([
            'tenant_id' => $tenant->id,
            'hall_id' => $hall->id,
            'customer_id' => $customer->id,
            'booking_reference' => $reference,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'event_type' => 'Walima Reception',
            'event_date' => now()->addDays(30)->format('Y-m-d'),
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
    }

    /**
     * Canonical gateway hash: every field except the hash field itself,
     * sorted by key, joined as `key=value&key=value`.
     */
    private function gatewayHash(array $payload, string $hashField, string $algo, string $secret): string
    {
        unset($payload[$hashField]);
        ksort($payload);

        $pairs = [];
        foreach ($payload as $key => $value) {
            $pairs[] = $key.'='.$value;
        }

        return hash_hmac($algo, implode('&', $pairs), $secret);
    }

    private function jazzcashPayload(string $bookingReference, array $overrides = []): array
    {
        return array_merge([
            'pp_TxnRef' => $bookingReference,
            'pp_Amount' => 5000000, // PKR 50,000.00 in paisa
            'pp_ResponseCode' => '000',
            'pp_ResponseMessage' => 'SUCCESS',
            'pp_SecureHash' => '',
        ], $overrides);
    }

    private function easypaisaPayload(string $bookingReference, array $overrides = []): array
    {
        return array_merge([
            'txnRef' => $bookingReference,
            'amount' => 5000000,
            'resultCode' => 'SUCCESS',
            'resultMessage' => 'Payment successful',
            'secureHash' => '',
        ], $overrides);
    }

    // ─────────────────────────────────────────────────────────────
    // Forged / unsigned / tampered — must never credit a booking
    // ─────────────────────────────────────────────────────────────

    public function test_unsigned_forged_jazzcash_webhook_is_rejected_and_credits_nothing(): void
    {
        [$hall, $tenant] = $this->venueAndHall();
        $booking = $this->makeBooking($tenant, $hall, 'BK-WH-FORGED-001');

        // Attacker sets the success code but supplies no hash at all.
        $response = $this->postJson('/api/v1/webhooks/jazzcash', $this->jazzcashPayload($booking->booking_reference));

        $response->assertStatus(401);

        $booking->refresh();
        $this->assertSame(0.0, (float) $booking->advance_paid, 'Forged webhook must not credit the booking.');
        $this->assertSame(750000.0, (float) $booking->balance_due);
        $this->assertDatabaseMissing('payments', ['booking_id' => $booking->id]);
    }

    public function test_jazzcash_webhook_with_wrong_signature_is_rejected(): void
    {
        config(['services.jazzcash.password' => 'real-merchant-secret']);

        [$hall, $tenant] = $this->venueAndHall();
        $booking = $this->makeBooking($tenant, $hall, 'BK-WH-BADSIG-001');

        $payload = $this->jazzcashPayload($booking->booking_reference, [
            'pp_SecureHash' => str_repeat('ab', 32), // valid format, wrong key
        ]);

        $this->postJson('/api/v1/webhooks/jazzcash', $payload)->assertStatus(401);

        $this->assertDatabaseMissing('payments', ['booking_id' => $booking->id]);
    }

    public function test_tampered_amount_after_signing_is_rejected(): void
    {
        $secret = 'real-merchant-secret';
        config(['services.jazzcash.password' => $secret]);

        [$hall, $tenant] = $this->venueAndHall();
        $booking = $this->makeBooking($tenant, $hall, 'BK-WH-TAMPER-001');

        // Signed for PKR 10,000 …
        $signed = $this->jazzcashPayload($booking->booking_reference, ['pp_Amount' => 1000000]);
        $signed['pp_SecureHash'] = $this->gatewayHash($signed, 'pp_SecureHash', 'sha256', $secret);

        // … then the amount is inflated to PKR 50,000 in transit.
        $signed['pp_Amount'] = 5000000;

        $this->postJson('/api/v1/webhooks/jazzcash', $signed)->assertStatus(401);

        $booking->refresh();
        $this->assertSame(0.0, (float) $booking->advance_paid);
        $this->assertDatabaseMissing('payments', ['booking_id' => $booking->id]);
    }

    public function test_jazzcash_webhook_fails_closed_when_secret_is_not_configured(): void
    {
        config(['services.jazzcash.password' => null]);

        [$hall, $tenant] = $this->venueAndHall();
        $booking = $this->makeBooking($tenant, $hall, 'BK-WH-NOSECRET-001');

        // Even a hash computed against an empty/absent secret must not pass.
        $payload = $this->jazzcashPayload($booking->booking_reference);
        $payload['pp_SecureHash'] = hash_hmac('sha256', 'pp_TxnRef='.$booking->booking_reference.'&pp_Amount=5000000&pp_ResponseCode=000&pp_ResponseMessage=SUCCESS', '');

        $this->postJson('/api/v1/webhooks/jazzcash', $payload)->assertStatus(401);

        $this->assertDatabaseMissing('payments', ['booking_id' => $booking->id]);
    }

    public function test_easypaisa_forged_success_webhook_is_rejected(): void
    {
        config(['services.easypaisa.secret_key' => 'real-easypaisa-secret']);

        [$hall, $tenant] = $this->venueAndHall();
        $booking = $this->makeBooking($tenant, $hall, 'BK-WH-EPFORGED-001');

        // Success fields present, but no valid HMAC-SHA512.
        $payload = $this->easypaisaPayload($booking->booking_reference, ['secureHash' => str_repeat('cd', 64)]);

        $this->postJson('/api/v1/webhooks/easypaisa', $payload)->assertStatus(401);

        $booking->refresh();
        $this->assertSame(0.0, (float) $booking->advance_paid);
        $this->assertDatabaseMissing('payments', ['booking_id' => $booking->id]);
    }

    public function test_easypaisa_webhook_fails_closed_when_secret_is_not_configured(): void
    {
        config(['services.easypaisa.secret_key' => null]);

        [$hall, $tenant] = $this->venueAndHall();
        $booking = $this->makeBooking($tenant, $hall, 'BK-WH-EPNOSEC-001');

        $this->postJson('/api/v1/webhooks/easypaisa', $this->easypaisaPayload($booking->booking_reference))
            ->assertStatus(401);

        $this->assertDatabaseMissing('payments', ['booking_id' => $booking->id]);
    }

    // ─────────────────────────────────────────────────────────────
    // Legitimately signed webhooks — must still be processed
    // ─────────────────────────────────────────────────────────────

    public function test_validly_signed_jazzcash_webhook_credits_the_booking_once(): void
    {
        $secret = 'real-merchant-secret';
        config(['services.jazzcash.password' => $secret]);

        [$hall, $tenant] = $this->venueAndHall();
        $booking = $this->makeBooking($tenant, $hall, 'BK-WH-VALID-001');

        $payload = $this->jazzcashPayload($booking->booking_reference);
        $payload['pp_SecureHash'] = $this->gatewayHash($payload, 'pp_SecureHash', 'sha256', $secret);

        $this->postJson('/api/v1/webhooks/jazzcash', $payload)
            ->assertOk()
            ->assertJson(['status' => 'processed']);

        $booking->refresh();
        $this->assertSame(50000.0, (float) $booking->advance_paid);
        $this->assertSame(700000.0, (float) $booking->balance_due);

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'amount' => 50000,
            'payment_method' => 'JAZZCASH',
        ]);

        // Gateway retry with the same transaction reference must be idempotent.
        $this->postJson('/api/v1/webhooks/jazzcash', $payload)
            ->assertOk()
            ->assertJson(['status' => 'already_processed']);

        $this->assertSame(1, Payment::query()->where('booking_id', $booking->id)->count(), 'Replay must not double-credit.');
    }

    public function test_validly_signed_easypaisa_webhook_credits_the_booking(): void
    {
        $secret = 'real-easypaisa-secret';
        config(['services.easypaisa.secret_key' => $secret]);

        [$hall, $tenant] = $this->venueAndHall();
        $booking = $this->makeBooking($tenant, $hall, 'BK-WH-EPVALID-001');

        $payload = $this->easypaisaPayload($booking->booking_reference);
        $payload['secureHash'] = $this->gatewayHash($payload, 'secureHash', 'sha512', $secret);

        $this->postJson('/api/v1/webhooks/easypaisa', $payload)
            ->assertOk()
            ->assertJson(['status' => 'processed']);

        $booking->refresh();
        $this->assertSame(50000.0, (float) $booking->advance_paid);
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'payment_method' => 'EASYPAISA',
        ]);
    }

    public function test_valid_signature_with_declined_response_code_is_rejected(): void
    {
        $secret = 'real-merchant-secret';
        config(['services.jazzcash.password' => $secret]);

        [$hall, $tenant] = $this->venueAndHall();
        $booking = $this->makeBooking($tenant, $hall, 'BK-WH-DECLINE-001');

        // Perfectly signed, but the gateway declined the transaction.
        $payload = $this->jazzcashPayload($booking->booking_reference, ['pp_ResponseCode' => '1001']);
        $payload['pp_SecureHash'] = $this->gatewayHash($payload, 'pp_SecureHash', 'sha256', $secret);

        $this->postJson('/api/v1/webhooks/jazzcash', $payload)->assertStatus(401);

        $booking->refresh();
        $this->assertSame(0.0, (float) $booking->advance_paid);
        $this->assertDatabaseMissing('payments', ['booking_id' => $booking->id]);
    }

    public function test_unknown_gateway_stays_rejected(): void
    {
        // Route whitelist restricts {gateway}; an out-of-list value 404s.
        $this->postJson('/api/v1/webhooks/evilpay', ['status' => 'SUCCESS'])->assertStatus(404);
    }
}
