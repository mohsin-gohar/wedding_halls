<?php

namespace Tests\Feature;

use App\Livewire\StaffAttendance;
use App\Models\StaffAttendance as AttendanceRecord;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class StaffAttendanceUniqueConstraintRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_daily_attendance_multiple_times_does_not_trigger_unique_constraint_violation(): void
    {
        $tenant = Tenant::create([
            'name' => 'Royal Marquee Bahria',
            'slug' => 'royal-marquee-bahria',
            'email' => 'bahria@marquee.pk',
            'phone' => '+92 300 1234567',
            'city' => 'Lahore',
            'subscription_plan' => 'STARTER',
            'currency' => 'PKR',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_TENANT_OWNER',
            'password' => Hash::make('password'),
        ]);

        $staff1 = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_FRONT_DESK',
            'status' => 'ACTIVE',
        ]);

        $staff2 = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_CHEF',
            'status' => 'ACTIVE',
        ]);

        $targetDate = '2026-11-07';

        // 1. First save: initial attendance records created
        Livewire::actingAs($owner)
            ->test(StaffAttendance::class)
            ->set('selectedDate', $targetDate)
            ->set('attendanceData', [
                $staff1->id => [
                    'status' => 'PRESENT',
                    'check_in' => '10:00',
                    'check_out' => '22:00',
                    'overtime_hours' => 0,
                    'notes' => 'First save',
                ],
                $staff2->id => [
                    'status' => 'HALF_DAY',
                    'check_in' => '10:00',
                    'check_out' => '16:00',
                    'overtime_hours' => 0,
                    'notes' => 'Half day',
                ],
            ])
            ->call('saveDailyAttendance')
            ->assertHasNoErrors();

        $this->assertEquals(2, AttendanceRecord::where('tenant_id', $tenant->id)->whereDate('date', $targetDate)->count());

        // 2. Second save on the EXACT same date (this previously caused UniqueConstraintViolationException)
        Livewire::actingAs($owner)
            ->test(StaffAttendance::class)
            ->set('selectedDate', $targetDate)
            ->call('loadAttendanceForDate')
            ->set('attendanceData', [
                $staff1->id => [
                    'status' => 'PRESENT',
                    'check_in' => '09:30',
                    'check_out' => '22:30',
                    'overtime_hours' => 2.5,
                    'notes' => 'Updated overtime',
                ],
                $staff2->id => [
                    'status' => 'PRESENT',
                    'check_in' => '10:00',
                    'check_out' => '22:00',
                    'overtime_hours' => 1.0,
                    'notes' => 'Updated to full present',
                ],
            ])
            ->call('saveDailyAttendance')
            ->assertHasNoErrors();

        // Must still be 2 records (updated, not duplicated or crashed)
        $this->assertEquals(2, AttendanceRecord::where('tenant_id', $tenant->id)->whereDate('date', $targetDate)->count());

        $rec1 = AttendanceRecord::where('tenant_id', $tenant->id)->where('user_id', $staff1->id)->whereDate('date', $targetDate)->first();
        $this->assertEquals(2.5, (float) $rec1->overtime_hours);
        $this->assertEquals('Updated overtime', $rec1->notes);

        // 3. Test edit record modal save on same date
        Livewire::actingAs($owner)
            ->test(StaffAttendance::class)
            ->call('openEditRecordModal', $staff1->id, $targetDate)
            ->set('editingStatus', 'ON_LEAVE')
            ->set('editingNotes', 'Changed to leave')
            ->set('editingCorrectionReason', 'Employee was on approved leave')
            ->call('saveEditedRecord')
            ->assertHasNoErrors();

        $rec1->refresh();
        $this->assertEquals('ON_LEAVE', $rec1->status);
        $this->assertEquals('Changed to leave', $rec1->notes);
    }
}
