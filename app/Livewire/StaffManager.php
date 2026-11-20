<?php

namespace App\Livewire;

use App\Models\AttendanceCorrection;
use App\Models\AuditLog;
use App\Models\StaffAttendance;
use App\Models\StaffLeave;
use App\Models\StaffPayroll;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class StaffManager extends Component
{
    public bool $isCreating = false;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $role = 'ROLE_FRONT_DESK';

    public string $password = '';

    public string $baseSalary = '';

    public string $search = '';

    public string $roleFilter = 'ALL';

    public string $statusFilter = 'ACTIVE';

    public bool $showFormerStaff = false;

    // Staff Profile Drawer/Modal State
    public bool $showProfileModal = false;

    public ?int $profileStaffId = null;

    public ?array $profileStats = null;

    // Edit Staff State
    public bool $isEditing = false;

    public ?int $editingStaffId = null;

    public string $editName = '';

    public string $editEmail = '';

    public string $editPhone = '';

    public string $editRole = 'ROLE_FRONT_DESK';

    public string $editPassword = '';

    public string $editBaseSalary = '';

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    protected $rules = [
        'name' => 'required|string|max:100',
        'email' => 'required|email|unique:users,email',
        'phone' => 'nullable|string|max:30',
        // Kept in sync with allowedRoles(); mutations revalidate against the
        // exact tenant-assignable set at the point of write.
        'role' => 'required|in:ROLE_MANAGER,ROLE_VENDOR_MANAGER,ROLE_ACCOUNTANT,ROLE_VENDOR_ACCOUNTANT,ROLE_FRONT_DESK,ROLE_BOOKING_MANAGER,ROLE_CHEF,ROLE_CATERING_STAFF,ROLE_FLOOR_STAFF,ROLE_SERVICE_STAFF,ROLE_STAFF_MEMBER,ROLE_PHOTOGRAPHER,ROLE_INVENTORY_MANAGER,ROLE_HR_MANAGER',
        'password' => 'required|string|min:8',
    ];

    public function mount()
    {
        $this->authorizeStaffManager();
    }

    private function authorizeStaffManager(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->isTenantOwner() && ! $user->isManager()) || ! $user->tenant_id) {
            abort(403, 'Unauthorized. Only Venue Owners or General Managers can access Staff Management.');
        }
    }

    private function allowedRoles(): array
    {
        // Vendor-operational roles only. Platform staff roles (support,
        // finance, moderation) are provisioned by the Super Admin and must
        // never be assignable from a tenant's staff screen.
        $roles = [
            'ROLE_ACCOUNTANT',
            'ROLE_FRONT_DESK',
            'ROLE_CHEF',
            'ROLE_FLOOR_STAFF',
            'ROLE_SERVICE_STAFF',
            'ROLE_VENDOR_ACCOUNTANT',
            'ROLE_BOOKING_MANAGER',
            'ROLE_STAFF_MEMBER',
            'ROLE_PHOTOGRAPHER',
            'ROLE_CATERING_STAFF',
            'ROLE_INVENTORY_MANAGER',
            'ROLE_HR_MANAGER',
        ];

        if (Auth::user()->isTenantOwner()) {
            array_unshift($roles, 'ROLE_MANAGER');
            array_unshift($roles, 'ROLE_VENDOR_MANAGER');
        }

        return array_values(array_unique($roles));
    }

    public function openCreateModal()
    {
        $this->authorizeStaffManager();
        $tenant = Auth::user()->tenant;
        if ($tenant && ! $tenant->canAddStaff()) {
            $maxStaff = $tenant->subscriptionPlan?->max_staff ?? 'plan limit';
            $this->errorMessage = 'Staff limit reached ('.$tenant->users()->count()."/{$maxStaff}). Upgrade your SaaS subscription to add more team members.";

            return;
        }

        $this->reset(['name', 'email', 'phone', 'role', 'password', 'errorMessage', 'feedbackMessage']);
        $this->role = 'ROLE_FRONT_DESK';
        $this->baseSalary = '';
        $this->isCreating = true;
    }

    public function closeCreateModal()
    {
        $this->authorizeStaffManager();
        $this->isCreating = false;
        $this->reset(['name', 'email', 'phone', 'role', 'password', 'errorMessage']);
    }

    public function createStaffMember()
    {
        $this->authorizeStaffManager();
        $tenant = Auth::user()->tenant;
        if ($tenant && ! $tenant->canAddStaff()) {
            $this->errorMessage = 'Staff limit reached. Please upgrade your subscription plan.';

            return;
        }

        $this->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'phone' => 'nullable|string|max:30',
            'role' => ['required', Rule::in($this->allowedRoles())],
            'password' => 'required|string|min:8',
            'baseSalary' => 'nullable|numeric|min:0|max:100000000',
        ]);

        $user = User::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'base_salary' => $this->baseSalary !== '' ? (float) $this->baseSalary : null,
            'password' => Hash::make($this->password),
        ]);
        $user->assignRole($this->role);

        AuditLog::log(
            'STAFF_CREATED',
            "Staff user [{$user->name}] created with role [{$this->role}].",
            Auth::user()->tenant_id,
            'User',
            (string) $user->id
        );

        $this->feedbackMessage = "Team member [{$user->name}] successfully added!";
        $this->isCreating = false;
        $this->reset(['name', 'email', 'phone', 'password', 'baseSalary']);
    }

    public function openEditModal(int $userId)
    {
        $this->authorizeStaffManager();
        $staff = User::where('tenant_id', Auth::user()->tenant_id)->findOrFail($userId);
        if (($staff->isTenantOwner() && $staff->id !== Auth::id())
            || (! Auth::user()->isTenantOwner() && $staff->isManager())) {
            abort(403, 'You do not have permission to edit this staff profile.');
        }
        $this->editingStaffId = $staff->id;
        $this->editName = $staff->name;
        $this->editEmail = $staff->email;
        $this->editPhone = $staff->phone ?? '';
        $this->editRole = $staff->role;
        $this->editPassword = '';
        $this->editBaseSalary = $staff->base_salary !== null ? (string) $staff->base_salary : '';
        $this->errorMessage = null;
        $this->isEditing = true;
    }

    public function closeEditModal()
    {
        $this->authorizeStaffManager();
        $this->isEditing = false;
        $this->editingStaffId = null;
        $this->reset(['editName', 'editEmail', 'editPhone', 'editRole', 'editPassword', 'editBaseSalary', 'errorMessage']);
    }

    public function updateStaffMember()
    {
        $this->authorizeStaffManager();
        if (! $this->editingStaffId) {
            return;
        }

        $staff = User::where('tenant_id', Auth::user()->tenant_id)->findOrFail($this->editingStaffId);

        $this->validate([
            'editName' => 'required|string|max:100',
            'editEmail' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->editingStaffId)],
            'editPhone' => 'nullable|string|max:30',
            'editRole' => $staff->isTenantOwner() ? 'nullable' : ['required', Rule::in($this->allowedRoles())],
            'editPassword' => 'nullable|string|min:8',
            'editBaseSalary' => 'nullable|numeric|min:0|max:100000000',
        ]);

        $data = [
            'name' => $this->editName,
            'email' => $this->editEmail,
            'phone' => $this->editPhone,
            'role' => $staff->isTenantOwner() ? $staff->role : $this->editRole,
            'base_salary' => $this->editBaseSalary !== '' ? (float) $this->editBaseSalary : null,
        ];

        if (! empty($this->editPassword)) {
            $data['password'] = Hash::make($this->editPassword);
        }

        $staff->update($data);
        if (! $staff->isTenantOwner()) {
            $staff->syncRoles([$this->editRole]);
        }

        AuditLog::log(
            'STAFF_UPDATED',
            "Staff user [{$staff->name}] profile updated with role [{$this->editRole}].",
            Auth::user()->tenant_id,
            'User',
            (string) $staff->id
        );

        $this->feedbackMessage = "Staff member [{$staff->name}] updated successfully!";
        $this->closeEditModal();
    }

    public function removeStaffMember(int $userId)
    {
        $this->authorizeStaffManager();
        $currentUser = Auth::user();
        if ($currentUser->id === $userId) {
            $this->errorMessage = 'You cannot remove your own user account.';

            return;
        }

        $staff = User::where('tenant_id', $currentUser->tenant_id)->findOrFail($userId);

        if ($staff->isTenantOwner()) {
            $this->errorMessage = 'The primary venue business owner account cannot be deleted.';

            return;
        }

        if (! $currentUser->isTenantOwner() && $staff->isManager()) {
            abort(403, 'Only the venue owner can remove a manager account.');
        }

        $staffName = $staff->name;
        $staff->delete(); // Revoke login while preserving payroll and attendance history.

        AuditLog::log(
            'STAFF_REMOVED',
            "Staff user [{$staffName}] was removed by [{$currentUser->name}].",
            $currentUser->tenant_id,
            'User',
            (string) $userId
        );

        $this->feedbackMessage = "Staff member [{$staffName}] has been removed.";
    }

    public function restoreStaffMember(int $userId): void
    {
        $this->authorizeStaffManager();
        if (! Auth::user()->isTenantOwner()) {
            abort(403, 'Only the venue owner can restore former staff accounts.');
        }

        $staff = User::onlyTrashed()->where('tenant_id', Auth::user()->tenant_id)->findOrFail($userId);
        $tenant = Auth::user()->tenant;
        if ($tenant && ! $tenant->canAddStaff()) {
            $this->errorMessage = 'Staff limit reached. Upgrade your subscription before restoring this account.';

            return;
        }

        $staff->restore();
        $staff->update(['status' => 'ACTIVE']);

        AuditLog::log(
            'STAFF_RESTORED',
            'Former staff user ['.$staff->name.'] restored by ['.Auth::user()->name.'].',
            Auth::user()->tenant_id,
            'User',
            (string) $staff->id
        );

        $this->feedbackMessage = "Former staff member [{$staff->name}] restored.";
    }

    public function openProfileModal(int $userId): void
    {
        $this->authorizeStaffManager();
        $staff = User::where('tenant_id', Auth::user()->tenant_id)
            ->withTrashed()
            ->findOrFail($userId);

        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth()->format('Y-m-d');
        $monthEnd = $now->copy()->endOfMonth()->format('Y-m-d');

        // Attendance summary for current month
        $attendancesCount = StaffAttendance::where('tenant_id', Auth::user()->tenant_id)
            ->where('user_id', $staff->id)
            ->whereDate('date', '>=', $monthStart)->whereDate('date', '<=', $monthEnd)
            ->where('status', 'PRESENT')
            ->count();

        $overtimeTotal = (float) StaffAttendance::where('tenant_id', Auth::user()->tenant_id)
            ->where('user_id', $staff->id)
            ->whereDate('date', '>=', $monthStart)->whereDate('date', '<=', $monthEnd)
            ->sum('overtime_hours');

        // Latest payroll record
        $latestPayroll = StaffPayroll::where('tenant_id', Auth::user()->tenant_id)
            ->where('user_id', $staff->id)
            ->latest()
            ->first();

        $this->profileStaffId = $staff->id;
        $this->profileStats = [
            'staff' => $staff,
            'attendances_present' => $attendancesCount,
            'overtime_total' => $overtimeTotal,
            'latest_payroll_month' => $latestPayroll?->month_year,
            'latest_payroll_status' => $latestPayroll?->status,
            'latest_payroll_net' => $latestPayroll?->net_salary,
        ];
        $this->showProfileModal = true;
    }

    public function closeProfileModal(): void
    {
        $this->showProfileModal = false;
        $this->profileStaffId = null;
        $this->profileStats = null;
    }

    public function toggleStaffStatus(int $userId): void
    {
        $this->authorizeStaffManager();
        $currentUser = Auth::user();
        if ($currentUser->id === $userId) {
            $this->errorMessage = 'You cannot suspend your own account.';

            return;
        }

        $staff = User::where('tenant_id', $currentUser->tenant_id)->findOrFail($userId);
        if ($staff->isTenantOwner()) {
            $this->errorMessage = 'Cannot alter status of the primary venue owner.';

            return;
        }

        $newStatus = $staff->status === 'SUSPENDED' ? 'ACTIVE' : 'SUSPENDED';
        $staff->update(['status' => $newStatus]);

        AuditLog::log(
            'STAFF_STATUS_CHANGED',
            "Staff user [{$staff->name}] status changed to [{$newStatus}].",
            $currentUser->tenant_id,
            'User',
            (string) $userId
        );

        $this->feedbackMessage = "Staff member [{$staff->name}] status updated to {$newStatus}.";
        if ($this->showProfileModal && $this->profileStaffId === $userId) {
            $this->openProfileModal($userId);
        }
    }

    public function purgeStaffMember(int $userId): void
    {
        $this->authorizeStaffManager();
        $currentUser = Auth::user();
        if ($currentUser->id === $userId) {
            $this->errorMessage = 'You cannot delete your own user account.';

            return;
        }

        $staff = User::where('tenant_id', $currentUser->tenant_id)
            ->withTrashed()
            ->findOrFail($userId);

        if ($staff->isTenantOwner()) {
            $this->errorMessage = 'The primary venue business owner account cannot be permanently deleted.';

            return;
        }

        if (! $currentUser->isTenantOwner() && $staff->isManager()) {
            abort(403, 'Only the venue owner can permanently purge a manager account.');
        }

        $staffName = $staff->name;

        // Clean up linked HR history strictly scoped to the current tenant.
        // Corrections and leave applications would otherwise point at a user
        // that no longer exists after a permanent purge.
        $tenantId = $currentUser->tenant_id;
        $attendanceIds = StaffAttendance::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->pluck('id');

        AttendanceCorrection::where('tenant_id', $tenantId)
            ->where(function ($query) use ($userId, $attendanceIds) {
                $query->where('user_id', $userId)
                    ->orWhereIn('attendance_id', $attendanceIds);
            })
            ->delete();
        StaffAttendance::where('tenant_id', $tenantId)->where('user_id', $userId)->delete();
        StaffPayroll::where('tenant_id', $tenantId)->where('user_id', $userId)->delete();
        StaffLeave::where('tenant_id', $tenantId)->where('user_id', $userId)->delete();

        $staff->forceDelete();

        AuditLog::log(
            'STAFF_PERMANENTLY_PURGED',
            "Staff user [{$staffName}] permanently purged by [{$currentUser->name}] to free server storage.",
            $currentUser->tenant_id,
            'User',
            (string) $userId
        );

        $this->showProfileModal = false;
        $this->profileStaffId = null;
        $this->profileStats = null;
        $this->feedbackMessage = "Staff member [{$staffName}] and records permanently deleted from server.";
    }

    public function render()
    {
        $this->authorizeStaffManager();
        $tenant = Auth::user()->tenant;
        $staffQuery = $this->showFormerStaff
            ? User::onlyTrashed()->hallStaff(Auth::user()->tenant_id)
            : User::hallStaff(Auth::user()->tenant_id);

        if (! $this->showFormerStaff && $this->statusFilter !== 'ALL') {
            $staffQuery->where('status', $this->statusFilter);
        }
        if ($this->roleFilter !== 'ALL') {
            $staffQuery->where('role', $this->roleFilter);
        }
        if (trim($this->search) !== '') {
            $search = '%'.trim($this->search).'%';
            $staffQuery->where(fn ($query) => $query->where('name', 'like', $search)
                ->orWhere('email', 'like', $search)
                ->orWhere('phone', 'like', $search));
        }
        $staffMembers = $staffQuery->latest()->get();

        $maxStaff = $tenant && $tenant->subscriptionPlan ? $tenant->subscriptionPlan->max_staff : 10;
        $currentStaffCount = User::hallStaff(Auth::user()->tenant_id)->count();

        return view('livewire.staff-manager', [
            'staffMembers' => $staffMembers,
            'tenant' => $tenant,
            'maxStaff' => $maxStaff,
            'currentStaffCount' => $currentStaffCount,
            'allowedRoles' => $this->allowedRoles(),
        ]);
    }
}
