<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\Tenant;
use App\Models\Venue;
use App\Services\PlatformAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialReportingIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_gmv_excludes_unconfirmed_hold_bookings(): void
    {
        $tenant = Tenant::create([
            'name' => 'GMV Test Group', 'slug' => 'gmv-test-group',
            'subdomain' => 'gmv-test-group', 'is_active' => true,
            'verification_status' => 'APPROVED',
        ]);
        $venue = Venue::create([
            'tenant_id' => $tenant->id, 'name' => 'GMV Venue', 'slug' => 'gmv-venue',
            'city' => 'Lahore', 'is_active' => true,
        ]);
        $hall = Hall::create([
            'tenant_id' => $tenant->id, 'venue_id' => $venue->id, 'name' => 'GMV Hall',
            'slug' => 'gmv-hall', 'min_capacity' => 50, 'max_capacity' => 500,
            'base_rent' => 100000, 'is_active' => true,
        ]);

        foreach ([['HOLD', 900000], ['CONFIRMED', 100000]] as [$status, $amount]) {
            Booking::create([
                'tenant_id' => $tenant->id, 'hall_id' => $hall->id,
                'customer_name' => 'GMV Test', 'customer_phone' => '+92 300 9999999',
                'event_date' => now()->toDateString(), 'slot_type' => 'EVENING',
                'guest_count' => 200, 'status' => $status, 'grand_total' => $amount,
            ]);
        }

        $data = app(PlatformAnalyticsService::class)->chartData('WEEKLY', 'gmv');

        $today = now()->format('D');
        $todayPoint = collect($data)->firstWhere('label', $today);
        $this->assertNotNull($todayPoint);
        // HOLD is pipeline value, not GMV: only the CONFIRMED 100,000 counts.
        $this->assertSame(100000.0, (float) $todayPoint['value']);
    }
}
