<?php

namespace Tests\Feature;

use App\Livewire\Auth\Register;
use App\Livewire\PublicHallDetails;
use App\Livewire\PublicMarketplace;
use App\Livewire\PublicVenues;
use App\Livewire\WalkInBookingModal;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\KitchenKot;
use App\Models\Package;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class TenantPrivacyAndSecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_newly_registered_tenant_is_pending_verification_and_on_trial(): void
    {
        Livewire::test(Register::class)
            ->set('hallName', 'Skyline Grand Marquee')
            ->set('ownerName', 'Malik Tanveer')
            ->set('email', 'tanveer@skyline.pk')
            ->set('phone', '+92 300 7776655')
            ->set('city', 'Islamabad')
            ->set('capacity', 600)
            ->set('baseRent', 190000)
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->call('register');

        $tenant = Tenant::where('email', 'tanveer@skyline.pk')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('PENDING', $tenant->verification_status);
        $this->assertEquals('TRIAL', $tenant->subscription_status);
        $this->assertNotNull($tenant->subscription_starts_at);
        $this->assertNotNull($tenant->subscription_expires_at);
        $this->assertFalse($tenant->isApproved());
        $this->assertTrue($tenant->isPending());
    }

    public function test_unapproved_tenant_halls_are_hidden_from_public_and_return_404(): void
    {
        // 1. Create a pending tenant with a hall
        $pendingTenant = Tenant::create([
            'name' => 'Secret Garden Marquee',
            'slug' => 'secret-garden-marquee',
            'email' => 'garden@secret.pk',
            'phone' => '+92 300 9991122',
            'address' => 'Rawalpindi, Pakistan',
            'subscription_plan' => 'Starter Marquee',
            'currency' => 'PKR',
            'verification_status' => 'PENDING',
            'is_active' => true,
        ]);

        $venue = Venue::create([
            'tenant_id' => $pendingTenant->id,
            'name' => 'Secret Garden Complex',
            'slug' => 'secret-garden-complex',
            'city' => 'Rawalpindi',
            'address' => 'Rawalpindi, Pakistan',
            'is_active' => true,
        ]);

        $pendingHall = Hall::create([
            'tenant_id' => $pendingTenant->id,
            'venue_id' => $venue->id,
            'name' => 'Unapproved Private Hall',
            'min_capacity' => 200,
            'max_capacity' => 500,
            'base_rent' => 120000,
            'is_active' => true,
        ]);

        // 2. Check PublicVenues: pending hall should NOT appear
        Livewire::test(PublicVenues::class)
            ->assertDontSee('Unapproved Private Hall');

        // 3. Check Homepage: pending hall should NOT appear
        $response = $this->get(route('home'));
        $response->assertDontSee('Unapproved Private Hall');

        // 4. Accessing public hall details directly should return 404
        $detailsResponse = $this->get(route('hall.showcase', $pendingHall->id));
        $detailsResponse->assertStatus(404);

        // 5. Approve tenant: hall should now become visible
        $pendingTenant->update(['verification_status' => 'APPROVED']);

        $approvedResponse = $this->get(route('hall.showcase', $pendingHall->id));
        $approvedResponse->assertStatus(200);
        $approvedResponse->assertSee('Unapproved Private Hall');
    }

    public function test_marketplace_hides_unapproved_tenants_and_inactive_venues(): void
    {
        $pendingTenant = Tenant::create([
            'name' => 'Pending Marketplace Tenant',
            'slug' => 'pending-marketplace-tenant',
            'verification_status' => 'PENDING',
            'is_active' => true,
        ]);

        $pendingVenue = Venue::create([
            'tenant_id' => $pendingTenant->id,
            'name' => 'Pending Marketplace Venue',
            'slug' => 'pending-marketplace-venue',
            'city' => 'Karachi',
            'is_active' => true,
        ]);

        Hall::create([
            'tenant_id' => $pendingTenant->id,
            'venue_id' => $pendingVenue->id,
            'name' => 'Pending Marketplace Hall',
            'min_capacity' => 100,
            'max_capacity' => 500,
            'base_rent' => 100000,
            'is_active' => true,
        ]);

        $inactiveVenue = Venue::create([
            'tenant_id' => Tenant::first()->id,
            'name' => 'Inactive Marketplace Venue',
            'slug' => 'inactive-marketplace-venue',
            'city' => 'Karachi',
            'is_active' => false,
        ]);

        Hall::create([
            'tenant_id' => $inactiveVenue->tenant_id,
            'venue_id' => $inactiveVenue->id,
            'name' => 'Inactive Venue Hall',
            'min_capacity' => 100,
            'max_capacity' => 500,
            'base_rent' => 100000,
            'is_active' => true,
        ]);

        $inactiveTenant = Tenant::create([
            'name' => 'Inactive Marketplace Tenant',
            'slug' => 'inactive-marketplace-tenant',
            'verification_status' => 'APPROVED',
            'is_active' => false,
        ]);

        $inactiveTenantVenue = Venue::create([
            'tenant_id' => $inactiveTenant->id,
            'name' => 'Inactive Tenant Venue',
            'slug' => 'inactive-tenant-venue',
            'city' => 'Karachi',
            'is_active' => true,
        ]);

        Hall::create([
            'tenant_id' => $inactiveTenant->id,
            'venue_id' => $inactiveTenantVenue->id,
            'name' => 'Inactive Tenant Hall',
            'min_capacity' => 100,
            'max_capacity' => 500,
            'base_rent' => 100000,
            'is_active' => true,
        ]);

        Livewire::test(PublicMarketplace::class)
            ->assertDontSee('Pending Marketplace Hall')
            ->assertDontSee('Inactive Venue Hall')
            ->assertDontSee('Inactive Tenant Hall')
            ->assertDontSee('Pending Marketplace Venue');
    }

    public function test_marketplace_paginates_public_halls_instead_of_loading_the_full_catalog(): void
    {
        $tenant = Tenant::first();
        $venue = Venue::where('tenant_id', $tenant->id)->firstOrFail();
        $existingHallCount = Hall::where('tenant_id', $tenant->id)
            ->where('venue_id', $venue->id)
            ->where('is_active', true)
            ->count();

        foreach (range(1, 11) as $number) {
            Hall::create([
                'tenant_id' => $tenant->id,
                'venue_id' => $venue->id,
                'name' => sprintf('Scale Test Hall %02d', $number),
                'min_capacity' => 100,
                'max_capacity' => 500,
                'base_rent' => 100000,
                'is_active' => true,
            ]);
        }

        $expectedHallCount = $existingHallCount + 11;

        $component = Livewire::test(PublicMarketplace::class)
            ->assertViewHas('halls', function ($halls) use ($expectedHallCount) {
                return $halls->count() === 9 && $halls->total() === $expectedHallCount;
            });

        $component->call('gotoPage', 2)
            ->assertViewHas('halls', function ($halls) use ($expectedHallCount) {
                return $halls->count() === $expectedHallCount - 9
                    && $halls->total() === $expectedHallCount
                    && $halls->currentPage() === 2;
            });

        $component->set('searchQuery', 'Scale Test Hall')
            ->assertViewHas('halls', function ($halls) {
                return $halls->currentPage() === 1 && $halls->total() === 11;
            });
    }

    public function test_walk_in_booking_modal_cross_tenant_client_lookup_is_strictly_isolated(): void
    {
        // Tenant A
        $tenantA = Tenant::first();
        $ownerA = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Staff Member Tenant A',
            'email' => 'staffA_' . uniqid() . '@marq.pk',
            'phone' => '+92 300 1111222',
            'role' => 'ROLE_TENANT_OWNER',
            'password' => Hash::make('secret123'),
        ]);

        // Tenant B
        $tenantB = Tenant::create([
            'name' => 'Tenant B Luxury Hall',
            'slug' => 'tenant-b-hall',
            'email' => 'ownerb_' . uniqid() . '@luxury.pk',
            'phone' => '+92 300 3334444',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);

        $venueB = Venue::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Venue B',
            'slug' => 'venue-b-' . uniqid(),
            'city' => 'Lahore',
            'address' => 'Lahore',
            'is_active' => true,
        ]);

        $hallB = Hall::create([
            'tenant_id' => $tenantB->id,
            'venue_id' => $venueB->id,
            'name' => 'Hall B',
            'min_capacity' => 100,
            'max_capacity' => 500,
            'base_rent' => 100000,
            'is_active' => true,
        ]);

        // Existing booking for client under Tenant B
        Booking::create([
            'tenant_id' => $tenantB->id,
            'hall_id' => $hallB->id,
            'booking_number' => 'BK-B-' . rand(1000, 9999),
            'customer_name' => 'Private VIP Client',
            'customer_phone' => '+92 333 9998877',
            'customer_email' => 'clientb@vip.pk',
            'customer_cnic' => '35202-1234567-1',
            'event_date' => now()->addDays(30)->format('Y-m-d'),
            'slot_type' => 'EVENING',
            'event_type' => 'Valima',
            'guests_count' => 300,
            'total_amount' => 300000,
            'advance_paid' => 100000,
            'status' => 'CONFIRMED',
        ]);

        // Tenant A staff attempts to search for Tenant B's customer
        $this->actingAs($ownerA);

        Livewire::test(WalkInBookingModal::class)
            ->call('openModalWithData')
            ->call('goToStep', 2)
            ->set('customer_phone', '+92 333 9998877')
            ->call('lookupClient')
            ->assertSet('clientFound', false)
            ->assertDontSee('Private VIP Client');

        // Now create a booking for client under Tenant A
        $hallA = Hall::where('tenant_id', $tenantA->id)->first();
        Booking::create([
            'tenant_id' => $tenantA->id,
            'hall_id' => $hallA->id,
            'booking_number' => 'BK-A-' . rand(1000, 9999),
            'customer_name' => 'Tenant A Valued Guest',
            'customer_phone' => '+92 321 1112233',
            'customer_email' => 'clienta@guest.pk',
            'customer_cnic' => '35201-9876543-1',
            'event_date' => now()->addDays(25)->format('Y-m-d'),
            'slot_type' => 'EVENING',
            'event_type' => 'Barat',
            'guests_count' => 400,
            'total_amount' => 450000,
            'advance_paid' => 150000,
            'status' => 'CONFIRMED',
        ]);

        // Tenant A searches for their own customer -> Found
        Livewire::test(WalkInBookingModal::class)
            ->call('openModalWithData')
            ->call('goToStep', 2)
            ->set('customer_phone', '+92 321 1112233')
            ->call('lookupClient')
            ->assertSet('clientFound', true)
            ->assertSet('customer_name', 'Tenant A Valued Guest');
    }

    public function test_public_or_customer_submitting_booking_is_marked_as_inquiry_with_zero_advance(): void
    {
        $tenant = Tenant::first();
        $hall = Hall::where('tenant_id', $tenant->id)->where('is_active', true)->first();
        $package = Package::first();

        // Public / unauthenticated user
        Livewire::test(WalkInBookingModal::class)
            ->call('openModalWithData', ['hall_id' => $hall->id])
            ->set('event_date', now()->addDays(20)->format('Y-m-d'))
            ->set('slot_type', 'EVENING')
            ->set('event_type', 'Barat')
            ->call('goToStep', 2)
            ->set('customer_name', 'Public Guest Inquiry')
            ->set('customer_phone', '+92 300 5554433')
            ->set('customer_email', 'inquiry@guest.pk')
            ->call('goToStep', 3)
            ->set('guest_count', 400)
            ->set('package_id', $package->id)
            ->call('goToStep', 4)
            ->set('advance_paid', 50000) // Public user attempting to input advance
            ->call('confirmBooking')
            ->assertSet('step', 5);

        // Verify booking created as INQUIRY with 0 advance
        $booking = Booking::where('customer_phone', '+92 300 5554433')->latest()->first();
        $this->assertNotNull($booking);
        $this->assertEquals('INQUIRY', $booking->status);
        $this->assertEquals('NEW_INQUIRY', $booking->pipeline_stage);
        $this->assertEquals(0, (float) $booking->advance_paid);

        // Verify no fake payment receipt was created
        $paymentsCount = Payment::where('booking_id', $booking->id)->count();
        $this->assertEquals(0, $paymentsCount);
    }

    public function test_customer_is_forbidden_from_viewing_chef_internal_kot_document(): void
    {
        $tenant = Tenant::first();
        $hall = Hall::where('tenant_id', $tenant->id)->first();
        $package = Package::first();

        // Customer user
        $customer = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Host Customer',
            'email' => 'host_' . uniqid() . '@customer.pk',
            'phone' => '+92 300 4443322',
            'role' => 'ROLE_CUSTOMER',
            'password' => Hash::make('password123'),
        ]);

        // Booking owned by this customer
        $booking = Booking::create([
            'tenant_id' => $tenant->id,
            'hall_id' => $hall->id,
            'customer_id' => $customer->id,
            'booking_number' => 'BK-' . rand(10000, 99999),
            'customer_name' => 'Host Customer',
            'customer_phone' => '+92 300 4443322',
            'event_date' => now()->addDays(15)->format('Y-m-d'),
            'slot_type' => 'EVENING',
            'event_type' => 'Barat',
            'guests_count' => 350,
            'package_id' => $package->id,
            'total_amount' => 500000,
            'advance_paid' => 150000,
            'status' => 'CONFIRMED',
        ]);

        $kot = KitchenKot::create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'kot_number' => 'KOT-202610-' . rand(100, 999),
            'guest_count' => 350,
            'preparation_status' => 'PENDING',
        ]);

        // Customer accessing their own agreement -> OK (200)
        $this->actingAs($customer);
        $agreementResponse = $this->get(route('documents.agreement', $booking->id));
        $agreementResponse->assertStatus(200);

        // Customer attempting to view operational Chef KOT -> Forbidden (403)
        $kotResponse = $this->get(route('documents.kot', $kot->id));
        $kotResponse->assertStatus(403);

        // Operational staff accessing Chef KOT -> Allowed (200)
        $staff = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Operational Staff',
            'email' => 'staff_' . uniqid() . '@marquee.pk',
            'phone' => '+92 300 8887766',
            'role' => 'ROLE_TENANT_OWNER',
            'password' => Hash::make('password123'),
        ]);
        $this->actingAs($staff);
        $staffKotResponse = $this->get(route('documents.kot', $kot->id));
        $staffKotResponse->assertStatus(200);
    }
}
