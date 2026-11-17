<?php

namespace Tests\Feature;

use App\Livewire\StaffAttendance;
use App\Models\StaffAttendance as AttendanceRecord;
use App\Models\StaffLeave;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Core HR business rules (Phase 2 hardening):
 *  - an employee is never auto-marked PRESENT by opening/saving an empty register
 *  - worked states require a real check-in, and check-out must follow check-in
 *  - nobody may approve their own leave; overlapping leave is rejected
 *  - approved leave is reflected in attendance records
 */
class AttendanceCoreRulesTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Enterprise Banquets',
            'slug' => 'enterprise-banquets',
            'email' => 'ops@enterprise-banquets.pk',
            'phone' => '+92 300 1112233',
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

    public function test_opening_and_saving_untouched_register_does_not_create_present_records(): void
    {
        Livewire::actingAs($this->owner)
            ->test(StaffAttendance::class)
            ->call('saveDailyAttendance');

        $this->assertDatabaseMissing('staff_attendances', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->staff->id,
        ]);
    }

    public function test_present_status_requires_a_check_in(): void
    {
        Livewire::actingAs($this->owner)
            ->test(StaffAttendance::class)
            ->set('attendanceData', [
                $this->staff->id => [
                    'status' => 'PRESENT',
                    'check_in' => '',
                    'check_out' => '',
                    'overtime_hours' => 0,
                    'notes' => '',
                ],
            ])
            ->call('saveDailyAttendance')
            ->assertHasErrors("attendanceData.{$this->staff->id}.check_in");

        $this->assertDatabaseMissing('staff_attendances', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->staff->id,
        ]);
    }

    public function test_check_out_before_check_in_is_rejected(): void
    {
        Livewire::actingAs($this->owner)
            ->test(StaffAttendance::class)
            ->set('attendanceData', [
                $this->staff->id => [
                    'status' => 'PRESENT',
                    'check_in' => '18:00',
                    'check_out' => '09:00',
                    'overtime_hours' => 0,
                    'notes' => '',
                ],
            ])
            ->call('saveDailyAttendance')
            ->assertHasErrors("attendanceData.{$this->staff->id}.check_out");

        $this->assertDatabaseMissing('staff_attendances', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->staff->id,
        ]);
    }

    public function test_valid_check_in_and_check_out_is_persisted(): void
    {
        Livewire::actingAs($this->owner)
            ->test(StaffAttendance::class)
            ->set('attendanceData', [
                $this->staff->id => [
                    'status' => 'PRESENT',
                    'check_in' => '09:30',
                    'check_out' => '18:30',
                    'overtime_hours' => 0,
                    'notes' => 'Evening walima shift',
                ],
            ])
            ->call('saveDailyAttendance')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('staff_attendances', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->staff->id,
            'status' => 'PRESENT',
            'check_in' => '09:30',
            'check_out' => '18:30',
        ]);
    }

    public function test_manager_cannot_approve_their_own_leave(): void
    {
        $leave = StaffLeave::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->owner->id,
            'leave_type' => 'CASUAL',
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-21',
            'days' => 2,
            'reason' => 'Family event',
            'status' => 'PENDING',
        ]);

        Livewire::actingAs($this->owner)
            ->test(StaffAttendance::class)
            ->call('approveLeave', $leave->id)
            ->assertStatus(403);
    }

    public function test_overlapping_leave_is_rejected(): void
    {
        StaffLeave::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->staff->id,
            'leave_type' => 'CASUAL',
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-22',
            'days' => 3,
            'reason' => 'Already scheduled',
            'status' => 'APPROVED',
        ]);

        Livewire::actingAs($this->owner)
            ->test(StaffAttendance::class)
            ->set('leaveUserId', $this->staff->id)
            ->set('leaveStartDate', '2026-11-21')
            ->set('leaveEndDate', '2026-11-23')
            ->set('leaveReason', 'Another overlapping request')
            ->call('submitLeave')
            ->assertHasErrors('leaveStartDate');

        $this->assertSame(1, StaffLeave::where('tenant_id', $this->tenant->id)->count());
    }

    public function test_approved_leave_creates_on_leave_attendance_records(): void
    {
        $leave = StaffLeave::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->staff->id,
            'leave_type' => 'SICK',
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-21',
            'days' => 2,
            'reason' => 'Medical rest',
            'status' => 'PENDING',
        ]);

        Livewire::actingAs($this->owner)
            ->test(StaffAttendance::class)
            ->call('approveLeave', $leave->id)
            ->assertHasNoErrors();

        $this->assertSame(2, AttendanceRecord::where('tenant_id', $this->tenant->id)
            ->where('user_id', $this->staff->id)
            ->where('status', 'ON_LEAVE')
            ->count());
    }
}
