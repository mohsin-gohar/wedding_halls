<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Package;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Catering Packages & Menus — VenueCloud ERP'])]
class PackageManager extends Component
{
    public ?string $feedbackMessage = null;
    public ?string $errorMessage = null;

    // Filter
    public string $filterStatus = 'ALL'; // ALL, ACTIVE, INACTIVE

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
        $user = Auth::user();
        if (!$user || (!$user->isTenantOwner() && !$user->isManager() && !$user->isChef() && !$user->isSuperAdmin())) {
            abort(403, 'Unauthorized. Access to Catering Packages is restricted to Venue Owners, Chefs, and Managers.');
        }

        $this->seedInitialPackagesIfEmpty();
    }

    protected function seedInitialPackagesIfEmpty()
    {
        $tenantId = Auth::user()->tenant_id;
        if ($tenantId && Package::where('tenant_id', $tenantId)->count() === 0) {
            $defaultPackages = [
                [
                    'name' => 'Royal Mughal Barat Feast',
                    'description' => 'Opulent royal feast featuring slow-cooked mutton roghan josh, long-grain chicken biryani, and artisanal tandoori breads.',
                    'per_head_price' => 2450.00,
                    'min_guests' => 250,
                    'menu_items_json' => [
                        'Mutton Roghan Josh with Bone Marrow',
                        'Special Degi Chicken Biryani (Basmati Aged)',
                        'Charcoal Grilled Chicken Reshmi Kabab',
                        'Hot Tandoori Roghni Naan & Taftan',
                        'Zafrani Kheer & Gulab Jamun',
                        'Fresh Mint Raita & Organic Salad',
                        'Chilled Soft Drinks & Mineral Water'
                    ],
                    'is_active' => true,
                ],
                [
                    'name' => 'Emerald Walima Reception Buffet',
                    'description' => 'Sophisticated wedding banquet with boneless mutton handi, live chicken sajji, and dessert bar.',
                    'per_head_price' => 2850.00,
                    'min_guests' => 300,
                    'menu_items_json' => [
                        'Mutton Kunna Gosht (Claypot Gravy)',
                        'Chicken White Qorma (Almond Paste)',
                        'Mutton Pulao with Fried Onions',
                        'Crispy Fried Fish with Tartar Sauce',
                        'Live Tawa Naan & Kalonji Kulcha',
                        'Shahi Tukray & Pistachio Kulfi',
                        'Russian Salad & Fresh Garden Greens',
                        'Gourmet Kashmiri Chai with Crushed Almonds'
                    ],
                    'is_active' => true,
                ],
                [
                    'name' => 'Classic Festive Silver Menu',
                    'description' => 'Traditional high-quality wedding banquet ideal for elegant family dinners and mehendi nights.',
                    'per_head_price' => 1950.00,
                    'min_guests' => 150,
                    'menu_items_json' => [
                        'Traditional Chicken Qorma Degi',
                        'Chicken Sindhi Biryani',
                        'Crispy Vegetable Spring Rolls',
                        'Roghni Tandoori Naan',
                        'Mutanjan Zarda with Sweet Khoya',
                        'Zeera Raita & Kachumber Salad',
                        'Chilled Soft Drinks'
                    ],
                    'is_active' => true,
                ]
            ];

            foreach ($defaultPackages as $pkg) {
                Package::create(array_merge($pkg, ['tenant_id' => $tenantId]));
            }
        }
    }

    // ==========================================
    // MODAL ACTIONS
    // ==========================================
    public function openCreateModal()
    {
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
            'Fresh Mint Raita & Salad'
        ];
        $this->newMenuItemName = '';
        $this->feedbackMessage = null;
        $this->errorMessage = null;
        $this->showPackageModal = true;
    }

    public function openEditModal(string $packageId)
    {
        $user = Auth::user();
        $package = Package::where('tenant_id', $user->tenant_id)->findOrFail($packageId);

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
        $item = trim($this->newMenuItemName);
        if ($item === '') {
            return;
        }

        if (!in_array($item, $this->menuItems, true)) {
            $this->menuItems[] = $item;
        }
        $this->newMenuItemName = '';
    }

    public function removeMenuItem(int $index)
    {
        if (isset($this->menuItems[$index])) {
            unset($this->menuItems[$index]);
            $this->menuItems = array_values($this->menuItems);
        }
    }

    public function addPresetDish(string $dish)
    {
        if (!in_array($dish, $this->menuItems, true)) {
            $this->menuItems[] = $dish;
        }
    }

    // ==========================================
    // SAVE PACKAGE (CREATE / UPDATE)
    // ==========================================
    public function savePackage()
    {
        $this->validate([
            'packageName' => 'required|string|min:3|max:120',
            'perHeadPrice' => 'required|numeric|min:500',
            'minGuests' => 'required|integer|min:20',
            'packageDescription' => 'nullable|string|max:1000',
        ]);

        if (empty($this->menuItems)) {
            $this->errorMessage = "Please add at least one dish / menu item to this package.";
            return;
        }

        $user = Auth::user();
        $tenantId = $user->tenant_id;

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
        $user = Auth::user();
        $package = Package::where('tenant_id', $user->tenant_id)->findOrFail($packageId);
        $package->update([
            'is_active' => !$package->is_active,
        ]);

        $statusStr = $package->is_active ? 'Activated' : 'Archived';
        $this->feedbackMessage = "Package [{$package->name}] has been {$statusStr}.";
    }

    public function deletePackage(string $packageId)
    {
        $user = Auth::user();
        $package = Package::where('tenant_id', $user->tenant_id)->findOrFail($packageId);

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
        $user = Auth::user();
        $query = Package::where('tenant_id', $user->tenant_id);

        if ($this->filterStatus === 'ACTIVE') {
            $query->where('is_active', true);
        } elseif ($this->filterStatus === 'INACTIVE') {
            $query->where('is_active', false);
        }

        $packages = $query->latest()->get();

        $totalPackages = Package::where('tenant_id', $user->tenant_id)->count();
        $activePackages = Package::where('tenant_id', $user->tenant_id)->where('is_active', true)->count();
        $avgPrice = Package::where('tenant_id', $user->tenant_id)->where('is_active', true)->avg('per_head_price') ?? 0;

        return view('livewire.package-manager', [
            'packages' => $packages,
            'totalPackages' => $totalPackages,
            'activePackages' => $activePackages,
            'avgPrice' => $avgPrice,
        ]);
    }
}
