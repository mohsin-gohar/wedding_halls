<?php

namespace Tests\Feature;

use App\Livewire\WalkInBookingModal;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WalkInBookingAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_customer_cannot_forge_staff_mode_to_lookup_private_booking_contacts(): void
    {
        $booking = Booking::query()->whereNotNull('customer_phone')->firstOrFail();
        $customer = User::factory()->create([
            'role' => 'ROLE_CUSTOMER',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($customer)
            ->test(WalkInBookingModal::class)
            ->set('isStaff', true)
            ->set('customer_phone', $booking->customer_phone)
            ->call('lookupClient')
            ->assertSet('isStaff', false)
            ->assertSet('clientFound', false)
            ->assertSet('customer_name', '');
    }

    public function test_floor_staff_with_booking_permission_can_lookup_contacts_inside_their_tenant(): void
    {
        $booking = Booking::query()->whereNotNull('customer_phone')->firstOrFail();
        $floorStaff = User::factory()->create([
            'tenant_id' => $booking->tenant_id,
            'role' => 'ROLE_FLOOR_STAFF',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($floorStaff)
            ->test(WalkInBookingModal::class)
            ->set('customer_phone', $booking->customer_phone)
            ->call('lookupClient')
            ->assertSet('isStaff', true)
            ->assertSet('clientFound', true)
            ->assertSet('customer_name', $booking->customer_name);
    }
}
