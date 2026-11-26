<?php

namespace Tests\Feature;

use App\Livewire\WhatsAppRsvpManager;
use App\Models\ApiToken;
use App\Models\Booking;
use App\Models\GuestRsvp;
use App\Models\Hall;
use App\Models\RFQBid;
use App\Models\User;
use App\Policies\RFQBidPolicy;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityHardeningRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        config([
            'services.whatsapp.app_secret' => 'test-app-secret',
            'services.whatsapp.access_token' => 'test-access-token',
            'services.whatsapp.phone_number_id' => 'test-phone-id',
            'services.whatsapp.api_version' => 'v21.0',
        ]);

        Http::fake();
    }

    public function test_api_booking_update_cannot_write_advance_paid(): void
    {
        $booking = $this->createBooking(Hall::query()->firstOrFail(), now()->addDays(200)->toDateString());
        $owner = $this->ownerFor($booking);

        $this->actingAs($owner->fresh())
            ->patchJson('/api/v1/bookings/'.$booking->id, [
                'advance_paid' => 999999,
                'notes' => 'attempted cash bypass',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('advance_paid');

        $this->assertSame(0.0, (float) $booking->fresh()->advance_paid);
        $this->assertSame(0.0, (float) $booking->fresh()->balance_due);
        $this->assertNull($booking->fresh()->notes);
    }

    public function test_scoped_api_token_cannot_call_write_endpoints(): void
    {
        $booking = $this->createBooking(Hall::query()->firstOrFail(), now()->addDays(201)->toDateString());
        $owner = $this->ownerFor($booking);

        [$token, $plain] = ApiToken::issue($owner, 'Read-only partner', ['bookings.read']);
        $this->assertSame(['bookings.read'], $token->abilities);

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->getJson('/api/v1/bookings')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->getJson('/api/v1/bookings/'.$booking->id)
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->patchJson('/api/v1/bookings/'.$booking->id, ['notes' => 'scoped token write'])
            ->assertStatus(403)
            ->assertJsonPath('message', 'This API token is missing the required ability: bookings.update');

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->postJson('/api/v1/payments/record', [
                'booking_id' => $booking->id,
                'amount' => 1000,
            ])
            ->assertStatus(403)
            ->assertJsonPath('message', 'This API token is missing the required ability: payments.record');

        $this->assertNull($booking->fresh()->notes);
    }

    public function test_legacy_unrestricted_token_still_works(): void
    {
        $booking = $this->createBooking(Hall::query()->firstOrFail(), now()->addDays(202)->toDateString());
        $owner = $this->ownerFor($booking);

        [, $plain] = ApiToken::issue($owner, 'First-party mobile app');

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->patchJson('/api/v1/bookings/'.$booking->id, ['notes' => 'legacy token update'])
            ->assertOk();

        $this->assertSame('legacy token update', $booking->fresh()->notes);
    }

    public function test_inactive_web_account_is_logged_out(): void
    {
        $owner = User::query()
            ->whereNotNull('tenant_id')
            ->where('role', 'ROLE_TENANT_OWNER')
            ->firstOrFail();

        $owner->forceFill(['status' => 'INACTIVE'])->saveQuietly();

        $this->actingAs($owner->fresh())
            ->get('/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_rfq_bid_policy_is_psr4_autoloadable(): void
    {
        $this->assertInstanceOf(RFQBidPolicy::class, Gate::getPolicyFor(RFQBid::class));
    }

    public function test_whatsapp_webhook_rejects_ambiguous_phone_and_honours_event_scope_and_replay(): void
    {
        $phone = '+92 300 7778899';
        $firstHall = Hall::query()->firstOrFail();
        $secondHall = Hall::query()->where('id', '!=', $firstHall->id)->firstOrFail();

        $firstBooking = $this->createBooking($firstHall, now()->addDays(210)->toDateString(), $phone);
        $secondBooking = $this->createBooking($secondHall, now()->addDays(211)->toDateString(), $phone);

        $firstRsvp = GuestRsvp::create([
            'tenant_id' => $firstBooking->tenant_id,
            'booking_id' => $firstBooking->id,
            'guest_name' => 'First Event Guest',
            'phone' => $phone,
            'attending_count' => 1,
            'rsvp_status' => 'TENTATIVE',
        ]);
        $secondRsvp = GuestRsvp::create([
            'tenant_id' => $secondBooking->tenant_id,
            'booking_id' => $secondBooking->id,
            'guest_name' => 'Second Event Guest',
            'phone' => '03007778899',
            'attending_count' => 1,
            'rsvp_status' => 'TENTATIVE',
        ]);

        // Same full number across two events => no mutation without explicit scope.
        $this->postWebhook($this->webhookPayload($phone, 'Attending: 7', 'msg-ambiguous'));
        $this->assertSame('TENTATIVE', $firstRsvp->fresh()->rsvp_status);
        $this->assertSame('TENTATIVE', $secondRsvp->fresh()->rsvp_status);

        // Explicit booking scope updates only that invitation.
        $this->postWebhook(
            $this->webhookPayload($phone, 'Attending: 4', 'msg-scoped', $firstBooking->id),
        )->assertOk();

        $this->assertSame('ATTENDING', $firstRsvp->fresh()->rsvp_status);
        $this->assertSame(4, $firstRsvp->fresh()->attending_count);
        $this->assertSame('TENTATIVE', $secondRsvp->fresh()->rsvp_status);

        // Replaying the same provider message cannot flip the headcount.
        $this->postWebhook(
            $this->webhookPayload($phone, 'Decline', 'msg-scoped', $firstBooking->id),
        )->assertOk();

        $this->assertSame('ATTENDING', $firstRsvp->fresh()->rsvp_status);
        $this->assertSame(4, $firstRsvp->fresh()->attending_count);
        $this->assertDatabaseCount('processed_webhook_messages', 2);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'WHATSAPP_RSVP_UPDATED',
            'entity_type' => 'GuestRsvp',
            'entity_id' => $firstRsvp->id,
        ]);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/messages'));
    }

    private function postWebhook(array $payload)
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        return $this->call('POST', '/webhooks/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, 'test-app-secret'),
        ], $body);
    }

    /**
     * @return array<string, mixed>
     */
    private function webhookPayload(string $from, string $body, string $messageId, ?string $bookingId = null): array
    {
        $value = [
            'messages' => [[
                'id' => $messageId,
                'from' => $from,
                'text' => ['body' => $body],
            ]],
        ];

        if ($bookingId) {
            $value['booking_id'] = $bookingId;
        }

        return [
            'entry' => [[
                'changes' => [[
                    'value' => $value,
                ]],
            ]],
        ];
    }

    private function createBooking(Hall $hall, string $date, string $phone = '+92 300 6665544'): Booking
    {
        $owner = $this->ownerForHall($hall);

        return app(BookingService::class)->createReservation([
            'hall_id' => $hall->id,
            'created_by_user_id' => $owner->id,
            'event_date' => $date,
            'slot_type' => 'EVENING',
            'guest_count' => 200,
            'customer_name' => 'Security Regression Client',
            'customer_phone' => $phone,
            'advance_paid' => 0,
        ]);
    }

    private function ownerForHall(Hall $hall): User
    {
        return User::query()
            ->where('tenant_id', $hall->tenant_id)
            ->where('role', 'ROLE_TENANT_OWNER')
            ->firstOrFail();
    }

    private function ownerFor(Booking $booking): User
    {
        return $this->ownerForHall(Hall::query()->findOrFail($booking->hall_id));
    }

    public function test_unrecognized_whatsapp_message_does_not_confirm_rsvp(): void
    {
        $phone = '+92 300 8887766';
        $hall = Hall::query()->firstOrFail();
        $booking = $this->createBooking($hall, now()->addDays(220)->toDateString(), $phone);
        $rsvp = GuestRsvp::create([
            'tenant_id' => $booking->tenant_id,
            'booking_id' => $booking->id,
            'guest_name' => 'Unclear Reply Guest',
            'phone' => $phone,
            'attending_count' => 2,
            'rsvp_status' => 'TENTATIVE',
        ]);

        $this->postWebhook($this->webhookPayload($phone, 'Hello, what time is the event?', 'msg-unclear'))
            ->assertOk()
            ->assertJsonPath('status', 'needs_clarification');

        $this->assertSame('TENTATIVE', $rsvp->fresh()->rsvp_status);
        $this->assertSame(2, $rsvp->fresh()->attending_count);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'WHATSAPP_RSVP_UPDATED',
            'entity_id' => $rsvp->id,
        ]);
    }

    public function test_whatsapp_rsvp_simulator_does_not_mutate_on_unrecognized_message(): void
    {
        $phone = '+92 300 8887767';
        $hall = Hall::query()->firstOrFail();
        $booking = $this->createBooking($hall, now()->addDays(221)->toDateString(), $phone);
        $rsvp = GuestRsvp::create([
            'tenant_id' => $booking->tenant_id,
            'booking_id' => $booking->id,
            'guest_name' => 'Simulator Unclear Guest',
            'phone' => $phone,
            'attending_count' => 3,
            'rsvp_status' => 'TENTATIVE',
        ]);

        Livewire::actingAs($this->ownerFor($booking))
            ->test(WhatsAppRsvpManager::class)
            ->set('selectedBookingId', $booking->id)
            ->set('simPhone', $phone)
            ->set('simMessage', 'Can I bring children?')
            ->call('simulateWhatsAppReply')
            ->assertSee('RSVP unchanged');

        $this->assertSame('TENTATIVE', $rsvp->fresh()->rsvp_status);
        $this->assertSame(3, $rsvp->fresh()->attending_count);
    }
}
