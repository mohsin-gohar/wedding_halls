<?php

namespace Tests\Feature;

use App\Models\AuditLog;
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

    public function test_grace_period_transition_is_idempotent_then_expires_after_three_days(): void
    {
        Carbon::setTestNow('2026-11-24 12:00:00');

        try {
            $tenant = Tenant::create([
                'name' => 'Grace Period Venue',
                'slug' => 'grace-period-venue',
                'email' => 'grace-period@example.test',
                'subscription_status' => 'ACTIVE',
                'verification_status' => 'APPROVED',
                'subscription_expires_at' => Carbon::now()->subDay(),
                'is_active' => true,
            ]);

            $service = app(SubscriptionService::class);
            $firstPass = $service->checkAndExpireSubscriptions();

            $this->assertSame(1, $firstPass['warning_count']);
            $this->assertSame('GRACE_PERIOD', $tenant->fresh()->subscription_status);

            $secondPass = $service->checkAndExpireSubscriptions();
            $this->assertSame(0, $secondPass['warning_count']);
            $this->assertSame(
                1,
                AuditLog::query()
                    ->where('action', 'SUBSCRIPTION_GRACE_PERIOD')
                    ->where('entity_id', (string) $tenant->id)
                    ->count()
            );

            Carbon::setTestNow('2026-11-28 12:00:00');
            $finalPass = $service->checkAndExpireSubscriptions();

            $this->assertSame(1, $finalPass['expired_count']);
            $this->assertSame('EXPIRED', $tenant->fresh()->subscription_status);
            $this->assertSame(
                1,
                AuditLog::query()
                    ->where('action', 'SUBSCRIPTION_EXPIRED')
                    ->where('entity_id', (string) $tenant->id)
                    ->count()
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_trial_tenants_without_paid_expiry_are_expired_at_trial_end(): void
    {
        $expiredTrial = Tenant::create([
            'name' => 'Expired Trial Venue',
            'slug' => 'expired-trial-venue',
            'email' => 'expired-trial@example.test',
            'subscription_status' => 'TRIAL',
            'verification_status' => 'APPROVED',
            'subscription_expires_at' => null,
            'trial_ends_at' => Carbon::now()->subMinute(),
            'is_active' => true,
        ]);

        $activeTrial = Tenant::create([
            'name' => 'Active Trial Venue',
            'slug' => 'active-trial-venue',
            'email' => 'active-trial@example.test',
            'subscription_status' => 'TRIAL',
            'verification_status' => 'APPROVED',
            'subscription_expires_at' => null,
            'trial_ends_at' => Carbon::now()->addDay(),
            'is_active' => true,
        ]);

        $results = app(SubscriptionService::class)->checkAndExpireSubscriptions();

        $this->assertSame(1, $results['expired_count']);
        $this->assertSame(0, $results['warning_count']);
        $this->assertSame('EXPIRED', $expiredTrial->fresh()->subscription_status);
        $this->assertSame('TRIAL', $activeTrial->fresh()->subscription_status);
        $this->assertSame(
            1,
            AuditLog::query()
                ->where('action', 'SUBSCRIPTION_EXPIRED')
                ->where('entity_id', (string) $expiredTrial->id)
                ->count()
        );
    }

    public function test_public_visibility_respects_paid_grace_and_trial_entitlement_boundaries(): void
    {
        Carbon::setTestNow('2026-11-24 12:00:00');

        try {
            $active = Tenant::create([
                'name' => 'Current Subscription',
                'slug' => 'current-subscription',
                'email' => 'current-subscription@example.test',
                'subscription_status' => 'ACTIVE',
                'subscription_expires_at' => Carbon::now()->addDay(),
                'verification_status' => 'APPROVED',
                'is_active' => true,
            ]);
            $grace = Tenant::create([
                'name' => 'Grace Subscription',
                'slug' => 'grace-subscription',
                'email' => 'grace-subscription@example.test',
                'subscription_status' => 'GRACE_PERIOD',
                'subscription_expires_at' => Carbon::now()->subDay(),
                'verification_status' => 'APPROVED',
                'is_active' => true,
            ]);
            $pastGrace = Tenant::create([
                'name' => 'Past Grace Subscription',
                'slug' => 'past-grace-subscription',
                'email' => 'past-grace-subscription@example.test',
                'subscription_status' => 'ACTIVE',
                'subscription_expires_at' => Carbon::now()->subDays(4),
                'verification_status' => 'APPROVED',
                'is_active' => true,
            ]);
            $expiredTrial = Tenant::create([
                'name' => 'Expired Trial Visibility',
                'slug' => 'expired-trial-visibility',
                'email' => 'expired-trial-visibility@example.test',
                'subscription_status' => 'TRIAL',
                'subscription_expires_at' => null,
                'trial_ends_at' => Carbon::now()->subMinute(),
                'verification_status' => 'APPROVED',
                'is_active' => true,
            ]);
            $expired = Tenant::create([
                'name' => 'Expired Subscription',
                'slug' => 'expired-subscription',
                'email' => 'expired-subscription@example.test',
                'subscription_status' => 'EXPIRED',
                'subscription_expires_at' => Carbon::now()->addYear(),
                'verification_status' => 'APPROVED',
                'is_active' => true,
            ]);

            $visibleIds = Tenant::query()->publicVisible()->pluck('id')->all();

            $this->assertContains($active->id, $visibleIds);
            $this->assertContains($grace->id, $visibleIds);
            $this->assertNotContains($pastGrace->id, $visibleIds);
            $this->assertNotContains($expiredTrial->id, $visibleIds);
            $this->assertNotContains($expired->id, $visibleIds);
            $this->assertFalse($pastGrace->canAcceptBookings());
            $this->assertFalse($expiredTrial->canAcceptBookings());
        } finally {
            Carbon::setTestNow();
        }
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
