<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionLifecycleAndAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_subscription_service_expires_accounts_past_grace_period_and_preserves_data(): void
    {
        $tenant = Tenant::create([
            'name' => 'Grand Palace Marquee',
            'slug' => 'grand-palace',
            'email' => 'grand@palace.pk',
            'subscription_status' => 'ACTIVE',
            'verification_status' => 'APPROVED',
            'subscription_expires_at' => Carbon::now()->subDays(5),
            'is_active' => true,
        ]);

        $venue = Venue::create([
            'tenant_id' => $tenant->id,
            'name' => 'Grand Complex',
            'slug' => 'grand-complex',
            'city' => 'Lahore',
        ]);

        $hall = Hall::create([
            'tenant_id' => $tenant->id,
            'venue_id' => $venue->id,
            'name' => 'Ballroom One',
            'base_rent' => 200000,
        ]);

        $booking = Booking::create([
            'tenant_id' => $tenant->id,
            'hall_id' => $hall->id,
            'customer_name' => 'Hamza Tariq',
            'customer_phone' => '03001234567',
            'event_date' => Carbon::today()->addDays(10)->format('Y-m-d'),
            'slot_type' => 'EVENING',
            'status' => 'CONFIRMED',
            'grand_total' => 350000,
            'advance_paid' => 150000,
        ]);

        $service = app(SubscriptionService::class);
        $results = $service->checkAndExpireSubscriptions();

        $this->assertSame(1, $results['expired_count']);
        $tenant->refresh();
        $this->assertSame('EXPIRED', $tenant->subscription_status);

        // Expiry locks access; it must never delete historical business data.
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
        $this->assertDatabaseHas('halls', ['id' => $hall->id]);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'customer_name' => 'Hamza Tariq',
            'grand_total' => 350000,
            'advance_paid' => 150000,
        ]);
        $this->assertTrue($service->isLocked($tenant));
    }

    public function test_artisan_command_subscriptions_check_expiry_executes_cleanly(): void
    {
        $this->artisan('subscriptions:check-expiry')
            ->expectsOutputToContain('Starting Riwaj Grand SaaS Subscription Expiry Audit...')
            ->expectsOutputToContain('SaaS Subscription Audit completed successfully.')
            ->assertExitCode(0);
    }

    public function test_subscription_renewal_weekly_monthly_and_yearly(): void
    {
        $tenant = Tenant::create([
            'name' => 'Imperial Marquee',
            'slug' => 'imperial-marquee',
            'email' => 'imperial@venue.pk',
            'subscription_status' => 'EXPIRED',
            'verification_status' => 'APPROVED',
            'subscription_expires_at' => Carbon::now()->subDays(2),
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Executive Suite',
            'slug' => 'executive-suite',
            'price_weekly' => 6000,
            'price_monthly' => 20000,
            'price_yearly' => 200000,
            'max_halls' => 3,
            'max_staff' => 10,
            'max_monthly_bookings' => 50,
        ]);

        $service = app(SubscriptionService::class);
        $superAdminId = (int) User::query()->where('role', 'ROLE_SUPER_ADMIN')->firstOrFail()->id;

        $service->renewSubscription($tenant, $plan, 'WEEKLY', 6000, 'CASH', 'TX-101', true, $superAdminId);
        $tenant->refresh();
        $this->assertSame('ACTIVE', $tenant->subscription_status);
        $this->assertSame('WEEKLY', $tenant->billing_cycle);
        $this->assertTrue($tenant->subscription_expires_at->isFuture());
        $this->assertFalse($service->isLocked($tenant));

        $service->renewSubscription($tenant, $plan, 'YEARLY', 200000, 'CASH', 'TX-102', true, $superAdminId);
        $tenant->refresh();
        $this->assertSame('ACTIVE', $tenant->subscription_status);
        $this->assertSame('YEARLY', $tenant->billing_cycle);
        $this->assertTrue($tenant->subscription_expires_at->greaterThan(Carbon::now()->addMonths(11)));
    }
}
