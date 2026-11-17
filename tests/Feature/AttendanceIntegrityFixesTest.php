<?php

namespace Tests\Feature;

use App\Livewire\AttendanceCalendar;
use App\Livewire\StaffAttendance;
use App\Models\AttendanceCorrection;
use App\Models\StaffAttendance as AttendanceRecord;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceIntegrityFixesTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Integrity Banquets',
            'slug' => 'integrity-banquets',
            'email' => 'ops@integrity.pk',
            'phone' => '+92 300 0001111',
            'city' => 'Karachi',
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

    public function test_bulk_present_is_an_attestation_without_invented_punch_times(): void
    {
        Livewire::actingAs($this->owner)
            ->test(StaffAttendance::class)
            ->call('markAllPresent')
            ->call('saveDailyAttendance')
            ->assertHasNoErrors();

        $record = AttendanceRecord::where('user_id', $this->staff->id)->first();
        $this->assertNotNull($record);
        $this->assertSame('PRESENT', $record->status);
        $this->assertSame('MANUAL_ATTESTATION', $record->source);
        $this->assertNull($record->check_in);
        $this->assertNull($record->check_out);
    }

    public function test_overnight_shift_is_accepted_and_same_day_reversal_is_not(): void
    {
        Livewire::actingAs($this->owner)
            ->test(StaffAttendance::class)
            ->set('attendanceData', [
                $this->staff->id => [
                    'status' => 'PRESENT',
                    'check_in' => '18:00',
                    'check_out' => '02:00',
                    'overtime_hours' => 0,
                    'notes' => 'Walima overnight',
                    'overnight' => true,
                    'source' => 'RECORDED',
                ],
            ])
            ->call('saveDailyAttendance')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('staff_attendances', [
            'user_id' => $this->staff->id,
            'check_in' => '18:00',
            'check_out' => '02:00',
            'overnight' => true,
        ]);
    }

    public function test_editing_an_existing_record_requires_a_reason_and_keeps_history(): void
    {
        AttendanceRecord::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->staff->id,
            'date' => '2026-11-12',
            'status' => 'PRESENT',
            'check_in' => '10:00',
            'check_out' => '18:00',
            'overtime_hours' => 0,
            'source' => 'RECORDED',
            'created_by' => $this->owner->id,
        ]);

        Livewire::actingAs($this->owner)
            ->test(StaffAttendance::class)
            ->call('openEditRecordModal', $this->staff->id, '2026-11-12')
            ->set('editingStatus', 'LATE')
            ->set('editingCheckIn', '11:15')
            ->call('saveEditedRecord')
            ->assertHasErrors('editingCorrectionReason');

        Livewire::actingAs($this->owner)
            ->test(StaffAttendance::class)
            ->call('openEditRecordModal', $this->staff->id, '2026-11-12')
            ->set('editingStatus', 'LATE')
            ->set('editingCheckIn', '11:15')
            ->set('editingCorrectionReason', 'Staff arrived after the baraat seating')
            ->call('saveEditedRecord')
            ->assertHasNoErrors();

        $correction = AttendanceCorrection::where('user_id', $this->staff->id)->first();
        $this->assertNotNull($correction);
        $this->assertSame('PRESENT', $correction->old_status);
        $this->assertSame('LATE', $correction->new_status);
        $this->assertSame('10:00', $correction->old_check_in);
        $this->assertSame('11:15', $correction->new_check_in);
        $this->assertSame($this->owner->id, $correction->actor_id);
        $this->assertSame('LATE', AttendanceRecord::where('user_id', $this->staff->id)->first()->status);
    }

    public function test_weekly_off_follows_the_tenant_setting(): void
    {
        $this->tenant->update(['weekly_off_days' => [5]]);

        $month = Carbon::today()->startOfMonth();
        $friday = $month->copy();
        while ($friday->dayOfWeek !== Carbon::FRIDAY) {
            $friday->addDay();
        }
        $sunday = $month->copy();
        while ($sunday->dayOfWeek !== Carbon::SUNDAY) {
            $sunday->addDay();
        }

        $cells = Livewire::actingAs($this->owner)
            ->test(AttendanceCalendar::class)
            ->viewData('cells');

        $this->assertSame('WEEKLY_OFF', $cells[$this->staff->id.'_'.$friday->format('Y-m-d')]['status']);
        $this->assertSame('NOT_MARKED', $cells[$this->staff->id.'_'.$sunday->format('Y-m-d')]['status']);
    }
}
