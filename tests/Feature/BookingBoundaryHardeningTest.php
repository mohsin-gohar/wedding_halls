<?php

namespace Tests\Feature;

use App\Models\Hall;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regressions for the BookingService write boundary.
 *
 * The web and API layers already derive prices from authoritative records, but
 * the shared service must also fail closed when a caller supplies forged
 * references, identities, lifecycle states or money values.
 */
class BookingBoundaryHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Hall $hall, array $overrides = []): array
    {
        return array_merge([
            'hall_id' => $hall->id,
            'event_date' => now()->addDays(90)->toDateString(),
            'slot_type' => 'EVENING',
            'event_type' => 'Barat',
            'customer_name' => 'Boundary Guest',
            'customer_phone' => '+92 300 9990000',
            'guest_count' => 200,
            'per_head_rate' => 1000,
            'hall_rent' => 100000,
            'advance_paid' => 0,
            'payment_method' => 'CASH',
        ], $overrides);
    }

    public function test_expired_tenant_is_rejected_at_the_booking_service_boundary(): void
    {
        $hall = Hall::query()->firstOrFail();
        $tenant = Tenant::query()->findOrFail($hall->tenant_id);
        $tenant->update([
            'subscription_status' => 'EXPIRED',
            'subscription_expires_at' => now()->addYear(),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Venue is not accepting bookings.');

        app(BookingService::class)->createReservation($this->payload($hall));
    }

    public function test_caller_supplied_booking_reference_is_never_trusted(): void
    {
        $hall = Hall::query()->firstOrFail();

        $booking = app(BookingService::class)->createReservation($this->payload($hall, [
            'booking_reference' => 'FORGED-REFERENCE-123',
        ]));

        $this->assertNotSame('FORGED-REFERENCE-123', $booking->booking_reference);
        $this->assertDatabaseMissing('bookings', ['booking_reference' => 'FORGED-REFERENCE-123']);
    }

    public function test_customer_cannot_book_on_behalf_of_another_customer(): void
    {
        $hall = Hall::query()->firstOrFail();
        $customer = User::query()->where('role', 'ROLE_CUSTOMER')->firstOrFail();
        $other = User::factory()->create(['role' => 'ROLE_CUSTOMER', 'status' => 'ACTIVE']);

        $this->actingAs($customer);

        $this->expectException(\InvalidArgumentException::class);

        app(BookingService::class)->createReservation($this->payload($hall, [
            'customer_id' => $other->id,
        ]));
    }

    public function test_advance_paid_cannot_exceed_the_authoritative_total(): void
    {
        $hall = Hall::query()->firstOrFail();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cash advance cannot exceed the booking total');

        // 200 guests x 1000 + 100000 rent = 300000 total.
        app(BookingService::class)->createReservation($this->payload($hall, [
            'advance_paid' => 300001,
        ]));
    }

    public function test_negative_financial_values_are_rejected(): void
    {
        $hall = Hall::query()->firstOrFail();

        $this->expectException(\InvalidArgumentException::class);

        app(BookingService::class)->createReservation($this->payload($hall, [
            'discount_amount' => -5000,
        ]));
    }

    public function test_creation_only_lifecycle_states_are_accepted(): void
    {
        $hall = Hall::query()->firstOrFail();

        $this->expectException(\InvalidArgumentException::class);

        app(BookingService::class)->createReservation($this->payload($hall, [
            'status' => 'COMPLETED',
        ]));
    }

    public function test_package_from_another_tenant_is_rejected(): void
    {
        $hall = Hall::query()->firstOrFail();

        $foreignTenant = Tenant::create([
            'name' => 'Foreign Venue Group',
            'slug' => 'foreign-venue-'.uniqid(),
            'email' => 'foreign@example.pk',
            'verification_status' => 'APPROVED',
            'subscription_status' => 'ACTIVE',
            'is_active' => true,
        ]);

        $foreignPackage = Package::create([
            'tenant_id' => $foreignTenant->id,
            'name' => 'Foreign Package',
            'per_head_price' => 500,
            'is_active' => true,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not available for this venue');

        app(BookingService::class)->createReservation($this->payload($hall, [
            'package_id' => $foreignPackage->id,
        ]));
    }

    public function test_production_template_and_config_use_a_shared_reservation_lock_store(): void
    {
        $template = (string) file_get_contents(base_path('.env.production.example'));

        $this->assertStringContainsString('CACHE_STORE=database', $template);
        $this->assertStringContainsString('BOOKING_LOCK_STORE=database', $template);
        $this->assertStringNotContainsString('CACHE_STORE=file', $template);
        $this->assertStringNotContainsString('CACHE_STORE=array', $template);
        $this->assertNotNull(config('booking.lock_store'));
    }

    public function test_milestone_payment_uses_the_authoritative_cash_ledger(): void
    {
        $hall = Hall::query()->firstOrFail();
        $owner = User::query()->where('role', 'ROLE_TENANT_OWNER')->firstOrFail();

        $this->actingAs($owner);

        $booking = app(BookingService::class)->createReservation($this->payload($hall));

        $payment = app(BookingService::class)->recordMilestonePayment($booking, [
            'amount' => 5000,
            'payment_type' => 'SECOND_INSTALLMENT',
        ]);

        $this->assertSame('CASH', $payment->payment_method);
        $this->assertSame('5000.00', (string) $payment->amount);
        $this->assertSame('5000.00', (string) $booking->fresh()->advance_paid);
    }

    public function test_milestone_payment_rejects_customer_actors_and_overpayment(): void
    {
        $hall = Hall::query()->firstOrFail();
        $owner = User::query()->where('role', 'ROLE_TENANT_OWNER')->firstOrFail();
        $customer = User::query()->where('role', 'ROLE_CUSTOMER')->firstOrFail();

        $this->actingAs($owner);
        $booking = app(BookingService::class)->createReservation($this->payload($hall));

        $this->actingAs($customer);

        try {
            app(BookingService::class)->recordMilestonePayment($booking, ['amount' => 1000]);
            $this->fail('A customer must not be able to record a cash payment.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('authorized staff', $exception->getMessage());
        }

        $this->actingAs($owner);

        try {
            app(BookingService::class)->recordMilestonePayment($booking, ['amount' => 300001]);
            $this->fail('A milestone payment must not exceed the outstanding balance.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('outstanding balance', $exception->getMessage());
        }

        $this->assertSame('0.00', (string) $booking->fresh()->advance_paid);
    }

    public function test_inactive_hall_cannot_accept_a_new_reservation(): void
    {
        $hall = Hall::query()->firstOrFail();
        $hall->update(['is_active' => false]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Venue is not accepting bookings.');

        app(BookingService::class)->createReservation($this->payload($hall));
    }

    public function test_hall_with_inactive_venue_cannot_accept_a_new_reservation(): void
    {
        $hall = Hall::query()->firstOrFail();
        $hall->venue()->update(['is_active' => false]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Venue is not accepting bookings.');

        app(BookingService::class)->createReservation($this->payload($hall));
    }
}
