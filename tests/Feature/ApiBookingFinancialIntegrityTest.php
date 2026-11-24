<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiBookingFinancialIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_client_cannot_control_booking_money_status_or_references(): void
    {
        $hall = Hall::query()->firstOrFail();
        $owner = User::query()
            ->where('tenant_id', $hall->tenant_id)
            ->where('role', 'ROLE_TENANT_OWNER')
            ->firstOrFail();
        $owner->assignRole('ROLE_TENANT_OWNER');

        $response = $this->actingAs($owner->fresh())->postJson('/api/v1/bookings', [
            'hall_id' => $hall->id,
            'event_type' => 'Barat',
            'event_date' => now()->addDays(120)->toDateString(),
            'slot_type' => 'EVENING',
            'guest_count' => 300,
            'customer_name' => 'Integrity Test Client',
            'customer_phone' => '+92 300 4445566',
            'per_head_rate' => 0,
            'hall_rent' => 0,
            'addons_total' => 0,
            'discount_amount' => 999999,
            'tax_amount' => 0,
            'advance_paid' => 999999,
            'payment_method' => 'CASH',
            'transaction_reference' => 'FAKE-ADVANCE-REF',
            'booking_reference' => 'HACKED-REFERENCE',
            'status' => 'CONFIRMED',
            'pipeline_stage' => 'CONFIRMED',
        ]);

        $response->assertCreated()->assertJsonPath('success', true);

        $booking = Booking::query()->where('customer_name', 'Integrity Test Client')->firstOrFail();

        // Money is derived from the hall (no package supplied), never from
        // the caller's zeroed rates or fabricated discount/advance.
        $this->assertEquals((float) $hall->base_rent, (float) $booking->grand_total);
        $this->assertEquals(0.0, (float) $booking->discount_amount);
        $this->assertEquals(0.0, (float) $booking->tax_amount);
        $this->assertEquals(0.0, (float) $booking->advance_paid);
        $this->assertEquals((float) $hall->base_rent, (float) $booking->balance_due);

        // Lifecycle and identifiers stay server-controlled.
        $this->assertSame('INQUIRY', $booking->status);
        $this->assertSame('NEW_INQUIRY', $booking->pipeline_stage);
        $this->assertNotSame('HACKED-REFERENCE', $booking->booking_reference);
        $this->assertSame((string) $hall->tenant_id, (string) $booking->tenant_id);
        $this->assertSame(0, Payment::query()->where('booking_id', $booking->id)->count());
    }

    public function test_expired_tenant_cannot_accept_api_bookings_or_availability_checks(): void
    {
        $hall = Hall::query()->firstOrFail();
        $tenant = Tenant::query()->findOrFail($hall->tenant_id);
        $tenant->update([
            'subscription_status' => 'EXPIRED',
            'subscription_expires_at' => now()->addYear(),
        ]);
        $owner = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('role', 'ROLE_TENANT_OWNER')
            ->firstOrFail();
        $owner->assignRole('ROLE_TENANT_OWNER');

        $this->actingAs($owner->fresh())->postJson('/api/v1/bookings', [
            'hall_id' => $hall->id,
            'event_type' => 'Barat',
            'event_date' => now()->addDays(140)->toDateString(),
            'slot_type' => 'EVENING',
            'guest_count' => 200,
            'customer_name' => 'Expired Tenant Guest',
            'customer_phone' => '+92 300 4445599',
        ])->assertForbidden();

        $this->actingAs($owner->fresh())->getJson('/api/v1/bookings/availability?hall_id='.$hall->id.'&date='.now()->addDays(140)->toDateString())
            ->assertForbidden();

        $this->assertSame(0, Booking::query()->where('customer_name', 'Expired Tenant Guest')->count());
    }

    public function test_client_package_from_another_tenant_is_rejected(): void
    {
        $hall = Hall::query()->firstOrFail();
        $owner = User::query()
            ->where('tenant_id', $hall->tenant_id)
            ->where('role', 'ROLE_TENANT_OWNER')
            ->firstOrFail();
        $owner->assignRole('ROLE_TENANT_OWNER');

        $foreignTenant = Tenant::create([
            'name' => 'Foreign Package Tenant',
            'slug' => 'foreign-package-tenant-'.uniqid(),
            'email' => 'foreign@riwaj.pk',
            'phone' => '+92 300 9990011',
            'city' => 'Karachi',
            'subscription_plan' => 'STARTER',
            'currency' => 'PKR',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);

        $foreignPackage = Package::create([
            'tenant_id' => $foreignTenant->id,
            'name' => 'Foreign Catering Package',
            'per_head_price' => 5000,
            'min_guests' => 50,
            'is_active' => true,
        ]);

        $this->actingAs($owner->fresh())->postJson('/api/v1/bookings', [
            'hall_id' => $hall->id,
            'event_type' => 'Barat',
            'event_date' => now()->addDays(130)->toDateString(),
            'slot_type' => 'AFTERNOON',
            'guest_count' => 200,
            'customer_name' => 'Foreign Package Test',
            'customer_phone' => '+92 300 4445577',
            'package_id' => $foreignPackage->id,
        ])->assertStatus(422)->assertJsonValidationErrors('package_id');

        $this->assertSame(0, Booking::query()->where('customer_name', 'Foreign Package Test')->count());
    }
}
