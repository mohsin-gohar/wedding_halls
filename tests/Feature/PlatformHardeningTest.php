<?php

namespace Tests\Feature;

use App\Livewire\AdminDashboard;
use App\Livewire\Dashboard;
use App\Livewire\PublicAvailabilityChecker;
use App\Livewire\PublicHallDetails;
use App\Livewire\PublicVenues;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use App\Support\CalendarWindow;
use App\Support\PlatformMetrics;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformHardeningTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Hall $hall;

    private User $superAdmin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = SubscriptionPlan::create([
            'name' => 'Hardening Plan',
            'slug' => 'hardening-plan',
            'price_monthly' => 15000,
            'max_halls' => 5,
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Hardening Marquee',
            'slug' => 'hardening-marquee',
            'email' => 'hardening@example.com',
            'phone' => '+92 300 0000000',
            'city' => 'Lahore',
            'address' => 'Main Boulevard, Lahore',
            'verification_status' => 'APPROVED',
            'subscription_status' => 'ACTIVE',
            'subscription_plan_id' => $plan->id,
            'is_active' => true,
        ]);

        $venue = Venue::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Hardening Venue',
            'slug' => 'hardening-venue',
            'city' => 'Lahore',
            'address' => 'Main Boulevard, Lahore',
            'is_active' => true,
        ]);

        $this->hall = Hall::create([
            'tenant_id' => $this->tenant->id,
            'venue_id' => $venue->id,
            'name' => 'Hardening Ballroom',
            'slug' => 'hardening-ballroom',
            'min_capacity' => 100,
            'max_capacity' => 500,
            'base_rent' => 200000,
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Platform Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'ROLE_SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->customer = User::create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'ROLE_CUSTOMER',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_calendar_window_is_dynamic_and_rejects_invalid_dates(): void
    {
        Carbon::setTestNow('2030-06-15 10:00:00');

        try {
            $this->assertSame([2030, 2031, 2032, 2033, 2034, 2035], CalendarWindow::years());
            $this->assertSame(2030, CalendarWindow::clampYear(1999));
            $this->assertSame(2035, CalendarWindow::clampYear(2099));
            $this->assertSame('2030-06-01', CalendarWindow::firstNavigableMonth()->format('Y-m-d'));
            $this->assertSame('2035-12-31', CalendarWindow::lastMonth()->endOfMonth()->format('Y-m-d'));

            $this->assertNull(CalendarWindow::parseDate('2030-02-31'));
            $this->assertNull(CalendarWindow::parseDate('15-06-2030'));
            $this->assertSame('2030-06-15', CalendarWindow::parseDate('2030-06-15')?->format('Y-m-d'));
            $this->assertTrue(CalendarWindow::contains(Carbon::today()));
            $this->assertFalse(CalendarWindow::contains(Carbon::yesterday()));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_public_venues_calendar_uses_the_dynamic_window_and_blocks_past_dates(): void
    {
        Carbon::setTestNow('2030-06-15 10:00:00');

        try {
            $calendar = Livewire::test(PublicVenues::class)
                ->assertSet('allowedYears', [2030, 2031, 2032, 2033, 2034, 2035])
                ->assertSet('selectedYear', 2030)
                ->assertSet('selectedMonthNumber', 6)
                ->assertSet('selectedCalendarDate', '2030-06-15')
                ->assertSee('2030-2035');

            // A tampered year list is rebuilt from the runtime window so the
            // Blade min()/max() labels can never receive an empty array.
            $calendar->set('allowedYears', [])->call('$refresh')
                ->assertSet('allowedYears', [2030, 2031, 2032, 2033, 2034, 2035]);

            // The previous arrow must stop at the current month, not January.
            $calendar->call('previousMonth')
                ->assertSet('selectedYear', 2030)
                ->assertSet('selectedMonthNumber', 6)
                ->assertSet('selectedCalendarDate', '2030-06-15');

            // Past and malformed dates must not replace the current selection.
            $calendar->call('selectCalendarDate', '2030-06-14')
                ->assertSet('selectedCalendarDate', '2030-06-15')
                ->call('selectCalendarDate', 'not-a-date')
                ->assertSet('selectedCalendarDate', '2030-06-15');

            // A future date within the displayed month is accepted.
            $calendar->call('selectCalendarDate', '2030-06-20')
                ->assertSet('selectedCalendarDate', '2030-06-20')
                ->call('jumpToYear', 2040)
                ->assertSet('selectedYear', 2030);

            // A past date injected directly into the component is normalised
            // back to today before it can be used for a booking.
            $calendar->set('selectedCalendarDate', '2030-06-14')
                ->call('triggerBooking', 'EVENING', false)
                ->assertDispatched('openWalkInModal', fn (string $event, array $params): bool => ($params[0]['event_date'] ?? null) === '2030-06-15')
                ->assertNotDispatched('luxury-alert');

            // A valid future date with one hall in scope opens the modal.
            $calendar->call('selectCalendarDate', '2030-06-20')
                ->call('triggerBooking', 'EVENING', false)
                ->assertDispatched('openWalkInModal', fn (string $event, array $params): bool => ($params[0]['event_date'] ?? null) === '2030-06-20');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_availability_checker_clamps_dates_to_the_booking_horizon(): void
    {
        Carbon::setTestNow('2030-06-15 10:00:00');

        try {
            $checker = Livewire::test(PublicAvailabilityChecker::class)
                ->assertSet('selectedDate', '2030-06-16');

            $checker->call('setDate', '2030-06-14')
                ->assertSet('selectedDate', '2030-06-16')
                ->call('setDate', '2036-01-01')
                ->assertSet('selectedDate', '2030-06-16')
                ->call('setDate', '2030-07-04')
                ->assertSet('selectedDate', '2030-07-04')
                // A tampered date earlier in the current month must clamp to
                // today, not slip through because it shares the current month.
                ->set('selectedDate', '2030-06-14')
                ->assertSet('selectedDate', '2030-06-15')
                ->set('selectedDate', 'not-a-date')
                ->assertSet('selectedDate', '2030-06-16');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_hall_showcase_normalizes_a_tampered_calendar_month(): void
    {
        Carbon::setTestNow('2030-06-15 10:00:00');

        try {
            Livewire::test(PublicHallDetails::class, ['slug' => $this->hall->slug])
                ->set('calendarMonth', 'not-a-month')
                ->call('setSelectedDate', '2030-06-20')
                ->assertSet('selectedDate', '2030-06-20')
                ->assertSet('calendarMonth', '2030-06');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_public_rate_limiters_are_registered_with_expected_caps(): void
    {
        $pages = RateLimiter::limiter('public-pages');
        $availability = RateLimiter::limiter('public-availability');

        $this->assertNotNull($pages);
        $this->assertNotNull($availability);

        $request = Request::create('/venues');

        $this->assertSame(120, $pages($request)->maxAttempts);
        $this->assertSame(60, $availability($request)->maxAttempts);
    }

    public function test_admin_booking_status_change_is_audited_and_invalid_status_is_rejected(): void
    {
        $booking = $this->createBooking('HOLD');
        $versionBefore = PlatformMetrics::version();

        $component = Livewire::actingAs($this->superAdmin)
            ->test(AdminDashboard::class);

        $component->call('updateBookingStatus', $booking->id, 'CONFIRMED')
            ->assertSet('feedbackMessage', fn ($message) => is_string($message) && str_contains($message, 'CONFIRMED'));

        $this->assertSame('CONFIRMED', $booking->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'BOOKING_STATUS_UPDATED',
            'entity_id' => $booking->id,
        ]);
        $this->assertGreaterThan($versionBefore, PlatformMetrics::version());

        $component->call('updateBookingStatus', $booking->id, 'NOT_A_REAL_STATUS');

        $this->assertSame('Invalid booking status supplied.', $component->get('errorMessage'));
        $this->assertSame('CONFIRMED', $booking->fresh()->status);
        $this->assertSame(1, AuditLog::where('action', 'BOOKING_STATUS_UPDATED')->count());
    }

    public function test_subscription_payment_cannot_be_approved_twice(): void
    {
        $payment = SubscriptionPayment::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => SubscriptionPlan::first()->id,
            'billing_cycle' => 'MONTHLY',
            'amount' => 15000,
            'payment_method' => 'CASH',
            'transaction_reference' => 'TEST-DOUBLE-APPROVAL',
            'status' => 'PENDING',
        ]);

        $component = Livewire::actingAs($this->superAdmin)
            ->test(AdminDashboard::class);

        $component->call('approveSubscriptionPayment', $payment->id);

        $expiryAfterFirstApproval = $this->tenant->fresh()->subscription_expires_at->copy();

        $component->call('approveSubscriptionPayment', $payment->id);

        $this->assertSame('This subscription payment has already been processed.', $component->get('errorMessage'));
        $this->assertTrue($expiryAfterFirstApproval->equalTo($this->tenant->fresh()->subscription_expires_at));
        $this->assertSame(1, AuditLog::where('action', 'SUBSCRIPTION_PAYMENT_APPROVED')->count());
    }

    public function test_deleting_a_hall_with_active_bookings_is_blocked(): void
    {
        $booking = $this->createBooking('CONFIRMED');

        $component = Livewire::actingAs($this->superAdmin)
            ->test(AdminDashboard::class)
            ->call('deleteHall', $this->hall->id);

        $this->assertStringContainsString('active or confirmed bookings', $component->get('errorMessage'));
        $this->assertDatabaseHas('halls', ['id' => $this->hall->id]);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'HALL_DELETED']);
    }

    public function test_deleting_a_hall_without_active_bookings_is_audited(): void
    {
        $booking = $this->createBooking('CANCELLED');

        Livewire::actingAs($this->superAdmin)
            ->test(AdminDashboard::class)
            ->call('deleteHall', $this->hall->id);

        $this->assertDatabaseMissing('halls', ['id' => $this->hall->id]);
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'HALL_DELETED']);
    }

    public function test_platform_metrics_snapshot_is_invalidated_by_audit_events(): void
    {
        Cache::flush();

        $before = PlatformMetrics::snapshot();
        $this->assertSame(1, $before['totalTenants']);

        Tenant::create([
            'name' => 'Second Marquee',
            'slug' => 'second-marquee',
            'email' => 'second@example.com',
            'phone' => '+92 300 1111111',
            'city' => 'Karachi',
            'address' => 'Clifton, Karachi',
            'verification_status' => 'APPROVED',
            'subscription_status' => 'ACTIVE',
            'is_active' => true,
        ]);

        // The cached snapshot is still served until an audited mutation flushes it.
        $this->assertSame(1, PlatformMetrics::snapshot()['totalTenants']);

        AuditLog::log('TEST_METRIC_FLUSH', 'Verifying platform metrics invalidation.');

        $this->assertSame(2, PlatformMetrics::snapshot()['totalTenants']);
        $this->assertSame(2, PlatformMetrics::version());
    }

    public function test_non_super_admin_cannot_render_the_admin_console(): void
    {
        $this->actingAs($this->customer);

        $this->assertSame('ROLE_CUSTOMER', auth()->user()->role);

        Livewire::test(AdminDashboard::class)
            ->assertStatus(403);
    }

    public function test_homepage_search_filters_capacity_and_books_the_requested_date(): void
    {
        Carbon::setTestNow('2026-10-29 10:00:00');

        try {
            $intimate = Hall::create([
                'tenant_id' => $this->tenant->id,
                'venue_id' => $this->hall->venue_id,
                'name' => 'Intimate Lawn',
                'slug' => 'intimate-lawn',
                'min_capacity' => 20,
                'max_capacity' => 80,
                'base_rent' => 50000,
                'is_active' => true,
            ]);

            Livewire::withQueryParams([
                'city' => 'Lahore',
                'guests' => '250',
                'date' => '2026-11-15',
                'shift' => 'EVENING',
            ])->test(PublicVenues::class)
                ->assertSee('Hardening Ballroom')
                ->assertDontSee('Intimate Lawn')
                ->assertSee('250+ guests', false)
                ->assertSet('selectedCalendarDate', '2026-11-15');

            Booking::create([
                'tenant_id' => $this->tenant->id,
                'hall_id' => $this->hall->id,
                'customer_name' => 'Booked Shift',
                'customer_phone' => '+92 300 1111111',
                'event_type' => 'Barat',
                'event_date' => '2026-11-15',
                'slot_type' => 'EVENING',
                'guest_count' => 300,
                'status' => 'CONFIRMED',
            ]);

            Livewire::withQueryParams([
                'city' => 'Lahore',
                'guests' => '250',
                'date' => '2026-11-15',
                'shift' => 'EVENING',
            ])->test(PublicVenues::class)
                ->assertDontSee('Hardening Ballroom')
                ->assertDontSee($intimate->name);

            Livewire::withQueryParams(['date' => '2026-11-15'])
                ->test(PublicHallDetails::class, ['slug' => $this->hall->slug])
                ->assertSet('selectedDate', '2026-11-15')
                ->assertSet('calendarMonth', '2026-11');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_billing_disabled_hides_saas_tabs_and_blocks_direct_plan_navigation(): void
    {
        config(['platform.billing_enabled' => false]);

        Livewire::actingAs($this->superAdmin)
            ->test(AdminDashboard::class)
            ->assertDontSee('SaaS Plans')
            ->assertDontSee('Payment Approvals')
            ->assertDontSee('New SaaS Plan')
            ->assertDontSee('Assigned SaaS Plan')
            ->call('setTab', 'plans')
            ->assertSet('activeTab', 'vendors')
            ->assertSet('feedbackMessage', 'SaaS billing is disabled during the free launch period — every hall ships at no charge.');
    }

    public function test_vendor_dashboard_shows_free_launch_access_and_hides_kitchen_module(): void
    {
        config([
            'platform.billing_enabled' => false,
            'platform.kitchen_module_enabled' => false,
            'platform.free_access_days' => 30,
        ]);

        $owner = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Hardening Owner',
            'email' => 'hardening-owner@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'ROLE_TENANT_OWNER',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($owner)
            ->test(Dashboard::class)
            ->assertSee('Free Launch Access')
            ->assertSee('30 Days Complimentary')
            ->assertSee('Traffic Pulse')
            ->assertDontSee('Current SaaS Plan')
            ->assertDontSee('Kitchen KOTs')
            ->assertDontSee('Chef KOT Board');

        $this->get('/services')
            ->assertOk()
            ->assertDontSee('Kitchen KOT Order Slips');
    }

    private function createBooking(string $status): Booking
    {
        return Booking::create([
            'tenant_id' => $this->tenant->id,
            'hall_id' => $this->hall->id,
            'customer_name' => 'Booking Guard Test',
            'customer_phone' => '+92 300 1234567',
            'event_type' => 'Barat',
            'event_date' => now()->addDays(10)->format('Y-m-d'),
            'slot_type' => 'EVENING',
            'guest_count' => 250,
            'per_head_rate' => 2000,
            'hall_rent' => 200000,
            'status' => $status,
        ]);
    }
}
