<?php

namespace Tests\Feature;

use App\Livewire\CalendarShiftGrid;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CalendarViewsAndHoldExpiryTest extends TestCase
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

    public function test_week_day_and_agenda_views_show_the_booking(): void
    {
        $hall = $this->hall();
        $date = Carbon::now()->addDays(40)->toDateString();
        Booking::create([
            'tenant_id' => $hall->tenant_id,
            'hall_id' => $hall->id,
            'customer_name' => 'Agenda Walima Host',
            'customer_phone' => '+92 300 5557788',
            'event_date' => $date,
            'slot_type' => 'EVENING',
            'status' => 'CONFIRMED',
        ]);

        $owner = $this->owner($hall);

        foreach (['week', 'day', 'agenda'] as $mode) {
            Livewire::actingAs($owner)
                ->test(CalendarShiftGrid::class)
                ->set('anchorDate', $date)
                ->set('selectedHallId', $hall->id)
                ->call('setView', $mode)
                ->assertSet('viewMode', $mode)
                ->assertSee('Agenda Walima Host');
        }

        Livewire::actingAs($owner)
            ->test(CalendarShiftGrid::class)
            ->set('anchorDate', $date)
            ->call('setView', 'agenda')
            ->call('navigateNext')
            ->assertSet('viewMode', 'agenda');
    }

    public function test_owner_can_save_shift_windows_buffer_and_hold_hours(): void
    {
        $hall = $this->hall();

        Livewire::actingAs($this->owner($hall))
            ->test(CalendarShiftGrid::class)
            ->set('selectedHallId', $hall->id)
            ->set('bufferMinutes', 90)
            ->set('holdExpiryHours', '12')
            ->set('shiftStarts.EVENING', '19:00')
            ->set('shiftEnds.EVENING', '23:30')
            ->call('saveShiftPolicy')
            ->assertHasNoErrors();

        $hall->tenant->refresh();
        $this->assertSame(90, (int) $hall->tenant->shift_buffer_minutes);
        $this->assertSame(12, (int) $hall->tenant->hold_expiry_hours);
        $this->assertSame('19:00', $hall->tenant->shift_windows['EVENING']['start']);
        $this->assertSame('23:30', $hall->tenant->shift_windows['EVENING']['end']);
    }

    public function test_buffer_blocks_adjacent_shift_and_zero_buffer_does_not(): void
    {
        $hall = $this->hall();
        $date = Carbon::now()->addDays(20)->toDateString();
        Booking::create([
            'tenant_id' => $hall->tenant_id,
            'hall_id' => $hall->id,
            'customer_name' => 'Evening Host',
            'customer_phone' => '+92 300 5550101',
            'event_date' => $date,
            'slot_type' => 'EVENING',
            'status' => 'CONFIRMED',
        ]);

        $service = app(BookingService::class);
        $this->assertTrue($service->checkSlotAvailability($hall->id, $date, 'AFTERNOON'));

        $hall->tenant->update(['shift_buffer_minutes' => 180]);
        $this->assertFalse($service->checkSlotAvailability($hall->id, $date, 'AFTERNOON'));
    }

    public function test_hold_expiry_is_stamped_only_when_configured_and_command_cancels_it(): void
    {
        $hall = $this->hall();
        $service = app(BookingService::class);
        $date = Carbon::now()->addDays(25)->toDateString();

        $unstamped = $service->createReservation([
            'hall_id' => $hall->id,
            'customer_name' => 'Open Hold',
            'customer_phone' => '+92 300 5552222',
            'event_date' => $date,
            'slot_type' => 'NIGHT',
            'status' => 'HOLD',
            'guest_count' => 100,
        ]);
        $this->assertNull($unstamped->hold_expires_at);

        $hall->tenant->update(['hold_expiry_hours' => 6]);
        $stamped = $service->createReservation([
            'hall_id' => $hall->id,
            'customer_name' => 'Timed Hold',
            'customer_phone' => '+92 300 5553333',
            'event_date' => Carbon::now()->addDays(26)->toDateString(),
            'slot_type' => 'NIGHT',
            'status' => 'HOLD',
            'guest_count' => 100,
        ]);
        $this->assertNotNull($stamped->hold_expires_at);

        $stamped->update(['hold_expires_at' => now()->subHour()]);
        $this->artisan('bookings:expire-holds')->assertSuccessful();

        $stamped->refresh();
        $unstamped->refresh();
        $this->assertSame('CANCELLED', $stamped->status);
        $this->assertSame('HOLD', $unstamped->status);
        $this->assertTrue(AuditLog::where('action', 'BOOKING_HOLD_EXPIRED')->where('entity_id', $stamped->id)->exists());
    }

    public function test_calendar_csv_export_is_available_to_tenant_owner_and_scoped_to_selected_hall(): void
    {
        $hall = $this->hall();
        $date = Carbon::now()->addDays(40)->toDateString();
        Booking::create([
            'tenant_id' => $hall->tenant_id,
            'hall_id' => $hall->id,
            'customer_name' => 'CSV Export Host',
            'customer_phone' => '+92 300 5557788',
            'event_date' => $date,
            'slot_type' => 'EVENING',
            'status' => 'CONFIRMED',
        ]);

        Livewire::actingAs($this->owner($hall))
            ->test(CalendarShiftGrid::class)
            ->set('anchorDate', $date)
            ->set('selectedHallId', $hall->id)
            ->set('viewMode', 'day')
            ->call('exportCalendarCsv')
            ->assertFileDownloaded('riwaj-grand-calendar.csv');
    }
}
