<?php

namespace Tests\Feature;

use App\Livewire\Auth\Register;
use App\Livewire\FloorPlanDesigner;
use App\Livewire\HallMediaManager;
use App\Livewire\KitchenKotBoard;
use App\Livewire\PublicContact;
use App\Livewire\PublicGuestRsvp;
use App\Livewire\PublicAvailabilityChecker;
use App\Livewire\PublicVenues;
use App\Livewire\UtilityFuelTracker;
use App\Livewire\WhatsAppRsvpManager;
use App\Models\Booking;
use App\Models\ContactInquiry;
use App\Models\GuestRsvp;
use App\Models\Hall;
use App\Models\PlatformTask;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class EnterpriseRemediationAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test DATA-01: Cancelled bookings allow the slot to be immediately re-booked
     * without triggering database unique constraint deadlocks.
     */
    public function test_cancelled_booking_allows_immediate_slot_rebooking(): void
    {
        $hall = Hall::where('is_active', true)->first();
        $this->assertNotNull($hall);

        $eventDate = Carbon::tomorrow()->addDays(5)->format('Y-m-d');
        $slotType = 'EVENING';

        $bookingService = app(BookingService::class);

        // 1. Create first reservation
        $booking1 = $bookingService->createReservation([
            'hall_id' => $hall->id,
            'event_date' => $eventDate,
            'slot_type' => $slotType,
            'customer_name' => 'Original Client',
            'customer_phone' => '03001112233',
            'guest_count' => 250,
            'status' => 'CONFIRMED',
        ]);

        $this->assertEquals('CONFIRMED', $booking1->status);

        // 2. Client cancels booking
        $booking1->update(['status' => 'CANCELLED']);
        $this->assertEquals('CANCELLED', $booking1->fresh()->status);

        // 3. New client reserves the exact same date & slot
        $booking2 = $bookingService->createReservation([
            'hall_id' => $hall->id,
            'event_date' => $eventDate,
            'slot_type' => $slotType,
            'customer_name' => 'New Replacement Client',
            'customer_phone' => '03219998877',
            'guest_count' => 300,
            'status' => 'CONFIRMED',
        ]);

        $this->assertNotNull($booking2);
        $this->assertEquals('New Replacement Client', $booking2->customer_name);
        $this->assertEquals('CONFIRMED', $booking2->status);
    }

    /**
     * Test SEC-01: Customer accounts are blocked from accessing vendor ERP tools.
     */
    public function test_customer_role_cannot_access_vendor_erp_tools(): void
    {
        $customer = User::create([
            'name' => 'Customer User',
            'email' => 'customer_audit@test.pk',
            'password' => Hash::make('password'),
            'role' => 'ROLE_CUSTOMER',
            'tenant_id' => null,
        ]);

        // Attempting to visit vendor ERP routes redirects to customer portal
        $this->actingAs($customer)->get('/kitchen-kots')
            ->assertRedirect(route('customer.dashboard'));

        $this->actingAs($customer)->get('/seating-planner')
            ->assertRedirect(route('customer.dashboard'));

        $this->actingAs($customer)->get('/utility-fuel')
            ->assertRedirect(route('customer.dashboard'));

        $this->actingAs($customer)->get('/hall/media')
            ->assertRedirect(route('customer.dashboard'));

        // Component mount directly throws 403 for customers
        Livewire::actingAs($customer)
            ->test(KitchenKotBoard::class)
            ->assertForbidden();

        Livewire::actingAs($customer)
            ->test(FloorPlanDesigner::class)
            ->assertForbidden();

        Livewire::actingAs($customer)
            ->test(UtilityFuelTracker::class)
            ->assertForbidden();

        Livewire::actingAs($customer)
            ->test(HallMediaManager::class)
            ->assertForbidden();
    }

    /**
     * Test SEC-02: Public guest RSVP search rejects single-digit or short searches
     * and strictly requires complete phone numbers.
     */
    public function test_public_guest_rsvp_rejects_enumeration_and_requires_valid_phone(): void
    {
        $hall = Hall::first();
        $booking = Booking::first();

        GuestRsvp::create([
            'tenant_id' => $hall->tenant_id,
            'booking_id' => $booking->id,
            'guest_name' => 'VIP Sheikh Tariq',
            'phone' => '+923339998877',
            'attending_count' => 2,
            'rsvp_status' => 'TENTATIVE',
        ]);

        // 1. Single digit search is rejected
        Livewire::test(PublicGuestRsvp::class)
            ->set('phone', '3')
            ->call('searchInvitation')
            ->assertSet('foundRsvp', null)
            ->assertSee('minimum 10 digits');

        // 2. Short number search is rejected
        Livewire::test(PublicGuestRsvp::class)
            ->set('phone', '0333999')
            ->call('searchInvitation')
            ->assertSet('foundRsvp', null)
            ->assertSee('minimum 10 digits');

        // 3. Complete 10/11 digit number locates the invitation
        Livewire::test(PublicGuestRsvp::class)
            ->set('phone', '03339998877')
            ->call('searchInvitation')
            ->assertSet('foundRsvp.guest_name', 'VIP Sheikh Tariq');
    }

    /**
     * Test UI-01: Uppercase slot_type bookings accurately populate the 30-day availability heatmap.
     */
    public function test_venue_directory_uses_only_city_and_hall_name_filters(): void
    {
        $hall = Hall::where('is_active', true)->first();
        $directory = Livewire::test(PublicVenues::class)
            ->assertSee('id="venue-city"', false)
            ->assertSee('id="venue-search"', false)
            ->assertDontSee('Event Date')
            ->assertDontSee('Min Guests');

        $directory
            ->set('city', $hall->venue->city)
            ->set('search', $hall->name)
            ->assertSee($hall->name);

        $directory
            ->set('search', 'definitely-no-such-wedding-hall')
            ->assertSee('No venues match these filters');
    }

    public function test_full_day_booking_blocks_every_shift_in_public_availability_checker(): void
    {
        $hall = Hall::where('is_active', true)->first();
        $eventDate = Carbon::tomorrow()->addDays(8)->format('Y-m-d');

        Booking::create([
            'tenant_id' => $hall->tenant_id,
            'hall_id' => $hall->id,
            'customer_name' => 'Full Day Availability Test',
            'customer_phone' => '03008887766',
            'event_date' => $eventDate,
            'slot_type' => 'FULL_DAY',
            'status' => 'CONFIRMED',
            'guest_count' => 200,
            'grand_total' => 150000,
        ]);

        Livewire::test(PublicAvailabilityChecker::class)
            ->set('selectedDate', $eventDate)
            ->assertViewHas('bookings', function ($bookings) use ($hall) {
                return $bookings->has($hall->id . '_AFTERNOON')
                    && $bookings->has($hall->id . '_EVENING')
                    && $bookings->has($hall->id . '_NIGHT');
            });
    }

    /**
     * Test FUNC-01: Public contact form persists inquiries to database and creates PlatformTask.
     */
    public function test_public_contact_form_persists_inquiry_and_super_admin_task(): void
    {
        Livewire::test(PublicContact::class)
            ->set('name', 'Hamza Butt')
            ->set('email', 'hamza@example.com')
            ->set('phone', '03005554433')
            ->set('city', 'Lahore')
            ->set('eventDate', Carbon::tomorrow()->addMonths(2)->format('Y-m-d'))
            ->set('message', 'Inquiring regarding corporate banquet booking for 400 executives.')
            ->call('submitInquiry')
            ->assertSet('isSubmitted', true);

        // Verify database persistence
        $inquiry = ContactInquiry::where('email', 'hamza@example.com')->first();
        $this->assertNotNull($inquiry);
        $this->assertEquals('Hamza Butt', $inquiry->name);
        $this->assertEquals('NEW', $inquiry->status);

        // Verify task creation for Super Admin
        $task = PlatformTask::where('title', 'like', '%Hamza Butt%')->first();
        $this->assertNotNull($task);
    }

    /**
     * Test FUNC-03: Vendor registration respects platform_vendor_registration_open switch.
     */
    public function test_vendor_registration_respects_master_switch(): void
    {
        // Toggle switch OFF
        Cache::put('platform_vendor_registration_open', false, 3600);

        Livewire::test(Register::class)
            ->set('hallName', 'Blocked Venue')
            ->set('ownerName', 'Blocked Owner')
            ->set('email', 'blocked@venuecloud.pk')
            ->set('phone', '03009990011')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['hallName']);

        // Clean up cache
        Cache::forget('platform_vendor_registration_open');
    }
}
