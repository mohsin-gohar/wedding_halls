<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

    // Edit Staff State
    public bool $isEditing = false;

    public ?int $editingStaffId = null;

    public string $editName = '';

    public string $editEmail = '';

    public string $editPhone = '';

    public string $editRole = 'ROLE_FRONT_DESK';

    public string $editPassword = '';

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    protected $rules = [
        'name' => 'required|string|max:100',
        'email' => 'required|email|unique:users,email',
        'phone' => 'nullable|string|max:30',
        'role' => 'required|in:ROLE_MANAGER,ROLE_ACCOUNTANT,ROLE_FRONT_DESK,ROLE_CHEF,ROLE_FLOOR_STAFF',
        'password' => 'required|string|min:6',
    ];

    public function mount()
    {
        $user = Auth::user();
        if (! $user || (! $user->isTenantOwner() && ! $user->isManager() && ! $user->isSuperAdmin())) {
            abort(403, 'Unauthorized. Only Venue Owners or General Managers can access Staff Management.');
        }
    }

    public function openCreateModal()
    {
        $tenant = Auth::user()->tenant;
        if ($tenant && ! $tenant->canAddStaff()) {
            $this->errorMessage = 'Staff limit reached ('.$tenant->users()->count().'/'.$tenant->subscriptionPlan->max_staff.'). Upgrade your SaaS subscription to add more team members.';

            return;
        }

        $this->reset(['name', 'email', 'phone', 'role', 'password', 'errorMessage', 'feedbackMessage']);
        $this->role = 'ROLE_FRONT_DESK';
        $this->isCreating = true;
    }

    public function closeCreateModal()
    {
        $this->isCreating = false;
        $this->reset(['name', 'email', 'phone', 'role', 'password', 'errorMessage']);
    }

    public function createStaffMember()
    {
        $tenant = Auth::user()->tenant;
        if ($tenant && ! $tenant->canAddStaff()) {
            $this->errorMessage = 'Staff limit reached. Please upgrade your subscription plan.';

            return;
        }

        $this->validate();

        $user = User::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
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
        $this->reset(['name', 'email', 'phone', 'password']);
    }

    public function openEditModal(int $userId)
    {
        $staff = User::where('tenant_id', Auth::user()->tenant_id)->findOrFail($userId);
        $this->editingStaffId = $staff->id;
        $this->editName = $staff->name;
        $this->editEmail = $staff->email;
        $this->editPhone = $staff->phone ?? '';
        $this->editRole = $staff->role;
        $this->editPassword = '';
        $this->errorMessage = null;
        $this->isEditing = true;
    }

    public function closeEditModal()
    {
        $this->isEditing = false;
        $this->editingStaffId = null;
        $this->reset(['editName', 'editEmail', 'editPhone', 'editRole', 'editPassword', 'errorMessage']);
    }

    public function updateStaffMember()
    {
        if (! $this->editingStaffId) {
            return;
        }

        $this->validate([
            'editName' => 'required|string|max:100',
            'editEmail' => 'required|email|unique:users,email,'.$this->editingStaffId,
            'editPhone' => 'nullable|string|max:30',
            'editRole' => 'required|in:ROLE_MANAGER,ROLE_ACCOUNTANT,ROLE_FRONT_DESK,ROLE_CHEF,ROLE_FLOOR_STAFF',
            'editPassword' => 'nullable|string|min:6',
        ]);

        $staff = User::where('tenant_id', Auth::user()->tenant_id)->findOrFail($this->editingStaffId);

        $data = [
            'name' => $this->editName,
            'email' => $this->editEmail,
            'phone' => $this->editPhone,
            'role' => $this->editRole,
        ];

        if (! empty($this->editPassword)) {
            $data['password'] = Hash::make($this->editPassword);
        }

        $staff->update($data);
        $staff->syncRoles([$this->editRole]);

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

        $staffName = $staff->name;
        $staff->delete();

        AuditLog::log(
            'STAFF_REMOVED',
            "Staff user [{$staffName}] was removed by [{$currentUser->name}].",
            $currentUser->tenant_id,
            'User',
            (string) $userId
        );

        $this->feedbackMessage = "Staff member [{$staffName}] has been removed.";
    }

    public function render()
    {
        $tenant = Auth::user()->tenant;
        $staffMembers = User::where('tenant_id', Auth::user()->tenant_id)
            ->latest()
            ->get();

        $maxStaff = $tenant && $tenant->subscriptionPlan ? $tenant->subscriptionPlan->max_staff : 10;
        $currentStaffCount = $staffMembers->count();

        return view('livewire.staff-manager', [
            'staffMembers' => $staffMembers,
            'tenant' => $tenant,
            'maxStaff' => $maxStaff,
            'currentStaffCount' => $currentStaffCount,
        ]);
    }
}
