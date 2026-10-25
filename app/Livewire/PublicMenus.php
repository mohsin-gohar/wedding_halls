<?php

namespace App\Livewire;

use App\Models\Package;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public', ['title' => 'Catering Packages & Menus — EventHub Pakistan'])]
class PublicMenus extends Component
{
    public int $calculatorGuests = 450;

    public ?string $selectedPackageId = null;

    public function mount()
    {
        $first = Package::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('tenant_id')
                    ->orWhereHas('tenant', fn ($q) => $q->where('is_active', true)->where('verification_status', 'APPROVED'));
            })
            ->first();
        if ($first) {
            $this->selectedPackageId = $first->id;
        }
    }

    public function render()
    {
        $packages = Package::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('tenant_id')
                    ->orWhereHas('tenant', fn ($q) => $q->where('is_active', true)->where('verification_status', 'APPROVED'));
            })
            ->get();
        $selectedPackage = Package::find($this->selectedPackageId) ?? $packages->first();

        // Calculate live estimates based on Bawarchi formulas
        $estimatedFoodTotal = $selectedPackage ? ((float) $selectedPackage->per_head_price * $this->calculatorGuests) : 0;
        $muttonKg = round($this->calculatorGuests * 0.12, 1);
        $riceKg = round($this->calculatorGuests * 0.16, 1);
        $oilLiters = round($this->calculatorGuests * 0.025, 1);

        return view('livewire.public-menus', [
            'packages' => $packages,
            'selectedPackage' => $selectedPackage,
            'estimatedFoodTotal' => $estimatedFoodTotal,
            'muttonKg' => $muttonKg,
            'riceKg' => $riceKg,
            'oilLiters' => $oilLiters,
        ]);
    }
}
