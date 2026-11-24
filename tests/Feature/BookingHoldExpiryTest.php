<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\Tenant;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingHoldExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Carbon::setTestNow('2026-11-24 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_expired_hold_is_cancelled_and_audited_exactly_once(): void
    {
        $booking = $this->createBooking('HOLD', Carbon::now()->subMinute());

        $this->artisan('bookings:expire-holds')
            ->expectsOutputToContain('Expired holds cancelled: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'CANCELLED',
        ]);
        $this->assertSame(1, AuditLog::query()->where('action', 'BOOKING_HOLD_EXPIRED')->count());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'BOOKING_HOLD_EXPIRED',
            'entity_type' => 'Booking',
            'entity_id' => (string) $booking->id,
        ]);

        $this->artisan('bookings:expire-holds')
            ->expectsOutputToContain('Expired holds cancelled: 0')
            ->assertExitCode(0);

        $this->assertSame(1, AuditLog::query()->where('action', 'BOOKING_HOLD_EXPIRED')->count());
    }

    public function test_confirmed_bookings_and_holds_without_expiry_are_untouched(): void
    {
        $confirmed = $this->createBooking('CONFIRMED', Carbon::now()->subDay());
        $noExpiry = $this->createBooking('HOLD', null);

        $this->artisan('bookings:expire-holds')
            ->expectsOutputToContain('Expired holds cancelled: 0')
            ->assertExitCode(0);

        $this->assertDatabaseHas('bookings', [
            'id' => $confirmed->id,
            'status' => 'CONFIRMED',
        ]);
        $this->assertDatabaseHas('bookings', [
            'id' => $noExpiry->id,
            'status' => 'HOLD',
            'hold_expires_at' => null,
        ]);
        $this->assertSame(0, AuditLog::query()->where('action', 'BOOKING_HOLD_EXPIRED')->count());
    }

    private function createBooking(string $status, ?Carbon $holdExpiresAt): Booking
    {
        $tenant = Tenant::create([
            'name' => 'Hold Test Venue',
            'slug' => 'hold-test-'.strtolower($status).'-'.uniqid(),
            'email' => 'hold-'.uniqid().'@example.test',
            'subscription_status' => 'ACTIVE',
            'verification_status' => 'APPROVED',
            'subscription_expires_at' => Carbon::now()->addMonth(),
            'is_active' => true,
        ]);

        $venue = Venue::create([
            'tenant_id' => $tenant->id,
            'name' => 'Hold Test Complex',
            'slug' => 'hold-test-complex-'.uniqid(),
            'city' => 'Karachi',
        ]);

        $hall = Hall::create([
            'tenant_id' => $tenant->id,
            'venue_id' => $venue->id,
            'name' => 'Hold Test Hall',
            'base_rent' => 100000,
        ]);

        return Booking::create([
            'tenant_id' => $tenant->id,
            'hall_id' => $hall->id,
            'customer_name' => 'Test Customer',
            'customer_phone' => '03001234567',
            'event_date' => Carbon::today()->addDays(10)->format('Y-m-d'),
            'slot_type' => 'EVENING',
            'status' => $status,
            'hold_expires_at' => $holdExpiresAt,
            'grand_total' => 100000,
        ]);
    }
}
