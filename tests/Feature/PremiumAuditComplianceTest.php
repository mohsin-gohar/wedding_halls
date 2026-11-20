<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\CustomerLogin;
use App\Models\Hall;
use App\Models\Tenant;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class PremiumAuditComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_xml_returns_valid_xml_with_approved_halls_only(): void
    {
        $approvedTenant = Tenant::create([
            'name' => 'Royal Pearl Banquets',
            'slug' => 'royal-pearl-banquets',
            'subdomain' => 'royal-pearl-banquets',
            'is_active' => true,
            'verification_status' => 'APPROVED',
            'marketplace_visible' => true,
        ]);

        $approvedVenue = Venue::create([
            'tenant_id' => $approvedTenant->id,
            'name' => 'Royal Pearl Complex',
            'slug' => 'royal-pearl-complex',
            'city' => 'Lahore',
            'area' => 'Gulberg III',
            'address' => 'Main Boulevard Gulberg',
            'contact_phone' => '03001234567',
        ]);

        $approvedHall = Hall::create([
            'tenant_id' => $approvedTenant->id,
            'venue_id' => $approvedVenue->id,
            'name' => 'Emerald Crystal Hall',
            'slug' => 'emerald-crystal-hall',
            'min_capacity' => 200,
            'max_capacity' => 800,
            'base_rent' => 250000,
            'is_active' => true,
        ]);

        $pendingTenant = Tenant::create([
            'name' => 'Unverified Marquee',
            'slug' => 'unverified-marquee',
            'subdomain' => 'unverified-marquee',
            'is_active' => true,
            'verification_status' => 'PENDING',
        ]);

        $pendingVenue = Venue::create([
            'tenant_id' => $pendingTenant->id,
            'name' => 'Unverified Complex',
            'slug' => 'unverified-complex',
            'city' => 'Karachi',
            'area' => 'DHA',
            'address' => 'Phase 6',
            'contact_phone' => '03007654321',
        ]);

        Hall::create([
            'tenant_id' => $pendingTenant->id,
            'venue_id' => $pendingVenue->id,
            'name' => 'Hidden Pending Hall',
            'slug' => 'hidden-pending-hall',
            'min_capacity' => 100,
            'max_capacity' => 400,
            'base_rent' => 150000,
            'is_active' => true,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/xml', (string) $response->headers->get('Content-Type'));
        $response->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', false);
        $response->assertSee(route('hall.showcase', $approvedHall->slug), false);
        $response->assertDontSee('hidden-pending-hall', false);
    }

    public function test_public_pages_include_seo_meta_canonical_pwa_and_accessibility_landmarks(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('<link rel="manifest"', false);
        $response->assertSee('property="og:title"', false);
        $response->assertSee('property="og:description"', false);
        $response->assertSee('name="twitter:card"', false);
        $response->assertSee('Skip to main content', false);
        $response->assertSee('<main id="main-content"', false);
        $response->assertSee('role="region"', false);
        $response->assertSee('aria-label="Cookie consent notice"', false);
        $response->assertDontSee('i.pinimg.com', false);
    }

    public function test_hall_showcase_renders_event_venue_json_ld_structured_data(): void
    {
        $tenant = Tenant::create([
            'name' => 'Serena Palace Events',
            'slug' => 'serena-palace-events',
            'subdomain' => 'serena-palace-events',
            'is_active' => true,
            'verification_status' => 'APPROVED',
        ]);

        $venue = Venue::create([
            'tenant_id' => $tenant->id,
            'name' => 'Serena Grand Complex',
            'slug' => 'serena-grand-complex',
            'city' => 'Islamabad',
            'area' => 'F-7',
            'address' => 'Constitution Avenue',
            'contact_phone' => '03009998877',
        ]);

        $hall = Hall::create([
            'tenant_id' => $tenant->id,
            'venue_id' => $venue->id,
            'name' => 'Kohinoor Grand Ballroom',
            'slug' => 'kohinoor-grand-ballroom',
            'min_capacity' => 300,
            'max_capacity' => 1000,
            'base_rent' => 350000,
            'is_active' => true,
        ]);

        $response = $this->get(route('hall.showcase', $hall->slug));

        $response->assertStatus(200);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('"@type":"EventVenue"', false);
        $response->assertSee('Kohinoor Grand Ballroom', false);
        $response->assertSee('PKR 350,000', false);
    }

    public function test_custom_error_views_render_luxury_branded_responses(): void
    {
        $view404 = view('errors.404')->render();
        $this->assertStringContainsString('404', $view404);
        $this->assertStringContainsString('Return Home', $view404);

        $view500 = view('errors.500')->render();
        $this->assertStringContainsString('500', $view500);
        $this->assertStringContainsString('Return Home', $view500);
    }

    public function test_core_tables_have_performance_indexes(): void
    {
        $this->assertTrue(Schema::hasIndex('halls', ['slug']));
        $this->assertTrue(Schema::hasIndex('tenants', ['verification_status']));
        $this->assertTrue(Schema::hasIndex('bookings', ['event_date']));
    }

    public function test_native_http_post_login_authenticates_and_redirects_all_portal_roles(): void
    {
        $this->seed();

        // 1. Super Admin via POST /login
        $response = $this->post(route('login.perform'), [
            'email' => 'admin@eventhub.com',
            'password' => 'admin1235',
        ]);
        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
        $this->get('/admin')->assertStatus(200);

        // 2. Hall Owner via POST /login (can switch directly even while logged in)
        $response = $this->post(route('login.perform'), [
            'email' => 'owner@venuecloud.pk',
            'password' => 'password',
        ]);
        $response->assertRedirect(route('dashboard'));
        $this->get('/dashboard')->assertStatus(200);

        // 3. Customer via POST /customer/login
        $response = $this->post(route('customer.login.perform'), [
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);
        $response->assertRedirect(route('customer.dashboard'));
        $this->get('/customer/dashboard')->assertStatus(200);

        // 4. Customer via Livewire Login component
        Livewire::test(Login::class)
            ->set('email', 'customer@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('customer.dashboard'));

        // 5. Customer via Livewire CustomerLogin component
        Livewire::test(CustomerLogin::class)
            ->set('email', 'customer@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('customer.dashboard'));
    }
}
