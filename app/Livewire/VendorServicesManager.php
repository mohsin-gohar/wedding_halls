<?php

namespace App\Livewire;

use App\Models\VendorCategory;
use App\Models\VendorService;
use App\Support\PermissionMatrix;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Vendor ERP — Service catalog manager (Phase 2).
 *
 * The vendor owner/manager maintains their own service catalog with
 * pricing_type + price_from/price_to + unit. Services power the public
 * marketplace profile and become bookables via the polymorphic engine.
 */
#[Layout('components.layouts.app', ['title' => 'Service Catalog — Vendor ERP'])]
class VendorServicesManager extends Component
{
    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public bool $showServiceModal = false;

    public bool $isEditing = false;

    public ?string $editingServiceId = null;

    public string $serviceName = '';

    public string $serviceDescription = '';

    public string $pricingType = 'FIXED';

    public ?float $priceFrom = null;

    public ?float $priceTo = null;

    public string $serviceUnit = 'per event';

    public ?int $vendorCategoryId = null;

    public array $categories = [];

    public array $services = [];

    public int $serviceCount = 0;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! PermissionMatrix::userCan($user, 'services.view')) {
            abort(403, 'Unauthorized. Service catalog access is restricted to vendor staff.');
        }

        $this->categories = VendorCategory::query()->where('is_active', true)->orderBy('sort')->get(['id', 'name'])->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->all();

        $tenantId = Auth::user()->tenant_id;

        if (! $tenantId) {
            abort(403, 'Forbidden. You do not have an active vendor organization assigned.');
        }

        $this->services = VendorService::query()
            ->where('tenant_id', $tenantId)
            ->with('vendorCategory:id,name')
            ->orderByDesc('created_at')
            ->get(['id', 'tenant_id', 'vendor_category_id', 'name', 'description', 'pricing_type', 'price_from', 'price_to', 'unit', 'is_active'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'tenant_id' => $s->tenant_id,
                'vendor_category_id' => $s->vendor_category_id,
                'name' => $s->name,
                'description' => $s->description,
                'pricing_type' => $s->pricing_type,
                'price_from' => $s->price_from,
                'price_to' => $s->price_to,
                'unit' => $s->unit,
                'is_active' => $s->is_active,
                'vendorCategory' => $s->vendorCategory,
            ])
            ->values()
            ->toArray();

        $this->serviceCount = VendorService::query()->where('tenant_id', $tenantId)->count();
    }

    public function hasPortalPermission(string $permission): bool
    {
        return PermissionMatrix::userCan(Auth::user(), $permission);
    }

    private function assertCan(string $permission): void
    {
        abort_unless($this->hasPortalPermission($permission), 403, 'Unauthorized. Missing permission: '.$permission);
    }

    public function openCreateModal(): void
    {
        $this->assertCan('services.manage');

        $this->isEditing = false;
        $this->editingServiceId = null;
        $this->serviceName = '';
        $this->serviceDescription = '';
        $this->pricingType = 'FIXED';
        $this->priceFrom = null;
        $this->priceTo = null;
        $this->serviceUnit = 'per event';
        $this->vendorCategoryId = null;
        $this->showServiceModal = true;
        $this->errorMessage = null;
    }

    public function openEditModal(string $serviceId): void
    {
        $this->assertCan('services.manage');

        $tenantId = Auth::user()->tenant_id;

        if (! $tenantId) {
            abort(403);
        }

        $service = VendorService::query()->where('tenant_id', $tenantId)->find($serviceId);

        if (! $service) {
            abort(404);
        }

        $this->isEditing = true;
        $this->editingServiceId = (string) $service->id;
        $this->serviceName = (string) $service->name;
        $this->serviceDescription = (string) ($service->description ?? '');
        $this->pricingType = (string) $service->pricing_type;
        $this->priceFrom = $service->price_from !== null ? (float) $service->price_from : null;
        $this->priceTo = $service->price_to !== null ? (float) $service->price_to : null;
        $this->serviceUnit = (string) ($service->unit ?? 'per event');
        $this->vendorCategoryId = $service->vendor_category_id !== null ? (int) $service->vendor_category_id : null;
        $this->showServiceModal = true;
        $this->errorMessage = null;
    }

    public function closeServiceModal(): void
    {
        $this->showServiceModal = false;
        $this->editingServiceId = null;
        $this->serviceName = '';
        $this->serviceDescription = '';
        $this->pricingType = 'FIXED';
        $this->priceFrom = null;
        $this->priceTo = null;
        $this->serviceUnit = 'per event';
        $this->vendorCategoryId = null;
    }

    public function saveService(): void
    {
        $this->assertCan('services.manage');

        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        $tenantId = $user->tenant_id;

        if (! $tenantId) {
            abort(403);
        }

        if ($this->serviceName === '') {
            $this->addError('serviceName', 'Service name is required.');

            return;
        }

        if (! in_array($this->pricingType, ['FIXED', 'PER_HEAD', 'HOURLY', 'DAY', 'PROJECT', 'CONSULT'], true)) {
            $this->addError('pricingType', 'Invalid pricing type.');

            return;
        }

        DB::transaction(function () use ($tenantId) {
            if ($this->isEditing) {
                $service = VendorService::query()->where('tenant_id', $tenantId)->find($this->editingServiceId);

                if (! $service) {
                    abort(404);
                }

                $service->update([
                    'vendor_category_id' => $this->vendorCategoryId,
                    'name' => $this->serviceName,
                    'description' => $this->serviceDescription ?: null,
                    'pricing_type' => $this->pricingType,
                    'price_from' => $this->priceFrom,
                    'price_to' => $this->priceTo,
                    'unit' => $this->serviceUnit,
                ]);

                $this->feedbackMessage = 'Service updated successfully.';
            } else {
                VendorService::create([
                    'tenant_id' => $tenantId,
                    'vendor_category_id' => $this->vendorCategoryId,
                    'name' => $this->serviceName,
                    'description' => $this->serviceDescription ?: null,
                    'pricing_type' => $this->pricingType,
                    'price_from' => $this->priceFrom,
                    'price_to' => $this->priceTo,
                    'unit' => $this->serviceUnit,
                    'is_active' => true,
                ]);

                $this->feedbackMessage = 'Service created successfully.';
            }
        });

        $this->closeServiceModal();
    }

    public function toggleService(string $serviceId): void
    {
        $this->assertCan('services.manage');

        $tenantId = Auth::user()->tenant_id;

        if (! $tenantId) {
            abort(403);
        }

        $service = VendorService::query()->where('tenant_id', $tenantId)->find($serviceId);

        if (! $service) {
            abort(404);
        }

        $service->update(['is_active' => ! $service->is_active]);

        $this->feedbackMessage = $service->fresh()->is_active ? 'Service activated.' : 'Service deactivated.';
    }

    public function render()
    {
        return view('livewire.vendor-services-manager')
            ->layout('components.layouts.app', [
                'title' => 'Service Catalog — Vendor ERP',
            ]);
    }
}
