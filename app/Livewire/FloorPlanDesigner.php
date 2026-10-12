<?php

namespace App\Livewire;

use App\Models\FloorPlan;
use App\Models\Hall;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FloorPlanDesigner extends Component
{
    public ?string $selectedHallId = null;

    public string $planName = 'Standard Wedding Banquet Layout';

    public array $elements = [];

    public ?string $successMessage = null;

    public function mount()
    {
        $user = Auth::user();
        if ($user && $user->tenant_id) {
            $firstHall = Hall::where('tenant_id', $user->tenant_id)->where('is_active', true)->first();
        } else {
            $firstHall = null;
        }

        if ($firstHall) {
            $this->selectedHallId = $firstHall->id;
            $this->loadDefaultElements();
        }
    }

    public function loadDefaultElements()
    {
        $this->elements = [
            ['id' => 'stage-1', 'type' => 'STAGE', 'name' => 'Royal Bride & Groom Stage', 'seats' => 6, 'x' => 350, 'y' => 30],
            ['id' => 'buffet-1', 'type' => 'BUFFET', 'name' => 'Main Buffet Counter Line', 'seats' => 0, 'x' => 50, 'y' => 450],
            ['id' => 'tbl-1', 'type' => 'TABLE_10', 'name' => 'Table A1', 'seats' => 10, 'x' => 120, 'y' => 140],
            ['id' => 'tbl-2', 'type' => 'TABLE_10', 'name' => 'Table A2', 'seats' => 10, 'x' => 280, 'y' => 140],
            ['id' => 'tbl-3', 'type' => 'TABLE_10', 'name' => 'Table A3', 'seats' => 10, 'x' => 440, 'y' => 140],
            ['id' => 'tbl-4', 'type' => 'TABLE_10', 'name' => 'Table A4', 'seats' => 10, 'x' => 600, 'y' => 140],
            ['id' => 'tbl-5', 'type' => 'TABLE_8', 'name' => 'Table B1', 'seats' => 8, 'x' => 120, 'y' => 260],
            ['id' => 'tbl-6', 'type' => 'TABLE_8', 'name' => 'Table B2', 'seats' => 8, 'x' => 280, 'y' => 260],
            ['id' => 'tbl-7', 'type' => 'TABLE_8', 'name' => 'Table B3', 'seats' => 8, 'x' => 440, 'y' => 260],
            ['id' => 'tbl-8', 'type' => 'TABLE_8', 'name' => 'Table B4', 'seats' => 8, 'x' => 600, 'y' => 260],
            ['id' => 'sofa-1', 'type' => 'VIP_SOFA', 'name' => 'VIP Family Sofa 1', 'seats' => 8, 'x' => 240, 'y' => 80],
            ['id' => 'sofa-2', 'type' => 'VIP_SOFA', 'name' => 'VIP Family Sofa 2', 'seats' => 8, 'x' => 480, 'y' => 80],
        ];
    }

    public function addElement(string $type)
    {
        $id = uniqid('el_');
        $seats = 0;
        $name = 'Element';

        if ($type === 'TABLE_10') {
            $seats = 10;
            $name = '10-Seater Round Table';
        } elseif ($type === 'TABLE_8') {
            $seats = 8;
            $name = '8-Seater Round Table';
        } elseif ($type === 'VIP_SOFA') {
            $seats = 8;
            $name = 'VIP Luxury Sofa Lounge';
        } elseif ($type === 'STAGE') {
            $seats = 4;
            $name = 'Bridal Stage Podium';
        } elseif ($type === 'BUFFET') {
            $seats = 0;
            $name = 'Buffet Serving Line';
        }

        $this->elements[] = [
            'id' => $id,
            'type' => $type,
            'name' => $name,
            'seats' => $seats,
            'x' => 200 + (count($this->elements) * 15) % 400,
            'y' => 200 + (count($this->elements) * 15) % 300,
        ];
    }

    public function removeElement(string $id)
    {
        $this->elements = array_values(array_filter($this->elements, fn ($el) => $el['id'] !== $id));
    }

    public function saveFloorPlan()
    {
        $user = Auth::user();
        $hall = Hall::where('id', $this->selectedHallId)
            ->when($user && $user->tenant_id, fn ($q) => $q->where('tenant_id', $user->tenant_id))
            ->first();

        if (! $hall) {
            abort(403, 'Unauthorized hall access.');
        }

        $totalSeats = array_sum(array_column($this->elements, 'seats'));

        FloorPlan::updateOrCreate(
            [
                'hall_id' => $hall->id,
                'name' => $this->planName,
            ],
            [
                'tenant_id' => $hall->tenant_id,
                'layout_data' => $this->elements,
                'total_seats' => $totalSeats,
                'is_template' => true,
            ]
        );

        $this->successMessage = "Floor Plan saved successfully with {$totalSeats} capacity seats!";
    }

    public function render()
    {
        $user = Auth::user();
        if ($user && $user->tenant_id) {
            $halls = Hall::where('tenant_id', $user->tenant_id)->where('is_active', true)->get();
        } else {
            $halls = collect([]);
        }

        $totalSeats = array_sum(array_column($this->elements, 'seats'));

        return view('livewire.floor-plan-designer', [
            'halls' => $halls,
            'totalSeats' => $totalSeats,
        ]);
    }
}
