<?php

namespace Tests\Feature;

use App\Livewire\AdminDashboard;
use App\Livewire\Auth\Login;
use App\Livewire\HallMediaManager;
use App\Livewire\PublicHallDetails;
use App\Models\Hall;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminProvisionAndServicesCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_super_admin_can_provision_hall_and_set_credentials(): void
    {
        $superAdmin = User::where('role', 'ROLE_SUPER_ADMIN')->first();
        $this->actingAs($superAdmin);

        $plan = SubscriptionPlan::first();

        $component = Livewire::test(AdminDashboard::class)
            ->call('openProvisionModal')
            ->set('newBusinessName', 'Shehnai Royal Palace')
            ->set('newCity', 'Karachi')
            ->set('newAddress', 'Main Clifton Road, Block 4')
            ->set('newMinCapacity', 250)
            ->set('newMaxCapacity', 900)
            ->set('newBaseRent', 280000)
            ->set('newTagline', 'A Regal Jewel in Coastal Hospitality')
            ->set('newOwnerName', 'Malik Jahangir')
            ->set('newOwnerEmail', 'jahangir@shehnaipalace.pk')
            ->set('newOwnerPassword', 'SecretPass2026!')
            ->set('newOwnerPhone', '+923009988776')
            ->set('newPlanId', $plan->id)
            ->call('provisionHallAccount')
            ->assertSet('showProvisionModal', false);

        $this->assertNotNull($component->get('provisionedCredentials'));

        // Check user created with assigned password
        $owner = User::where('email', 'jahangir@shehnaipalace.pk')->first();
        $this->assertNotNull($owner);
        $this->assertEquals('ROLE_TENANT_OWNER', $owner->role);
        $this->assertTrue(Hash::check('SecretPass2026!', $owner->password));

        // Check Hall created
        $hall = Hall::where('name', 'Shehnai Royal Palace')->first();
        $this->assertNotNull($hall);
        $this->assertEquals('A Regal Jewel in Coastal Hospitality', $hall->tagline);
        $this->assertEquals(280000, (float)$hall->base_rent);
        $this->assertIsArray($hall->services);
        $this->assertNotEmpty($hall->services);
    }

    public function test_rent_admin_can_login_with_provisioned_credentials(): void
    {
        $plan = SubscriptionPlan::first();
        $tenant = Tenant::create([
            'name' => 'Pearl Continental Marquee',
            'slug' => 'pearl-marquee',
            'email' => 'pearl@owner.pk',
            'phone' => '+923001122334',
            'subscription_plan_id' => $plan->id,
            'subscription_status' => 'ACTIVE',
            'is_active' => true,
        ]);

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Chaudhry Akram',
            'email' => 'akram@pearlmarquee.pk',
            'password' => Hash::make('PearlMarquee123!'),
            'role' => 'ROLE_TENANT_OWNER',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'akram@pearlmarquee.pk')
            ->set('password', 'PearlMarquee123!')
            ->call('login')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($owner);
    }

    public function test_rent_admin_can_add_and_remove_services_catalog(): void
    {
        $owner = User::where('role', 'ROLE_TENANT_OWNER')->first() ?: User::where('email', 'reception@venuecloud.pk')->first();
        $this->actingAs($owner);

        $hall = Hall::where('tenant_id', $owner->tenant_id)->first();
        $this->assertNotNull($hall);

        Livewire::test(HallMediaManager::class)
            ->set('newServiceTitle', 'VIP Red Rose Stage Canopy')
            ->set('newServicePrice', 45000)
            ->set('newServiceCategory', 'Decor & Ambience')
            ->set('newServiceDescription', 'Fresh imported Dutch roses with candlelit stage pillars.')
            ->set('newServiceComplimentary', false)
            ->set('newServiceIcon', 'star')
            ->call('addService');

        $hall->refresh();
        $titles = collect($hall->services)->pluck('title')->toArray();
        $this->assertContains('VIP Red Rose Stage Canopy', $titles);

        // Now test removing a service
        $initialCount = count($hall->services);
        Livewire::test(HallMediaManager::class)
            ->call('removeService', $initialCount - 1);

        $hall->refresh();
        $this->assertEquals($initialCount - 1, count($hall->services));
    }

    public function test_public_hall_details_renders_dynamic_catalog_and_opulent_sections(): void
    {
        $hall = Hall::first();
        $hall->update([
            'tagline' => 'Pakistans Crown Jewel in Royal Celebrations',
            'services' => [
                [
                    'title' => 'Signature Drone Aerial Filming',
                    'price' => 75000,
                    'is_complimentary' => false,
                    'category' => 'Multimedia & Video',
                    'description' => 'Dual 4K cinematic drones with live stage vision mixing.',
                    'icon' => 'camera',
                ]
            ]
        ]);

        Livewire::test(PublicHallDetails::class, ['id' => $hall->id])
            ->assertSee($hall->name)
            ->assertSee('Signature Drone Aerial Filming')
            ->assertSee('PKR 75,000')
            ->assertSee('Upcoming 14-Day Shift Availability')
            ->assertSee('Verified Couple Experiences');
    }
}
