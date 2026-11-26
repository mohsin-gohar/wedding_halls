<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Package;
use App\Models\Tenant;
use App\Support\PermissionMatrix;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Catering Packages & Menus — Riwaj Grand ERP'])]
class PackageManager extends Component
{
    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    // Filter
    public string $filterStatus = 'ALL'; // ALL, ACTIVE, INACTIVE

    /** Platform admins must explicitly choose which tenant's catalogue to manage. */
    public ?string $selectedTenantId = null;

    /** Server-side allow-list for the one-click dish presets in the Blade view. */
    public const PRESET_DISHES = [
        'Mutton Kunna Gosht',
        'Chicken Degi Biryani',
        'Beef Seekh Kabab',
        'Tandoori Roghni Naan',
        'Zafrani Kheer',
        'Shahi Zarda',
        'Kashmiri Chai & Almonds',
    ];

    // Create / Edit Modal State
    public bool $showPackageModal = false;

    public bool $isEditing = false;

    public ?string $editingPackageId = null;

    // Form inputs
    public string $packageName = '';

    public string $packageDescription = '';

    public float $perHeadPrice = 2200;

    public int $minGuests = 200;

    public bool $isActive = true;

    // Dynamic Dish / Menu Items Management
    public array $menuItems = [];

    public string $newMenuItemName = '';

    public function mount()
    {
        $this->authorizePackageAction('packages.view');
    }

    private function authorizePackageAction(string $action): void
    {
        $user = Auth::user();
        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        $permission = $action === 'packages.view' ? 'packages.view' : 'packages.manage';
        abort_unless(PermissionMatrix::userCan($user, $permission), 403, 'Missing catering package permission.');
    }

    /** The tenant context for reads: null means "nothing visible" for a platform admin. */
    private function resolvedTenantId(): ?string
    {
        $user = Auth::user();
        if (! $user) {
            return null;
        }

        if ($user->isSuperAdmin() && ! $user->tenant_id) {
            if (! $this->selectedTenantId) {
                return null;
            }

            return Tenant::whereKey($this->selectedTenantId)->exists() ? $this->selectedTenantId : null;
        }

        return $user->tenant_id;
    }

    /** Mutations must have an explicit tenant context. */
    private function targetTenantId(): string
    {
        $tenantId = $this->resolvedTenantId();
        if (! $tenantId) {
            abort(403, 'Select a tenant before managing its catering packages.');
        }

        return $tenantId;
    }

    public function selectTenant(string $tenantId): void
    {
        $user = Auth::user();
        if (! $user || ! $user->isSuperAdmin()) {
            abort(403, 'Only platform admins may select a tenant catalogue.');
        }

        validator(['tenantId' => $tenantId], [
            'tenantId' => ['required', Rule::exists('tenants', 'id')],
        ])->validate();

        $this->selectedTenantId = $tenantId;
        $this->feedbackMessage = null;
        $this->errorMessage = null;
    }

    // ==========================================
    // MODAL ACTIONS
    // ==========================================
    public function openCreateModal()
    {
        $this->authorizePackageAction('packages.manage');
        $this->targetTenantId();

        $this->isEditing = false;
        $this->editingPackageId = null;
        $this->packageName = '';
        $this->packageDescription = '';
        $this->perHeadPrice = 2200;
        $this->minGuests = 200;
        $this->isActive = true;
        $this->menuItems = [
            'Chicken Biryani (Aged Basmati)',
            'Mutton Qorma Degi',
            'Tandoori Roghni Naan',
            'Zafrani Kheer',
            'Fresh Mint Raita & Salad',
        ];
        $this->newMenuItemName = '';
        $this->feedbackMessage = null;
        $this->errorMessage = null;
        $this->showPackageModal = true;
    }

    public function openEditModal(string $packageId)
    {
        $this->authorizePackageAction('packages.manage');
        $package = Package::where('tenant_id', $this->targetTenantId())->findOrFail($packageId);

        $this->isEditing = true;
        $this->editingPackageId = $package->id;
        $this->packageName = $package->name;
        $this->packageDescription = $package->description ?? '';
        $this->perHeadPrice = (float) $package->per_head_price;
        $this->minGuests = (int) $package->min_guests;
        $this->isActive = (bool) $package->is_active;
        $this->menuItems = is_array($package->menu_items_json) ? $package->menu_items_json : [];
        $this->newMenuItemName = '';
        $this->feedbackMessage = null;
        $this->errorMessage = null;
        $this->showPackageModal = true;
    }

    public function closePackageModal()
    {
        $this->showPackageModal = false;
        $this->editingPackageId = null;
    }

    // ==========================================
    // DISH LIST BUILDER
    // ==========================================
    public function addMenuItem()
    {
        $this->authorizePackageAction('packages.manage');

        $item = trim($this->newMenuItemName);
        if ($item === '') {
            return;
        }

        validator(['item' => $item], [
            'item' => 'required|string|max:120',
        ])->validate();

        if (! in_array($item, $this->menuItems, true)) {
            $this->menuItems[] = $item;
        }
        $this->newMenuItemName = '';
    }

    public function removeMenuItem(int $index)
    {
        $this->authorizePackageAction('packages.manage');

        if (isset($this->menuItems[$index])) {
            unset($this->menuItems[$index]);
            $this->menuItems = array_values($this->menuItems);
        }
    }

    public function addPresetDish(string $dish)
    {
        $this->authorizePackageAction('packages.manage');

        // The argument comes from Livewire's request payload, not from the
        // rendered button, so it must be checked against a server allow-list.
        validator(['dish' => $dish], [
            'dish' => ['required', Rule::in(self::PRESET_DISHES)],
        ])->validate();

        if (! in_array($dish, $this->menuItems, true)) {
            $this->menuItems[] = $dish;
        }
    }

    // ==========================================
    // SAVE PACKAGE (CREATE / UPDATE)
    // ==========================================
    public function savePackage()
    {
        $this->authorizePackageAction($this->isEditing ? 'packages.manage' : 'packages.manage');
        $tenantId = $this->targetTenantId();

        $this->validate([
            'packageName' => 'required|string|min:3|max:120',
            'perHeadPrice' => 'required|numeric|min:500|max:99999999.99',
            'minGuests' => 'required|integer|min:20',
            'packageDescription' => 'nullable|string|max:1000',
            'menuItems' => 'required|array|min:1|max:100',
            'menuItems.*' => 'required|string|max:120',
        ]);

        $this->menuItems = array_values(array_map('trim', $this->menuItems));
        if (empty($this->menuItems)) {
            $this->errorMessage = 'Please add at least one dish / menu item to this package.';

            return;
        }

        if ($this->isEditing && $this->editingPackageId) {
            $package = Package::where('tenant_id', $tenantId)->findOrFail($this->editingPackageId);
            $package->update([
                'name' => $this->packageName,
                'description' => $this->packageDescription,
                'per_head_price' => $this->perHeadPrice,
                'min_guests' => $this->minGuests,
                'menu_items_json' => array_values($this->menuItems),
                'is_active' => $this->isActive,
            ]);

            AuditLog::log(
                'PACKAGE_UPDATED',
                "Updated Catering Package [{$package->name}] with per-head rate PKR {$this->perHeadPrice}.",
                $tenantId,
                'Package',
                $package->id
            );

            $this->feedbackMessage = "Catering Package [{$package->name}] updated successfully!";
        } else {
            $package = Package::create([
                'tenant_id' => $tenantId,
                'name' => $this->packageName,
                'description' => $this->packageDescription,
                'per_head_price' => $this->perHeadPrice,
                'min_guests' => $this->minGuests,
                'menu_items_json' => array_values($this->menuItems),
                'is_active' => $this->isActive,
            ]);

            AuditLog::log(
                'PACKAGE_CREATED',
                "Created new Catering Package [{$package->name}] at PKR {$this->perHeadPrice}/head.",
                $tenantId,
                'Package',
                $package->id
            );

            $this->feedbackMessage = "New Catering Package [{$package->name}] created successfully!";
        }

        $this->showPackageModal = false;
    }

    // ==========================================
    // TOGGLE & DELETE
    // ==========================================
    public function togglePackageStatus(string $packageId)
    {
        $this->authorizePackageAction('package.status');
        $tenantId = $this->targetTenantId();

        $package = Package::where('tenant_id', $tenantId)->findOrFail($packageId);
        $newStatus = ! $package->is_active;
        $package->update(['is_active' => $newStatus]);

        AuditLog::log(
            'PACKAGE_STATUS_CHANGED',
            "Catering Package [{$package->name}] ".($newStatus ? 'activated' : 'archived').'.',
            $tenantId,
            'Package',
            $package->id
        );

        $statusStr = $newStatus ? 'Activated' : 'Archived';
        $this->feedbackMessage = "Package [{$package->name}] has been {$statusStr}.";
    }

    public function deletePackage(string $packageId)
    {
        $this->authorizePackageAction('packages.manage');
        $tenantId = $this->targetTenantId();

        $user = Auth::user();
        $package = Package::where('tenant_id', $tenantId)->findOrFail($packageId);

        $activeBookingsCount = $package->bookings()->whereIn('status', ['HOLD', 'CONFIRMED'])->count();
        if ($activeBookingsCount > 0) {
            $this->errorMessage = "Cannot delete [{$package->name}] because it is currently attached to {$activeBookingsCount} active booking(s). You can unpublish it instead.";

            return;
        }

        $name = $package->name;
        $package->delete();

        AuditLog::log(
            'PACKAGE_DELETED',
            "Deleted Catering Package [{$name}].",
            $user->tenant_id,
            'Package',
            $packageId
        );

        $this->feedbackMessage = "Catering Package [{$name}] deleted successfully.";
    }

    public function render()
    {
        $this->authorizePackageAction('packages.view');
        $user = Auth::user();
        $tenantId = $this->resolvedTenantId();

        // A platform admin with no explicit tenant selection sees nothing.
        $query = Package::where('tenant_id', $tenantId ?? '00000000-0000-0000-0000-000000000000');

        if ($this->filterStatus === 'ACTIVE') {
            $query->where('is_active', true);
        } elseif ($this->filterStatus === 'INACTIVE') {
            $query->where('is_active', false);
        }

        $packages = $query->latest()->get();

        $totalPackages = $tenantId ? Package::where('tenant_id', $tenantId)->count() : 0;
        $activePackages = $tenantId ? Package::where('tenant_id', $tenantId)->where('is_active', true)->count() : 0;
        $avgPrice = $tenantId ? (Package::where('tenant_id', $tenantId)->where('is_active', true)->avg('per_head_price') ?? 0) : 0;

        $tenantOptions = $user->isSuperAdmin()
            ? Tenant::query()->orderBy('name')->limit(200)->get(['id', 'name'])
            : collect([]);

        return view('livewire.package-manager', [
            'packages' => $packages,
            'totalPackages' => $totalPackages,
            'activePackages' => $activePackages,
            'avgPrice' => $avgPrice,
            'tenantOptions' => $tenantOptions,
            'selectedTenantId' => $this->selectedTenantId,
        ]);
    }
}
