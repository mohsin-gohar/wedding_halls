<?php

namespace Tests\Feature;

use App\Livewire\AttendanceCalendar;
use App\Models\AttendanceHoliday;
use App\Models\StaffAttendance as AttendanceRecord;
use App\Models\StaffLeave;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceCalendarTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Calendar Banquets',
            'slug' => 'calendar-banquets',
            'email' => 'ops@calendar-banquets.pk',
            'phone' => '+92 300 4445566',
            'city' => 'Lahore',
            'subscription_plan' => 'STARTER',
            'currency' => 'PKR',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);
        $this->owner = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'ROLE_TENANT_OWNER',
            'password' => Hash::make('password'),
        ]);
        $this->staff = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'ROLE_CHEF',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_calendar_renders_real_attendance_statuses(): void
    {
        $date = Carbon::today()->format('Y-m-d');
        AttendanceRecord::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->staff->id,
            'date' => $date,
            'status' => 'PRESENT',
            'check_in' => '09:00',
            'check_out' => '18:00',
            'created_by' => $this->owner->id,
        ]);

        $cells = Livewire::actingAs($this->owner)
            ->test(AttendanceCalendar::class)
            ->assertSee('Attendance Calendar')
            ->assertSee('Monthly totals')
            ->viewData('cells');

        $this->assertSame('PRESENT', $cells[$this->staff->id.'_'.$date]['status']);
        $this->assertStringContainsString('09:00', $cells[$this->staff->id.'_'.$date]['detail']);
    }

    public function test_calendar_shows_approved_leave_holidays_and_weekly_off(): void
    {
        $month = Carbon::today()->startOfMonth();
        $leaveDate = $month->copy()->addDays(3)->format('Y-m-d');
        $holidayDate = $month->copy()->addDays(4)->format('Y-m-d');

        StaffLeave::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->staff->id,
            'leave_type' => 'CASUAL',
            'start_date' => $leaveDate,
            'end_date' => $leaveDate,
            'days' => 1,
            'reason' => 'Family commitment',
            'status' => 'APPROVED',
        ]);
        AttendanceHoliday::create([
            'tenant_id' => $this->tenant->id,
            'date' => $holidayDate,
            'name' => 'Eid ul Fitr',
            'type' => 'PUBLIC',
            'created_by' => $this->owner->id,
        ]);

        $sunday = $month->copy();
        while ($sunday->dayOfWeek !== Carbon::SUNDAY) {
            $sunday->addDay();
        }

        $cells = Livewire::actingAs($this->owner)
            ->test(AttendanceCalendar::class)
            ->viewData('cells');

        $this->assertSame('ON_LEAVE', $cells[$this->staff->id.'_'.$leaveDate]['status']);
        $this->assertSame('HOLIDAY', $cells[$this->staff->id.'_'.$holidayDate]['status']);
        $this->assertSame('WEEKLY_OFF', $cells[$this->staff->id.'_'.$sunday->format('Y-m-d')]['status']);
    }

    public function test_month_navigation_and_employee_filter(): void
    {
        $component = Livewire::actingAs($this->owner)
            ->test(AttendanceCalendar::class)
            ->call('nextMonth')
            ->assertSet('selectedMonth', Carbon::today()->addMonth()->format('Y-m'))
            ->call('previousMonth')
            ->call('goToToday')
            ->assertSet('selectedMonth', Carbon::today()->format('Y-m'))
            ->set('selectedStaffId', (string) $this->staff->id);

        $this->assertCount(1, $component->viewData('staff'));
    }

    public function test_holiday_can_be_added_rejects_duplicates_and_can_be_removed(): void
    {
        $date = Carbon::today()->addDays(10)->format('Y-m-d');

        Livewire::actingAs($this->owner)
            ->test(AttendanceCalendar::class)
            ->set('holidayDate', $date)
            ->set('holidayName', 'Company Retreat')
            ->set('holidayType', 'COMPANY')
            ->call('addHoliday')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attendance_holidays', [
            'tenant_id' => $this->tenant->id,
            'date' => $date.' 00:00:00',
            'name' => 'Company Retreat',
            'type' => 'COMPANY',
        ]);

        $holiday = AttendanceHoliday::where('tenant_id', $this->tenant->id)->whereDate('date', $date)->firstOrFail();

        Livewire::actingAs($this->owner)
            ->test(AttendanceCalendar::class)
            ->set('holidayDate', $date)
            ->set('holidayName', 'Duplicate Holiday')
            ->call('addHoliday')
            ->assertHasErrors('holidayDate');

        Livewire::actingAs($this->owner)
            ->test(AttendanceCalendar::class)
            ->call('deleteHoliday', $holiday->id);

        $this->assertDatabaseMissing('attendance_holidays', ['id' => $holiday->id]);
    }

    public function test_other_tenants_attendance_is_not_visible(): void
    {
        $foreignTenant = Tenant::create([
            'name' => 'Rival Venue',
            'slug' => 'rival-venue-'.uniqid(),
            'city' => 'Karachi',
            'phone' => '+92 21 111999888',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);
        $foreignStaff = User::factory()->create([
            'tenant_id' => $foreignTenant->id,
            'role' => 'ROLE_CHEF',
            'status' => 'ACTIVE',
        ]);
        AttendanceRecord::create([
            'tenant_id' => $foreignTenant->id,
            'user_id' => $foreignStaff->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'status' => 'PRESENT',
            'check_in' => '08:00',
        ]);

        $component = Livewire::actingAs($this->owner)->test(AttendanceCalendar::class);
        $staffIds = $component->viewData('staff')->pluck('id')->all();
        $cells = $component->viewData('cells');

        $this->assertNotContains($foreignStaff->id, $staffIds);
        $this->assertFalse(isset($cells[$foreignStaff->id.'_'.Carbon::today()->format('Y-m-d')]));
    }

    public function test_calendar_is_closed_to_customers(): void
    {
        $customer = User::factory()->create(['role' => 'ROLE_CUSTOMER']);

        Livewire::actingAs($customer)
            ->test(AttendanceCalendar::class)
            ->assertStatus(403);
    }

    public function test_deep_link_sets_attendance_date(): void
    {
        $this->actingAs($this->owner)
            ->get(route('staff.attendance', ['date' => '2026-11-05', 'staff_id' => $this->staff->id]))
            ->assertOk()
            ->assertSee('05 November 2026');
    }
}
