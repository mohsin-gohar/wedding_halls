<?php

namespace Tests\Feature;

use App\Livewire\AttendanceCalendar;
use App\Livewire\StaffAttendance;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_accountant_without_staff_attendance_permission_cannot_open_hr_attendance_tools(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $accountant = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_ACCOUNTANT',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($accountant)
            ->test(StaffAttendance::class)
            ->assertStatus(403);

        Livewire::actingAs($accountant)
            ->test(AttendanceCalendar::class)
            ->assertStatus(403);
    }

    public function test_hr_manager_with_staff_attendance_permission_can_open_hr_attendance_tools(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $hrManager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_HR_MANAGER',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($hrManager)
            ->test(StaffAttendance::class)
            ->assertOk();

        Livewire::actingAs($hrManager)
            ->test(AttendanceCalendar::class)
            ->assertOk();
    }
}
