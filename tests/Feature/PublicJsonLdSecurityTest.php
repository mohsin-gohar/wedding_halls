<?php

namespace Tests\Feature;

use App\Models\Hall;
use App\Models\Tenant;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicJsonLdSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_controlled_hall_fields_cannot_break_out_of_json_ld_script(): void
    {
        $payload = '</script><script>window.__xss=1</script>';

        $tenant = Tenant::create([
            'name' => 'Security Test Events',
            'slug' => 'security-test-events',
            'subdomain' => 'security-test-events',
            'is_active' => true,
            'verification_status' => 'APPROVED',
        ]);

        $venue = Venue::create([
            'tenant_id' => $tenant->id,
            'name' => 'Security Test Venue',
            'slug' => 'security-test-venue',
            'city' => 'Lahore',
            'area' => 'DHA',
            'address' => $payload,
            'contact_phone' => '03009998877',
        ]);

        $hall = Hall::create([
            'tenant_id' => $tenant->id,
            'venue_id' => $venue->id,
            'name' => $payload,
            'slug' => 'security-test-hall',
            'min_capacity' => 100,
            'max_capacity' => 500,
            'base_rent' => 100000,
            'is_active' => true,
        ]);

        $response = $this->get(route('hall.showcase', $hall->slug));

        $response->assertOk();
        $response->assertSee('application/ld+json', false);
        $response->assertDontSee($payload, false);
        $response->assertSee('\u003C/script\u003E', false);
        $response->assertSee('"@type":"EventVenue"', false);
    }
}
