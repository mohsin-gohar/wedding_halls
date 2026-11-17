<?php

namespace Tests\Feature;

use App\Livewire\CalendarShiftGrid;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Booking calendar integrity (Phase 1 hardening):
 *  - overlapping blocking bookings are surfaced, never silently hidden
 *  - status labels are honest (INQUIRY is non-blocking; no fake 24H hold)
 *  - tenant/hall scoping remains intact
 */
class CalendarIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function hall(): Hall
    {
        return Hall::where('is_active', true)->firstOrFail();
    }

    private function owner(Hall $hall): User
    {
        return User::where('tenant_id', $hall->tenant_id)
            ->where('role', 'ROLE_TENANT_OWNER')
            ->firstOrFail();
    }

    private function bookingFor(Hall $hall, string $date, string $status, string $customerName): Booking
    {
        return Booking::create([
            'tenant_id' => $hall->tenant_id,
            'hall_id' => $hall->id,
            'customer_name' => $customerName,
            'customer_phone' => '+92 300 5551234',
            'event_date' => $date,
            'slot_type' => 'EVENING',
            'event_type' => 'Barat',
            'guest_count' => 200,
            'status' => $status,
        ]);
    }

    public function test_overlapping_blocking_bookings_are_surfaced_as_a_warning(): void
    {
        $hall = $this->hall();
        $date = Carbon::now()->startOfMonth()->addDays(5)->format('Y-m-d');

        // Created directly to simulate legacy data-integrity corruption that
        // bypassed BookingService's lock. The calendar must NOT hide it.
        $this->bookingFor($hall, $date, 'CONFIRMED', 'First Customer');
        $this->bookingFor($hall, $date, 'HOLD', 'Second Customer');

        Livewire::actingAs($this->owner($hall))
            ->test(CalendarShiftGrid::class)
            ->assertSee('conflicting bookings')
            ->call('viewSlotConflicts', $date, 'EVENING')
            ->assertSet('showConflictModal', true)
            ->assertSee('Data Integrity Warning')
            ->assertSee('First Customer')
            ->assertSee('Second Customer');
    }

    public function test_inquiry_is_labelled_non_blocking_and_false_hold_claim_is_removed(): void
    {
        $hall = $this->hall();
        $this->bookingFor($hall, Carbon::now()->startOfMonth()->addDays(6)->format('Y-m-d'), 'INQUIRY', 'Inquiry Customer');

        Livewire::actingAs($this->owner($hall))
            ->test(CalendarShiftGrid::class)
            ->assertSee('Inquiry (non-blocking)')
            ->assertSee('INQ')
            ->assertDontSee('On Hold (24H)');
    }

    public function test_calendar_does_not_display_another_tenants_booking(): void
    {
        $hall = $this->hall();
        $date = Carbon::now()->startOfMonth()->addDays(7)->format('Y-m-d');

        // A foreign tenant's booking row pointing at the same hall must not
        // leak into this tenant's calendar (query is scoped by hall tenant).
        $foreignTenant = Tenant::create([
            'name' => 'Foreign Events Group',
            'slug' => 'foreign-events-'.uniqid(),
            'city' => 'Islamabad',
            'phone' => '+92 51 111000222',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);

        Booking::create([
            'tenant_id' => $foreignTenant->id,
            'hall_id' => $hall->id,
            'customer_name' => 'Foreign Tenant Client',
            'customer_phone' => '+92 300 9990000',
            'event_date' => $date,
            'slot_type' => 'NIGHT',
            'status' => 'CONFIRMED',
        ]);

        Livewire::actingAs($this->owner($hall))
            ->test(CalendarShiftGrid::class)
            ->assertDontSee('Foreign Tenant Client');
    }

    public function test_month_navigation_stays_within_the_current_month_bounds(): void
    {
        $hall = $this->hall();

        Livewire::actingAs($this->owner($hall))
            ->test(CalendarShiftGrid::class)
            ->call('nextMonth')
            ->call('prevMonth')
            ->call('goToToday')
            ->assertSet('currentMonth', Carbon::now()->format('Y-m'));
    }
}
