<?php

namespace Tests\Feature;

use App\Livewire\UtilityFuelTracker;
use App\Models\Booking;
use App\Models\Expense;
use App\Models\Hall;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityLog;
use App\Models\Venue;
use App\Services\ExpenseCorrectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UtilityExpenseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_utility_save_creates_one_linked_expense_and_updates_it_on_resave(): void
    {
        $tenant = Tenant::create([
            'name' => 'Utility Test Group', 'slug' => 'utility-test-group',
            'subdomain' => 'utility-test-group', 'is_active' => true,
            'verification_status' => 'APPROVED',
        ]);
        $owner = User::factory()->create([
            'tenant_id' => $tenant->id, 'role' => 'ROLE_TENANT_OWNER', 'status' => 'ACTIVE',
        ]);
        $venue = Venue::create([
            'tenant_id' => $tenant->id, 'name' => 'Utility Venue',
            'slug' => 'utility-venue', 'city' => 'Lahore', 'is_active' => true,
        ]);
        $hall = Hall::create([
            'tenant_id' => $tenant->id, 'venue_id' => $venue->id, 'name' => 'Utility Hall',
            'slug' => 'utility-hall', 'min_capacity' => 50, 'max_capacity' => 500,
            'base_rent' => 100000, 'is_active' => true,
        ]);
        $booking = Booking::create([
            'tenant_id' => $tenant->id, 'hall_id' => $hall->id,
            'customer_name' => 'Utility Host', 'customer_phone' => '+92 300 1111111',
            'event_date' => now()->addDays(10)->toDateString(), 'slot_type' => 'EVENING',
            'guest_count' => 200, 'status' => 'CONFIRMED',
        ]);

        Livewire::actingAs($owner)
            ->test(UtilityFuelTracker::class)
            ->set('selectedBookingId', $booking->id)
            ->set('diesel_liters_consumed', 40)
            ->set('diesel_rate_per_liter', 300)
            ->set('wapda_units_consumed', 0)
            ->set('wapda_unit_rate', 0)
            ->set('generator_run_hours', 5)
            ->call('saveUtilityLog')
            ->assertHasNoErrors();

        $log = UtilityLog::where('booking_id', $booking->id)->firstOrFail();
        $this->assertNotNull($log->expense_id);
        $this->assertSame(12000.0, (float) $log->expense->amount);
        $this->assertSame(1, Expense::where('tenant_id', $tenant->id)->count());

        // A changed cost must not silently rewrite the posted expense.
        Livewire::actingAs($owner)
            ->test(UtilityFuelTracker::class)
            ->set('selectedBookingId', $booking->id)
            ->set('diesel_liters_consumed', 50)
            ->set('diesel_rate_per_liter', 300)
            ->set('wapda_units_consumed', 0)
            ->set('wapda_unit_rate', 0)
            ->set('generator_run_hours', 6)
            ->call('saveUtilityLog')
            ->assertHasErrors('diesel_liters_consumed');

        $this->assertSame(1, UtilityLog::where('booking_id', $booking->id)->count());
        $this->assertSame(1, Expense::where('tenant_id', $tenant->id)->count());
        $this->assertSame(12000.0, (float) $log->fresh()->expense->amount);

        // The audited correction path appends a signed row instead.
        $correction = app(ExpenseCorrectionService::class)->correct(
            $log->expense, 15000.0, 'Correct recorded fuel quantity', 'CORR-UTILITY-001', 'UTILITY', (string) $log->id
        );
        $this->assertSame('corrected', $correction['result']);
        $this->assertSame(12000.0, (float) $log->fresh()->expense->amount);
        $this->assertSame(1, Expense::where('tenant_id', $tenant->id)->whereNotNull('corrects_expense_id')->count());
        $this->assertSame(15000.0, (float) $log->fresh()->expense->corrections()->sum('amount') + 12000.0);
    }
}
