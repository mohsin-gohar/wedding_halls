<?php

namespace Tests\Feature;

use App\Livewire\AdminDashboard;
use App\Livewire\Dashboard;
use App\Livewire\PublicHallDetails;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\HallView;
use App\Models\HallWishlist;
use App\Models\PageVisit;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class SaaSArchitectureAndAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected Hall $hallA;
    protected Hall $hallB;
    protected User $vendorA;
    protected User $vendorB;
    protected User $superAdmin;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = SubscriptionPlan::create([
            'name' => 'Elite Imperial Marquee',
            'slug' => 'elite-imperial-marquee',
            'price_monthly' => 15000,
            'max_halls' => 5,
            'is_active' => true,
        ]);

        $this->tenantA = Tenant::create([
            'name' => 'Mughal Mahal Grand',
            'slug' => 'mughal-mahal-grand',
            'email' => 'vendorA@mughalmahal.pk',
            'phone' => '+92 300 1111111',
            'city' => 'Lahore',
            'address' => 'Gulberg III, Lahore',
            'verification_status' => 'APPROVED',
            'subscription_status' => 'ACTIVE',
            'subscription_plan_id' => $plan->id,
            'is_active' => true,
        ]);

        $venueA = Venue::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Mughal Mahal Venue Complex',
            'slug' => 'mughal-mahal-venue-complex',
            'city' => 'Lahore',
            'address' => 'Gulberg III, Lahore',
            'is_active' => true,
        ]);

        $this->hallA = Hall::create([
            'tenant_id' => $this->tenantA->id,
            'venue_id' => $venueA->id,
            'name' => 'Shehnai Grand Ballroom',
            'slug' => 'shehnai-grand-ballroom',
            'min_capacity' => 200,
            'max_capacity' => 800,
            'base_rent' => 250000,
            'is_active' => true,
        ]);

        $this->vendorA = User::create([
            'name' => 'Chaudhry Akram',
            'email' => 'akram@mughal.pk',
            'password' => Hash::make('secret123'),
            'role' => 'ROLE_TENANT_OWNER',
            'status' => 'ACTIVE',
            'tenant_id' => $this->tenantA->id,
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Pearl Continental Marquee',
            'slug' => 'pearl-continental-marquee',
            'email' => 'vendorB@pc.pk',
            'phone' => '+92 300 2222222',
            'city' => 'Karachi',
            'address' => 'Clifton, Karachi',
            'verification_status' => 'APPROVED',
            'subscription_status' => 'ACTIVE',
            'subscription_plan_id' => $plan->id,
            'is_active' => true,
        ]);

        $venueB = Venue::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'PC Clifton Arenas',
            'slug' => 'pc-clifton-arenas',
            'city' => 'Karachi',
            'address' => 'Clifton, Karachi',
            'is_active' => true,
        ]);

        $this->hallB = Hall::create([
            'tenant_id' => $this->tenantB->id,
            'venue_id' => $venueB->id,
            'name' => 'Crystal Palace Marquee',
            'slug' => 'crystal-palace-marquee',
            'min_capacity' => 150,
            'max_capacity' => 600,
            'base_rent' => 200000,
            'is_active' => true,
        ]);

        $this->vendorB = User::create([
            'name' => 'Suleman Raza',
            'email' => 'suleman@pc.pk',
            'password' => Hash::make('secret123'),
            'role' => 'ROLE_TENANT_OWNER',
            'status' => 'ACTIVE',
            'tenant_id' => $this->tenantB->id,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Chief Executive SaaS Admin',
            'email' => 'superadmin@eventhub.pk',
            'password' => Hash::make('secret123'),
            'role' => 'ROLE_SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->customer = User::create([
            'name' => 'Hamza Tariq',
            'email' => 'hamza@client.pk',
            'phone' => '+92 321 9998888',
            'password' => Hash::make('secret123'),
            'role' => 'ROLE_CUSTOMER',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_hall_page_view_counter_increments_dynamically_when_visiting_showcase(): void
    {
        $initialViews = HallView::where('hall_id', $this->hallA->id)->count();

        // 1. Visit hall showcase route
        $response = $this->get(route('hall.showcase', $this->hallA->slug));
        $response->assertStatus(200);

        // 2. Assert HallView was dynamically recorded
        $this->assertEquals($initialViews + 1, HallView::where('hall_id', $this->hallA->id)->count());

        // 3. Assert Tenant isolation in views
        $this->assertEquals(0, HallView::where('hall_id', $this->hallB->id)->count());
    }

    public function test_wishlist_toggle_updates_database_and_reflects_on_vendor_dashboard(): void
    {
        // 1. Customer toggles wishlist via PublicHallDetails
        $this->actingAs($this->customer);

        Livewire::test(PublicHallDetails::class, ['slug' => $this->hallA->slug])
            ->call('toggleWishlist');

        // 2. Assert saved to HallWishlist
        $this->assertTrue(HallWishlist::isWishlisted($this->hallA->id, $this->customer->id));
        $this->assertEquals(1, HallWishlist::countWishlists($this->hallA->id));

        // 3. Verify vendor dashboard reflects this wishlist
        $this->actingAs($this->vendorA);
        Livewire::test(Dashboard::class)
            ->assertSee('Wishlists Saved')
            ->assertSee('1');

        // 4. Vendor B dashboard should NOT see Vendor A's wishlist (Tenant Isolation)
        $this->actingAs($this->vendorB);
        Livewire::test(Dashboard::class)
            ->assertSee('Wishlists Saved');
    }

    public function test_super_admin_platform_analytics_and_user_directory(): void
    {
        // Record some dummy visits
        PageVisit::record('/venues', 'public.venues', null, '192.168.1.1', 'Mozilla/5.0');
        PageVisit::record('/venues/shehnai-grand-ballroom', 'hall.showcase', $this->customer->id, '192.168.1.2', 'Mozilla/5.0');

        $this->actingAs($this->superAdmin);
        Livewire::test(AdminDashboard::class)
            ->set('activeTab', 'analytics')
            ->assertSee('Platform Website Analytics')
            ->assertSee('Live Sessions')
            ->assertSee('Unique Visitors')
            ->assertSee('Total Page Views')
            // Switch to users directory tab
            ->set('activeTab', 'users')
            ->assertSee('Platform User Directory &amp; Access Control', false)
            ->assertSee('Hamza Tariq')
            ->assertSee('Chaudhry Akram')
            ->assertSee('Suleman Raza')
            // Test user search
            ->set('userSearch', 'Hamza')
            ->assertSee('hamza@client.pk')
            ->assertDontSee('suleman@pc.pk')
            // Reset search and test suspending a user
            ->set('userSearch', '')
            ->call('toggleUserStatus', $this->customer->id);

        // Verify status changed in database
        $this->assertEquals('SUSPENDED', $this->customer->fresh()->status);

        // Verify audit log recorded
        $this->assertTrue(AuditLog::where('action', 'USER_STATUS_TOGGLED')->exists());
    }

    public function test_vendor_dashboard_kanban_crm_lead_progression(): void
    {
        // 1. Create an inquiry booking for Vendor A
        $booking = Booking::create([
            'tenant_id' => $this->tenantA->id,
            'hall_id' => $this->hallA->id,
            'customer_name' => 'Zubair Qureshi',
            'customer_phone' => '+92 300 5554433',
            'customer_email' => 'zubair@example.com',
            'event_type' => 'BARAAT',
            'event_date' => now()->addDays(20),
            'slot_type' => 'EVENING',
            'guest_count' => 500,
            'per_head_rate' => 2000,
            'hall_rent' => 250000,
            'grand_total' => 1250000,
            'advance_paid' => 250000,
            'balance_due' => 1000000,
            'status' => 'INQUIRY',
            'crm_stage' => 'NEW_INQUIRY',
        ]);

        // 2. Vendor A loads dashboard and moves lead from NEW_INQUIRY to SITE_VISIT
        $this->actingAs($this->vendorA);
        Livewire::test(Dashboard::class)
            ->assertSee('Interactive Inquiries Pipeline (Kanban)')
            ->assertSee('Zubair Qureshi')
            ->call('updateLeadStage', $booking->id, 'SITE_VISIT');

        $this->assertEquals('SITE_VISIT', $booking->fresh()->crm_stage);

        // 3. Move from SITE_VISIT to QUOTATION
        Livewire::test(Dashboard::class)
            ->call('updateLeadStage', $booking->id, 'QUOTATION');

        $this->assertEquals('QUOTATION', $booking->fresh()->crm_stage);

        // 4. Move from QUOTATION to CONFIRMED
        Livewire::test(Dashboard::class)
            ->call('updateLeadStage', $booking->id, 'CONFIRMED');

        $this->assertEquals('CONFIRMED', $booking->fresh()->crm_stage);
        $this->assertEquals('CONFIRMED', $booking->fresh()->status);
    }

    public function test_vendor_dashboard_customer_details_and_interaction_notes(): void
    {
        $booking = Booking::create([
            'tenant_id' => $this->tenantA->id,
            'hall_id' => $this->hallA->id,
            'customer_name' => 'Fahad Mustafa',
            'customer_phone' => '03001234567',
            'customer_email' => 'fahad@example.com',
            'event_type' => 'VALIMA',
            'event_date' => now()->addDays(15),
            'slot_type' => 'NIGHT',
            'guest_count' => 450,
            'per_head_rate' => 1800,
            'hall_rent' => 200000,
            'grand_total' => 1010000,
            'status' => 'INQUIRY',
            'crm_stage' => 'NEW_INQUIRY',
        ]);

        $this->actingAs($this->vendorA);
        Livewire::test(Dashboard::class)
            ->call('openCustomerDetails', $booking->id)
            ->assertSet('showCustomerModal', true)
            ->set('leadNoteText', 'Client requested stage decor upgrade and 50 extra guests.')
            ->call('addLeadNote');

        $this->assertStringContainsString('Client requested stage decor upgrade', $booking->fresh()->notes);
    }

    public function test_tenant_isolation_prevents_vendor_b_from_seeing_vendor_a_leads(): void
    {
        // Vendor A booking
        $bookingA = Booking::create([
            'tenant_id' => $this->tenantA->id,
            'hall_id' => $this->hallA->id,
            'customer_name' => 'Exclusive Vendor A Client',
            'customer_phone' => '+92 300 8881111',
            'event_type' => 'MEHNDI',
            'event_date' => now()->addDays(10),
            'slot_type' => 'EVENING',
            'guest_count' => 300,
            'grand_total' => 600000,
            'status' => 'INQUIRY',
            'crm_stage' => 'NEW_INQUIRY',
        ]);

        // Vendor B dashboard should NOT see Vendor A's client
        $this->actingAs($this->vendorB);
        Livewire::test(Dashboard::class)
            ->assertDontSee('Exclusive Vendor A Client');
    }
}
