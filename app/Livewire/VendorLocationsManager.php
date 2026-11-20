<?php

namespace App\Livewire;

use App\Models\VendorLocation;
use App\Support\PermissionMatrix;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Vendor ERP — Multi-location business manager.
 *
 * A business may operate several branches. Every action is server-side
 * authorised (`locations.manage`) and strictly scoped to the signed-in
 * vendor's tenant.
 */
#[Layout('components.layouts.app', ['title' => 'Business Locations — Vendor ERP'])]
class VendorLocationsManager extends Component
{
    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public bool $showLocationModal = false;

    public bool $isEditing = false;

    public ?string $editingLocationId = null;

    public string $locationName = '';

    public string $branchType = 'BRANCH';

    public string $address = '';

    public string $area = '';

    public string $city = '';

    public string $province = '';

    public string $phone = '';

    public string $email = '';

    public bool $isPrimary = false;

    public bool $isActive = true;

    public string $notes = '';

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->tenant_id || ! PermissionMatrix::userCan($user, 'locations.view')) {
            abort(403, 'Unauthorized. Location management is restricted to vendor staff.');
        }
    }

    public function hasPortalPermission(string $permission): bool
    {
        return PermissionMatrix::userCan(Auth::user(), $permission);
    }

    private function assertCan(string $permission): void
    {
        abort_unless($this->hasPortalPermission($permission), 403, 'Unauthorized. Missing permission: '.$permission);
    }

    public function getLocationsProperty()
    {
        return VendorLocation::query()
            ->where('tenant_id', Auth::user()->tenant_id)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get();
    }

    public function getLocationCountProperty(): int
    {
        return VendorLocation::query()
            ->where('tenant_id', Auth::user()->tenant_id)
            ->where('is_active', true)
            ->count();
    }

    public function openCreateModal(): void
    {
        $this->assertCan('locations.manage');

        $this->reset(['locationName', 'branchType', 'address', 'area', 'city', 'province', 'phone', 'email', 'isPrimary', 'notes']);
        $this->branchType = 'BRANCH';
        $this->isActive = true;
        $this->isEditing = false;
        $this->editingLocationId = null;
        $this->showLocationModal = true;
        $this->errorMessage = null;
    }

    public function openEditModal(string $locationId): void
    {
        $this->assertCan('locations.manage');

        $location = $this->findLocation($locationId);

        if (! $location) {
            $this->errorMessage = 'That location is no longer available.';

            return;
        }

        $this->locationName = (string) $location->name;
        $this->branchType = (string) $location->branch_type;
        $this->address = (string) ($location->address ?? '');
        $this->area = (string) ($location->area ?? '');
        $this->city = (string) ($location->city ?? '');
        $this->province = (string) ($location->province ?? '');
        $this->phone = (string) ($location->phone ?? '');
        $this->email = (string) ($location->email ?? '');
        $this->isPrimary = (bool) $location->is_primary;
        $this->isActive = (bool) $location->is_active;
        $this->notes = (string) ($location->notes ?? '');

        $this->isEditing = true;
        $this->editingLocationId = $locationId;
        $this->showLocationModal = true;
        $this->errorMessage = null;
    }

    public function closeModal(): void
    {
        $this->showLocationModal = false;
        $this->isEditing = false;
        $this->editingLocationId = null;
    }

    public function saveLocation(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->tenant_id) {
            abort(403);
        }

        $this->assertCan('locations.manage');

        $this->validate([
            'locationName' => ['required', 'string', 'max:150'],
            'branchType' => ['required', 'in:'.implode(',', VendorLocation::BRANCH_TYPES)],
            'address' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:80'],
            'province' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $tenantId = $user->tenant_id;

        DB::transaction(function () use ($tenantId) {
            $payload = [
                'tenant_id' => $tenantId,
                'name' => trim($this->locationName),
                'branch_type' => $this->branchType,
                'address' => $this->address ?: null,
                'area' => $this->area ?: null,
                'city' => $this->city ?: null,
                'province' => $this->province ?: null,
                'phone' => $this->phone ?: null,
                'email' => $this->email ?: null,
                'is_primary' => $this->isPrimary,
                'is_active' => $this->isActive,
                'notes' => $this->notes ?: null,
            ];

            if ($this->isEditing) {
                $location = $this->findLocation((string) $this->editingLocationId);

                if (! $location) {
                    throw new \RuntimeException('Location not found.');
                }

                $location->update($payload);
            } else {
                $payload['slug'] = VendorLocation::generateSlug($this->locationName, $tenantId);
                $location = VendorLocation::create($payload);
            }

            // Exactly one primary location per business — every other
            // location of this tenant is demoted.
            if ($this->isPrimary) {
                VendorLocation::query()
                    ->where('tenant_id', $tenantId)
                    ->where('id', '!=', $location->id)
                    ->update(['is_primary' => false]);
            }
        });

        $this->closeModal();
        $this->feedbackMessage = $this->isEditing
            ? 'Location updated successfully.'
            : 'Location added successfully.';
    }

    public function toggleActive(string $locationId): void
    {
        $user = Auth::user();

        if (! $user || ! $user->tenant_id) {
            abort(403);
        }

        $this->assertCan('locations.manage');

        $location = $this->findLocation($locationId);

        if (! $location) {
            $this->errorMessage = 'That location is no longer available.';

            return;
        }

        $location->update(['is_active' => ! $location->is_active]);

        $this->feedbackMessage = $location->is_active
            ? 'Location re-activated.'
            : 'Location deactivated — it is hidden from customers.';
    }

    private function findLocation(string $locationId): ?VendorLocation
    {
        return VendorLocation::query()
            ->where('tenant_id', Auth::user()->tenant_id)
            ->where('id', $locationId)
            ->first();
    }

    public function render()
    {
        return view('livewire.vendor-locations-manager')
            ->layout('components.layouts.app', [
                'title' => 'Business Locations — Vendor ERP',
            ]);
    }
}
