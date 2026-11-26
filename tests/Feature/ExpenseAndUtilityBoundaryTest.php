<?php

namespace Tests\Feature;

use App\Livewire\ExpenseTracker;
use App\Livewire\UtilityFuelTracker;
use App\Models\Hall;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseAndUtilityBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function ownerForTenant(string $tenantId): User
    {
        return User::query()
            ->where('tenant_id', $tenantId)
            ->where('role', 'ROLE_TENANT_OWNER')
            ->firstOrFail();
    }

    public function test_expense_amount_cannot_overflow_decimal_storage(): void
    {
        $hall = Hall::query()->firstOrFail();
        $owner = $this->ownerForTenant($hall->tenant_id);

        Livewire::actingAs($owner)
            ->test(ExpenseTracker::class)
            ->set('category', 'OTHER')
            ->set('title', 'Overflow expense')
            ->set('amount', '10000000000')
            ->set('expense_date', now()->toDateString())
            ->call('createExpense')
            ->assertHasErrors(['amount']);

        $this->assertDatabaseMissing('expenses', [
            'tenant_id' => $owner->tenant_id,
            'title' => 'Overflow expense',
        ]);
    }

    public function test_utility_cost_cannot_overflow_decimal_storage(): void
    {
        $hall = Hall::query()->firstOrFail();
        $owner = $this->ownerForTenant($hall->tenant_id);
        $booking = app(BookingService::class)->createReservation([
            'hall_id' => $hall->id,
            'event_date' => now()->addDays(290)->toDateString(),
            'slot_type' => 'EVENING',
            'event_type' => 'Walima',
            'customer_name' => 'Utility Boundary Event',
            'customer_phone' => '+92 300 7774030',
            'guest_count' => 120,
            'per_head_rate' => 1200,
            'hall_rent' => 90000,
            'advance_paid' => 0,
            'status' => 'CONFIRMED',
        ]);

        Livewire::actingAs($owner)
            ->test(UtilityFuelTracker::class)
            ->set('selectedBookingId', $booking->id)
            ->set('generator_run_hours', 3)
            ->set('diesel_liters_consumed', 100000)
            ->set('diesel_rate_per_liter', 1000)
            ->set('wapda_units_consumed', 0)
            ->set('wapda_unit_rate', 0)
            ->call('saveUtilityLog')
            ->assertHasErrors(['diesel_liters_consumed']);

        $this->assertDatabaseMissing('utility_logs', [
            'booking_id' => $booking->id,
        ]);
    }
}
