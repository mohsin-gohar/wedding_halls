<?php

namespace Tests\Feature;

use App\Events\BookingCreatedEvent;
use App\Livewire\HallChat;
use App\Livewire\PublicVenues;
use App\Livewire\WalkInBookingModal;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\HallChatMessage;
use App\Models\KitchenKot;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use App\Services\BookingService;
use App\Services\HallAvailabilityService;
use App\Services\KitchenOrderService;
use App\Support\ShiftWindows;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PublicBookingHardeningRegressionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Tenant, 1: Venue, 2: Hall}
     */
    private function approvedVenue(string $suffix = ''): array
    {
        $tenant = Tenant::create([
            'name' => 'Approved Hardening Tenant '.$suffix,
            'slug' => 'approved-hardening-'.$suffix,
            'email' => 'approved-'.$suffix.'@riwajgrand.pk',
            'city' => 'Lahore',
            'verification_status' => Tenant::VERIFICATION_STATUS_APPROVED,
            'is_active' => true,
            'marketplace_visible' => true,
        ]);

        $venue = Venue::create([
            'tenant_id' => $tenant->id,
            'name' => 'Hardening Complex '.$suffix,
            'slug' => 'hardening-complex-'.$suffix,
            'city' => 'Lahore',
            'is_active' => true,
        ]);

        $hall = Hall::create([
            'tenant_id' => $tenant->id,
            'venue_id' => $venue->id,
            'name' => 'Hardening Hall '.$suffix,
            'min_capacity' => 100,
            'max_capacity' => 500,
            'base_rent' => 150000,
            'listing_status' => Hall::LISTING_STATUS_LISTED,
            'is_active' => true,
        ]);

        return [$tenant, $venue, $hall];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function bookingPayload(Hall $hall, string $date, array $overrides = []): array
    {
        return array_merge([
            'tenant_id' => $hall->tenant_id,
            'hall_id' => $hall->id,
            'customer_name' => 'Regression Guest',
            'customer_phone' => '+92 300 5550000',
            'customer_email' => 'guest@example.pk',
            'event_type' => 'Barat',
            'event_date' => $date,
            'slot_type' => 'AFTERNOON',
            'guest_count' => 200,
            'per_head_rate' => 2000,
            'hall_rent' => (float) $hall->base_rent,
            'grand_total' => 550000,
            'advance_paid' => 0,
            'status' => 'CONFIRMED',
        ], $overrides);
    }

    public function test_public_hall_scope_hides_withdrawn_and_unapproved_listings(): void
    {
        [$tenant, $venue, $visible] = $this->approvedVenue('visible');

        $withdrawn = Hall::create([
            'tenant_id' => $tenant->id,
            'venue_id' => $venue->id,
            'name' => 'Withdrawn Hardening Hall',
            'min_capacity' => 100,
            'max_capacity' => 500,
            'base_rent' => 100000,
            'listing_status' => 'WITHDRAWN',
            'is_active' => true,
        ]);

        $pendingTenant = Tenant::create([
            'name' => 'Pending Hardening Tenant',
            'slug' => 'pending-hardening',
            'email' => 'pending@riwajgrand.pk',
            'city' => 'Lahore',
            'verification_status' => 'PENDING',
            'is_active' => true,
            'marketplace_visible' => true,
        ]);
        $pendingVenue = Venue::create([
            'tenant_id' => $pendingTenant->id,
            'name' => 'Pending Hardening Complex',
            'slug' => 'pending-hardening-complex',
            'city' => 'Lahore',
            'is_active' => true,
        ]);
        $pendingHall = Hall::create([
            'tenant_id' => $pendingTenant->id,
            'venue_id' => $pendingVenue->id,
            'name' => 'Pending Tenant Hardening Hall',
            'min_capacity' => 100,
            'max_capacity' => 500,
            'base_rent' => 100000,
            'listing_status' => Hall::LISTING_STATUS_LISTED,
            'is_active' => true,
        ]);

        $this->assertTrue(Hall::publicVisible()->whereKey($visible->id)->exists());
        $this->assertFalse(Hall::publicVisible()->whereKey($withdrawn->id)->exists());
        $this->assertFalse(Hall::publicVisible()->whereKey($pendingHall->id)->exists());
        $this->assertFalse(Tenant::marketplaceVisible()->whereKey($pendingTenant->id)->exists());

        Livewire::test(PublicVenues::class)
            ->assertSee($visible->name)
            ->assertDontSee($withdrawn->name)
            ->assertDontSee($pendingHall->name);
    }

    public function test_availability_display_and_booking_check_share_overlap_rules(): void
    {
        [$tenant, , $hall] = $this->approvedVenue('overlap');
        $tenant->update([
            'shift_windows' => [
                'AFTERNOON' => ['start' => '12:00', 'end' => '16:00'],
                'EVENING' => ['start' => '15:30', 'end' => '22:00'],
                'NIGHT' => ['start' => '22:00', 'end' => '02:00'],
                'FULL_DAY' => ['start' => '12:00', 'end' => '02:00'],
            ],
            'shift_buffer_minutes' => 0,
        ]);
        $date = Carbon::tomorrow()->toDateString();

        Booking::create($this->bookingPayload($hall, $date, [
            'slot_type' => 'AFTERNOON',
            'status' => 'CONFIRMED',
        ]));

        $availability = app(HallAvailabilityService::class);

        $this->assertFalse($availability->isSlotAvailable($hall->id, $date, 'EVENING'));
        $this->assertFalse(app(BookingService::class)->checkSlotAvailability($hall->id, $date, 'EVENING'));
        $this->assertTrue($availability->isSlotAvailable($hall->id, $date, 'NIGHT'));
        $this->assertSame(
            '12:00 PM - 4:00 PM',
            ShiftWindows::labelForSlot($tenant->shift_windows, 'AFTERNOON')
        );
    }

    public function test_public_grid_matches_booking_check_with_nonzero_buffer_and_full_day(): void
    {
        [$tenant, , $hall] = $this->approvedVenue('buffer');
        $tenant->update([
            'shift_windows' => [
                'AFTERNOON' => ['start' => '12:00', 'end' => '16:00'],
                'EVENING' => ['start' => '16:30', 'end' => '22:00'],
                'NIGHT' => ['start' => '22:00', 'end' => '02:00'],
                'FULL_DAY' => ['start' => '12:00', 'end' => '02:00'],
            ],
            'shift_buffer_minutes' => 60,
        ]);

        $bufferDate = Carbon::tomorrow()->toDateString();
        $bufferBooking = Booking::create($this->bookingPayload($hall, $bufferDate, [
            'slot_type' => 'AFTERNOON',
            'status' => 'CONFIRMED',
        ]));

        $this->assertSame(60, (int) $tenant->fresh()->shift_buffer_minutes);
        $this->assertSame('CONFIRMED', Booking::whereKey($bufferBooking->id)->value('status'));
        $freshHall = $hall->fresh()->load('tenant');
        $this->assertTrue(ShiftWindows::conflicts(
            ShiftWindows::normalize($freshHall->tenant->shift_windows),
            'EVENING',
            'AFTERNOON',
            (int) $freshHall->tenant->shift_buffer_minutes
        ));

        $availability = app(HallAvailabilityService::class);
        $bookingService = app(BookingService::class);
        $blocked = $availability->blockedSlotsForHall($hall, $bufferDate);

        $this->assertArrayHasKey('EVENING', $blocked);
        $this->assertFalse($bookingService->checkSlotAvailability($hall->id, $bufferDate, 'EVENING'));

        $fullDayDate = Carbon::tomorrow()->addDay()->toDateString();
        Booking::create($this->bookingPayload($hall, $fullDayDate, [
            'slot_type' => 'FULL_DAY',
            'status' => 'CONFIRMED',
        ]));

        $fullDayBlocked = $availability->blockedSlotsForHall($hall, $fullDayDate);
        foreach (ShiftWindows::BOOKABLE_KEYS as $slot) {
            $this->assertArrayHasKey($slot, $fullDayBlocked);
            $this->assertFalse($bookingService->checkSlotAvailability($hall->id, $fullDayDate, $slot));
        }
    }

    public function test_inquiry_has_no_kot_or_booking_event_while_confirmed_does(): void
    {
        config(['platform.kitchen_module_enabled' => true]);
        Event::fake([BookingCreatedEvent::class]);

        [, , $hall] = $this->approvedVenue('kot');
        $service = app(BookingService::class);
        $kitchen = app(KitchenOrderService::class);

        $inquiry = $service->createReservation($this->bookingPayload($hall, Carbon::tomorrow()->toDateString(), [
            'status' => 'INQUIRY',
            'pipeline_stage' => 'NEW_INQUIRY',
        ]));
        $this->assertFalse($kitchen->hasKotForBooking($inquiry));
        Event::assertNotDispatched(BookingCreatedEvent::class);

        $hold = $service->createReservation($this->bookingPayload($hall, Carbon::tomorrow()->addDay()->toDateString(), [
            'slot_type' => 'EVENING',
            'status' => 'HOLD',
        ]));
        $this->assertFalse($kitchen->hasKotForBooking($hold));
        Event::assertDispatchedTimes(BookingCreatedEvent::class, 1);

        $confirmed = $service->createReservation($this->bookingPayload($hall, Carbon::tomorrow()->addDays(2)->toDateString(), [
            'slot_type' => 'NIGHT',
            'status' => 'CONFIRMED',
        ]));
        $this->assertTrue($kitchen->hasKotForBooking($confirmed));
        Event::assertDispatchedTimes(BookingCreatedEvent::class, 2);

        $kot = KitchenKot::where('booking_id', $confirmed->id)->firstOrFail();
        $kot->update(['preparation_status' => KitchenKot::STATUS_PREPARING]);
        $kitchen->generateKotForConfirmedBooking($confirmed->fresh());

        $this->assertSame(KitchenKot::STATUS_PREPARING, $kot->fresh()->preparation_status);
    }

    public function test_floor_staff_walk_in_creates_a_confirmed_booking(): void
    {
        [$tenant, , $hall] = $this->approvedVenue('floor');

        // Walk-in confirmed bookings are a front-desk capability; floor staff
        // are operational viewers under the least-privilege permission matrix.
        $staff = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Front Desk Member',
            'email' => 'frontdesk-'.$tenant->id.'@riwajgrand.pk',
            'phone' => '+92 300 8880000',
            'role' => 'ROLE_FRONT_DESK',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($staff);

        Livewire::test(WalkInBookingModal::class)
            ->set('hall_id', $hall->id)
            ->set('event_date', Carbon::tomorrow()->toDateString())
            ->set('slot_type', 'EVENING')
            ->set('customer_name', 'Walk In Host')
            ->set('customer_phone', '03001234567')
            ->set('guest_count', 250)
            ->call('confirmBooking')
            ->assertHasNoErrors();

        $this->assertSame('CONFIRMED', Booking::latest('id')->firstOrFail()->status);
    }

    public function test_public_inquiry_success_hides_agreement_link_and_shows_reference(): void
    {
        [, , $hall] = $this->approvedVenue('inquiry-ui');

        $component = Livewire::test(WalkInBookingModal::class)
            ->set('isOpen', true)
            ->set('hall_id', $hall->id)
            ->set('event_date', Carbon::tomorrow()->toDateString())
            ->set('slot_type', 'EVENING')
            ->set('customer_name', 'Public Inquiry Guest')
            ->set('customer_phone', '03007654321')
            ->set('guest_count', 200)
            ->call('confirmBooking')
            ->assertHasNoErrors()
            ->assertSee('Inquiry Received')
            ->assertDontSee('View Contract');

        $this->assertSame('INQUIRY', $component->get('createdBooking')->status);
        $component->assertSee($component->get('createdBooking')->booking_reference);
    }

    public function test_staff_chat_replies_are_persisted_into_the_selected_guest_thread(): void
    {
        [$tenant, , $hall] = $this->approvedVenue('chat');

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Chat Hall Owner',
            'email' => 'chat-owner-'.$tenant->id.'@riwajgrand.pk',
            'phone' => '+92 300 7770000',
            'role' => 'ROLE_TENANT_OWNER',
            'password' => Hash::make('password123'),
        ]);

        $guestIp = '203.0.113.7';
        $guestSession = 'guest-thread-hardening';
        HallChatMessage::create([
            'hall_id' => $hall->id,
            'ip_address' => $guestIp,
            'session_id' => $guestSession,
            'sender_type' => 'customer',
            'message_type' => 'text',
            'message' => 'Is this date available?',
        ]);

        $this->actingAs($owner);

        Livewire::test(HallChat::class, ['slug' => $hall->slug])
            ->call('setSelectedCustomer', $guestIp, $guestSession)
            ->set('newMessage', 'Yes, the date is available.')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $reply = HallChatMessage::where('hall_id', $hall->id)
            ->where('sender_type', 'hall_owner')
            ->firstOrFail();

        $this->assertSame($guestIp, $reply->ip_address);
        $this->assertSame($guestSession, $reply->session_id);

        Livewire::test(HallChat::class, ['slug' => $hall->slug])
            ->set('newMessage', 'A reply without a selected guest.')
            ->call('sendMessage')
            ->assertHasErrors('newMessage');

        $this->assertSame(1, HallChatMessage::where('hall_id', $hall->id)->where('sender_type', 'hall_owner')->count());
    }

    public function test_public_seo_urls_are_filter_free_and_event_venue_json_ld_is_unique(): void
    {
        [, , $hall] = $this->approvedVenue('seo');

        $response = $this->get(route('hall.showcase', $hall->slug).'?city=Lahore&shift=EVENING');
        $response->assertOk();

        $response->assertSee('rel="canonical" href="'.route('hall.showcase', $hall->slug).'"', false);
        $response->assertSee('<meta property="og:url" content="'.route('hall.showcase', $hall->slug).'">', false);
        $this->assertSame(1, substr_count((string) $response->getContent(), '"@type":"EventVenue"'));

        $this->get(route('robots'))
            ->assertOk()
            ->assertSee('Sitemap: '.url('/sitemap.xml'), false);
    }
}
