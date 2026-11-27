<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\Tenant;
use App\Models\Venue;
use App\Services\PlatformAnalyticsService;
use App\Support\PlatformMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinancialReportingIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_ledger_foreign_keys_restrict_parent_deletion(): void
    {
        $constraints = [
            ['expenses', 'expenses_tenant_id_foreign'],
            ['staff_payrolls', 'staff_payrolls_tenant_id_foreign'],
            ['purchase_orders', 'purchase_orders_tenant_id_foreign'],
            ['service_bookings', 'service_bookings_tenant_id_foreign'],
            ['service_bookings', 'service_bookings_customer_id_foreign'],
            ['service_payments', 'service_payments_tenant_id_foreign'],
            ['service_payments', 'service_payments_service_booking_id_foreign'],
            ['service_payments', 'service_payments_customer_id_foreign'],
            ['vendor_payouts', 'vendor_payouts_tenant_id_foreign'],
            ['wallet_transactions', 'wallet_transactions_tenant_id_foreign'],
        ];

        foreach ($constraints as [$table, $constraint]) {
            $result = DB::selectOne(
                'SELECT DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
                [$table, $constraint],
            );

            $this->assertNotNull($result, "Missing financial foreign key {$constraint}.");
            $this->assertSame('RESTRICT', strtoupper($result->DELETE_RULE), "Unexpected delete rule for {$constraint}.");
        }
    }

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

        PlatformMetrics::flush();
        $this->assertSame(100000.0, (float) PlatformMetrics::snapshot()['totalGmv']);
    }
}
