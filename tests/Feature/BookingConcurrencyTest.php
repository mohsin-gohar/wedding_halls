<?php

namespace Tests\Feature;

use App\Exceptions\SlotAlreadyBookedException;
use App\Models\Booking;
use App\Models\GuestRsvp;
use App\Models\Hall;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookingConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_booking_service_creates_atomic_reservation_and_kot()
    {
        $hall = Hall::first();
        $bookingService = app(BookingService::class);

        // Use a date far from the seeder's dynamic "today"/"tomorrow" bookings;
        // otherwise the seeded EVENING reservation makes this test flaky.
        $testDate = now()->addDays(180)->toDateString();
        $testSlot = 'EVENING';

        $data = [
            'hall_id' => $hall->id,
            'event_date' => $testDate,
            'slot_type' => $testSlot,
            'event_type' => 'Barat',
            'customer_name' => 'Mirza Asad Beg',
            'customer_phone' => '+92 300 9988771',
            'customer_cnic' => '35201-9988771-1',
            'guest_count' => 300,
            'per_head_rate' => 2000.00,
            'hall_rent' => 100000.00,
            'addons_total' => 20000.00,
            'discount_amount' => 10000.00,
            'tax_amount' => 35500.00,
            'advance_paid' => 200000.00,
            'payment_method' => 'CASH',
        ];

        $booking = $bookingService->createReservation($data);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals($testDate, $booking->event_date->format('Y-m-d'));
        $this->assertEquals($testSlot, $booking->slot_type);
        // Financial check: (2000 * 300) + 100000 + 20000 - 10000 + 35500 = 745500
        $this->assertEquals(745500.00, (float) $booking->grand_total);
        $this->assertEquals(545500.00, (float) $booking->balance_due);

        // Advance payment verified
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'amount' => 200000.00,
            'payment_type' => 'TOKEN_ADVANCE',
        ]);

        // Kitchen KOT verified with raw meat & rice
        $this->assertDatabaseHas('kitchen_kots', [
            'booking_id' => $booking->id,
            'guest_count' => 300,
            'raw_meat_kg' => 66.00, // 300 * 0.22
            'raw_rice_kg' => 48.00, // 300 * 0.16
            'raw_oil_liters' => 15.00, // 300 * 0.05
        ]);
    }

    public function test_booking_service_prevents_double_booking_and_throws_exception()
    {
        $this->expectException(SlotAlreadyBookedException::class);

        $hall = Hall::first();
        $bookingService = app(BookingService::class);

        $testDate = '2026-11-25';
        $testSlot = 'EVENING';

        $data = [
            'hall_id' => $hall->id,
            'event_date' => $testDate,
            'slot_type' => $testSlot,
            'customer_name' => 'Client A',
            'customer_phone' => '+92 300 1111111',
            'guest_count' => 200,
            'per_head_rate' => 1500,
            'advance_paid' => 50000,
        ];

        // First booking succeeds
        $bookingService->createReservation($data);

        // Attempting to double-book the same slot must throw SlotAlreadyBookedException
        $duplicateData = [
            'hall_id' => $hall->id,
            'event_date' => $testDate,
            'slot_type' => $testSlot,
            'customer_name' => 'Client B (Conflict)',
            'customer_phone' => '+92 300 2222222',
            'guest_count' => 250,
            'per_head_rate' => 1500,
            'advance_paid' => 50000,
        ];

        $bookingService->createReservation($duplicateData);
    }

    public function test_full_day_and_individual_shift_reservations_cannot_overlap(): void
    {
        $hall = Hall::first();
        $bookingService = app(BookingService::class);
        $testDate = '2026-11-26';

        $fullDayReservation = [
            'hall_id' => $hall->id,
            'event_date' => $testDate,
            'slot_type' => 'FULL_DAY',
            'customer_name' => 'Full Day Client',
            'customer_phone' => '+92 300 3333333',
            'guest_count' => 300,
            'per_head_rate' => 1800,
        ];

        $bookingService->createReservation($fullDayReservation);

        $this->assertFalse($bookingService->checkSlotAvailability($hall->id, $testDate, 'EVENING'));

        try {
            $bookingService->createReservation([
                ...$fullDayReservation,
                'slot_type' => 'EVENING',
                'customer_name' => 'Conflicting Evening Client',
                'customer_phone' => '+92 300 4444444',
            ]);
            $this->fail('An individual shift must not overlap an active full-day reservation.');
        } catch (SlotAlreadyBookedException) {
            $this->assertTrue(true);
        }

        $individualShiftDate = '2026-11-27';
        $bookingService->createReservation([
            ...$fullDayReservation,
            'event_date' => $individualShiftDate,
            'slot_type' => 'AFTERNOON',
            'customer_name' => 'Afternoon Client',
            'customer_phone' => '+92 300 5555555',
        ]);

        $this->assertFalse($bookingService->checkSlotAvailability($hall->id, $individualShiftDate, 'FULL_DAY'));

        $this->expectException(SlotAlreadyBookedException::class);
        $bookingService->createReservation([
            ...$fullDayReservation,
            'event_date' => $individualShiftDate,
            'customer_name' => 'Conflicting Full Day Client',
            'customer_phone' => '+92 300 6666666',
        ]);
    }

    public function test_document_routes_return_ok()
    {
        $booking = Booking::with('payments', 'kitchenKot')->first();

        // 1. Guests are sent to login before confidential contracts are served
        $this->get(route('documents.agreement', $booking->id))->assertRedirect(route('login'));

        // 2. Authenticated tenant owner can download legal agreement
        $owner = User::where('tenant_id', $booking->tenant_id)->first();
        $res = $this->actingAs($owner)->get(route('documents.agreement', $booking->id));
        $res->assertStatus(200);

        // Payment Receipt
        if ($booking->payments->isNotEmpty()) {
            $receiptRes = $this->actingAs($owner)->get(route('documents.receipt', $booking->payments->first()->id));
            $receiptRes->assertStatus(200);
        }

        // Kitchen KOT
        if ($booking->kitchenKot) {
            $kotRes = $this->actingAs($owner)->get(route('documents.kot', $booking->kitchenKot->id));
            $kotRes->assertStatus(200);
        }
    }

    public function test_whatsapp_webhook_verification_and_rsvp_processing()
    {
        // 1. Verification handshake
        Http::fake();
        config(['services.whatsapp.verify_token' => 'test-verify-token']);
        config(['services.whatsapp.app_secret' => 'test-app-secret']);
        config(['services.whatsapp.access_token' => 'test-access-token']);
        config(['services.whatsapp.phone_number_id' => 'test-phone-id']);
        config(['services.whatsapp.api_version' => 'v21.0']);

        $verifyRes = $this->get('/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=test-verify-token&hub_challenge=CHALLENGE_CODE_99');
        $verifyRes->assertStatus(200);
        $this->assertEquals('CHALLENGE_CODE_99', $verifyRes->getContent());

        // 2. Incoming message parsing for guest RSVP
        $guestRsvp = GuestRsvp::first();
        $this->assertNotNull($guestRsvp);

        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'from' => $guestRsvp->phone,
                                        'text' => [
                                            'body' => 'Attending: 5 guests with us',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'test-app-secret');
        $postRes = $this->call('POST', '/webhooks/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $body);
        $postRes->assertStatus(200);
        Http::assertSent(
            fn (Request $request) => $request->url() === 'https://graph.facebook.com/v21.0/test-phone-id/messages'
        );

        $guestRsvp->refresh();
        $this->assertEquals('ATTENDING', $guestRsvp->rsvp_status);
        $this->assertEquals(5, $guestRsvp->attending_count);

        $forgedPayload = $payload;
        $forgedPayload['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'] = 'Decline';
        $forgedBody = json_encode($forgedPayload, JSON_THROW_ON_ERROR);
        $this->call('POST', '/webhooks/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid',
        ], $forgedBody)->assertForbidden();

        $this->assertSame(5, $guestRsvp->fresh()->attending_count);
    }
}
