<?php

namespace Tests\Feature;

use App\Livewire\AdminDashboard;
use App\Models\Hall;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class FreeLaunchAndInstantLiveApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_services_and_policies_pages_display_free_launch_and_instant_live_terms(): void
    {
        // 1. Services page
        $this->get(route('public.services'))
            ->assertStatus(200)
            ->assertSee('100% Free Launch Access')
            ->assertSee('Register Your Hall For Free')
            ->assertSee('Live on Public Website!');

        // 2. Terms page
        $this->get(route('public.policies.terms'))
            ->assertStatus(200)
            ->assertSee('100% Free Launch Onboarding &amp; Zero Platform Rent', false)
            ->assertSee('Admin Verification &amp; Instant Live Publication', false);

        // 3. Privacy page
        $this->get(route('public.policies.privacy'))
            ->assertStatus(200)
            ->assertSee('Free Registration Data &amp; Public Showcase Notice', false);

        // 4. FAQs page
        $this->get(route('public.faqs'))
            ->assertStatus(200)
            ->assertSee('Is listing my hall on EventHub free? When does it go live?');

        // 5. Register page
        $this->get(route('register'))
            ->assertStatus(200)
            ->assertSee('100% Free Launch Registration')
            ->assertSee('Free Onboarding &amp; Instant Live Launch', false);
    }

    public function test_hall_goes_live_on_marketplace_immediately_upon_super_admin_approval(): void
    {
        // 1. Create a newly registered vendor (status: PENDING)
        $tenant = Tenant::create([
            'name' => 'Kohinoor Grand Marquee',
            'slug' => 'kohinoor-grand-marquee',
            'email' => 'contact@kohinoor.pk',
            'phone' => '+92 300 7777777',
            'city' => 'Lahore',
            'subscription_status' => 'TRIAL',
            'verification_status' => 'PENDING',
            'is_active' => true,
        ]);

        $venue = Venue::create([
            'tenant_id' => $tenant->id,
            'name' => 'Kohinoor Complex',
            'slug' => 'kohinoor-complex',
            'city' => 'Lahore',
            'address' => 'Gulberg III, Lahore',
            'is_active' => true,
        ]);

        $hall = Hall::create([
            'tenant_id' => $tenant->id,
            'venue_id' => $venue->id,
            'name' => 'Kohinoor Grand Banquet',
            'min_capacity' => 200,
            'max_capacity' => 800,
            'base_rent' => 250000,
            'is_active' => true,
        ]);

        // Prior to approval, unapproved tenant is not visible on public venues list
        $this->get(route('public.venues'))
            ->assertStatus(200)
            ->assertDontSee('Kohinoor Grand Banquet');

        // 2. Super admin logs in and approves vendor
        $admin = User::create([
            'name' => 'Platform Super Admin',
            'email' => 'admin@eventhub.com',
            'phone' => '+92 300 0000000',
            'role' => 'ROLE_SUPER_ADMIN',
            'password' => Hash::make('password123'),
        ]);

        Livewire::actingAs($admin)
            ->test(AdminDashboard::class)
            ->call('approveVendor', $tenant->id)
            ->assertHasNoErrors();

        $tenant->refresh();
        $this->assertEquals('APPROVED', $tenant->verification_status);
        $this->assertTrue($tenant->is_active);

        $venue->refresh();
        $this->assertTrue($venue->is_active);

        $hall->refresh();
        $this->assertTrue($hall->is_active);

        // 3. Immediately after approval, hall is now live on public venues list!
        $this->get(route('public.venues'))
            ->assertStatus(200)
            ->assertSee('Kohinoor Grand Banquet');
    }
}
