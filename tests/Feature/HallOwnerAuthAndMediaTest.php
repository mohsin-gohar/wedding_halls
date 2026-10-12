<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Dashboard;
use App\Livewire\HallMediaManager;
use App\Livewire\WalkInBookingModal;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class HallOwnerAuthAndMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_hall_owner_can_register_new_hall_and_auto_login(): void
    {
        Livewire::test(Register::class)
            ->set('hallName', 'Grand Pearl Palace')
            ->set('ownerName', 'Chaudhry Akram')
            ->set('email', 'akram@grandpearl.pk')
            ->set('phone', '+92 300 9876543')
            ->set('city', 'Faisalabad')
            ->set('capacity', 750)
            ->set('baseRent', 180000)
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->call('register')
            ->assertRedirect(route('hall.media'));

        $this->assertAuthenticated();

        // Verify database records
        $this->assertDatabaseHas('tenants', [
            'name' => 'Grand Pearl Palace',
            'email' => 'akram@grandpearl.pk',
        ]);

        $this->assertDatabaseHas('halls', [
            'name' => 'Grand Pearl Palace',
            'max_capacity' => 750,
            'base_rent' => 180000,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'akram@grandpearl.pk',
            'role' => 'ROLE_TENANT_OWNER',
        ]);
    }

    public function test_hall_owner_can_login_and_logout(): void
    {
        $tenant = Tenant::first();
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Owner',
            'email' => 'testowner@hall.pk',
            'phone' => '+92 300 1112233',
            'role' => 'ROLE_TENANT_OWNER',
            'password' => Hash::make('password123'),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'testowner@hall.pk')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        // Test logout
        $response = $this->post(route('logout'));
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_hall_owner_can_manage_photos_and_videos(): void
    {
        $tenant = Tenant::first();
        $hall = Hall::where('tenant_id', $tenant->id)->first();
        $user = User::first();

        $this->actingAs($user);

        Livewire::test(HallMediaManager::class)
            ->set('name', 'Updated Royal Palace Hall')
            ->set('newImageUrl', 'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1200')
            ->call('addImage')
            ->set('newVideoUrl', 'https://www.youtube.com/watch?v=7wtfhZwyrcc')
            ->call('addVideo')
            ->call('save')
            ->assertHasNoErrors();

        $hall->refresh();
        $this->assertEquals('Updated Royal Palace Hall', $hall->name);
        $this->assertContains('https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1200', $hall->images);
        $this->assertContains('https://www.youtube.com/embed/7wtfhZwyrcc', $hall->videos);
    }

    public function test_public_hall_showcase_renders_with_images_and_videos(): void
    {
        $hall = Hall::first();
        $response = $this->get(route('hall.showcase', $hall->id));
        $response->assertStatus(200);
        $response->assertSee($hall->name);
        $response->assertSee('Upcoming 14-Day Shift Availability');
    }

    public function test_dashboard_is_strictly_isolated_to_owner_tenant(): void
    {
        // Tenant 1
        $tenant1 = Tenant::first();
        $hall1 = Hall::where('tenant_id', $tenant1->id)->first();
        $owner1 = User::where('tenant_id', $tenant1->id)->first();

        // Create Tenant 2
        $tenant2 = Tenant::create([
            'name' => 'Competitor Banquet',
            'slug' => 'competitor-banquet',
            'email' => 'competitor@hall.pk',
            'phone' => '+92 300 0000000',
            'subscription_plan' => 'ENTERPRISE',
            'currency' => 'PKR',
            'is_active' => true,
        ]);

        $venue2 = Venue::create([
            'tenant_id' => $tenant2->id,
            'name' => 'Competitor Venue',
            'slug' => 'competitor-venue',
            'city' => 'Lahore',
            'phone' => '+92 300 0000000',
            'email' => 'competitor@hall.pk',
            'is_active' => true,
        ]);

        $hall2 = Hall::create([
            'tenant_id' => $tenant2->id,
            'venue_id' => $venue2->id,
            'name' => 'Competitor Hall',
            'min_capacity' => 100,
            'max_capacity' => 600,
            'base_rent' => 200000,
            'is_active' => true,
        ]);

        // Create booking for Tenant 2 with huge amount
        Booking::create([
            'tenant_id' => $tenant2->id,
            'hall_id' => $hall2->id,
            'booking_reference' => 'BK-TEST-TENANT2',
            'customer_name' => 'Competitor Client',
            'customer_phone' => '+92 300 9999999',
            'event_type' => 'BARAT',
            'event_date' => now()->format('Y-m-d'),
            'slot_type' => 'NIGHT',
            'guest_count' => 500,
            'per_head_rate' => 3000,
            'hall_rent' => 200000,
            'grand_total' => 1700000,
            'advance_paid' => 500000,
            'balance_due' => 1200000,
            'status' => 'CONFIRMED',
        ]);

        // Login as Owner 1
        $this->actingAs($owner1);

        $component = Livewire::test(Dashboard::class);
        $periodBookings = $component->viewData('allPeriodBookings');

        // Verify that competitor's booking is NOT in Owner 1's dashboard
        $bookingRefs = $periodBookings->pluck('booking_reference')->toArray();
        $this->assertNotContains('BK-TEST-TENANT2', $bookingRefs);

        // Verify competitor's hall is not in Owner 1's hall list
        $halls = $component->viewData('halls')->pluck('id')->toArray();
        $this->assertNotContains($hall2->id, $halls);
    }

    public function test_walk_in_booking_modal_opens_and_renders_correctly(): void
    {
        $hall = Hall::first();
        $this->assertNotNull($hall);

        Livewire::test(WalkInBookingModal::class)
            ->call('openModalWithData')
            ->assertSet('isOpen', true)
            ->assertSee('Walk-in Reservation Wizard')
            ->assertSee('Direct Booking Terminal')
            ->assertSee('Operational Shift Slot')
            ->assertSee('Afternoon')
            ->assertSee('Evening')
            ->assertSee('Late Night')
            ->set('slot_type', 'EVENING')
            ->assertSee('Slot is 100% available for booking!')
            ->call('goToStep', 2)
            ->assertSet('step', 2)
            ->assertSee('Host Full Name')
            ->call('closeModal')
            ->assertSet('isOpen', false);
    }
}
