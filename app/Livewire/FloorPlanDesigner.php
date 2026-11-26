<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\FloorPlan;
use App\Models\Hall;
use App\Models\Tenant;
use App\Support\PermissionMatrix;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class FloorPlanDesigner extends Component
{
    /** Server-side schema for layout_data elements. */
    public const ELEMENT_TYPES = ['TABLE_10', 'TABLE_8', 'ROUND_TABLE', 'VIP_SOFA', 'STAGE', 'BUFFET'];

    public ?string $selectedHallId = null;

    public ?string $selectedBookingId = null;

    public ?string $selectedTenantId = null;

    public string $planName = 'Standard Wedding Banquet Layout';

    public array $elements = [];

    public bool $isTemplate = false;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount()
    {
        $this->authorizeDesignAction('plan.view');

        $tenantId = $this->resolvedTenantId();
        $firstHall = $tenantId
            ? Hall::where('tenant_id', $tenantId)->where('is_active', true)->first()
            : null;

        if ($firstHall) {
            $this->selectedHallId = $firstHall->id;
            $this->loadDefaultElements();
        }
    }

    private function authorizeDesignAction(string $action): void
    {
        $user = Auth::user();
        if (! $user || $user->isCustomer() || (! $user->isSuperAdmin() && ! $user->tenant_id)) {
            abort(403, 'Unauthorized. Tenant staff access required.');
        }

        if ($action === 'plan.select-tenant') {
            abort_unless($user->isSuperAdmin(), 403, 'Only platform admins may switch tenant context.');

            return;
        }

        $permission = match ($action) {
            'plan.view' => 'floor-plans.view',
            'plan.edit', 'plan.save' => 'floor-plans.manage',
            default => null,
        };

        abort_unless($permission && PermissionMatrix::userCan($user, $permission), 403, 'Missing floor-plan permission.');
    }

    /** Read context: null for a platform admin until a tenant is chosen. */
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

    private function targetTenantId(): string
    {
        $tenantId = $this->resolvedTenantId();
        if (! $tenantId) {
            abort(403, 'Select a tenant before managing its floor plans.');
        }

        return $tenantId;
    }

    /** Server-side lock: a client cannot clear the legacy-plan save guard. */
    private function legacyPlanLockKey(string $tenantId): string
    {
        return 'floor-plan-legacy-lock:'.$tenantId.':'.Auth::id();
    }

    public function selectTenant(string $tenantId): void
    {
        $this->authorizeDesignAction('plan.select-tenant');

        validator(['tenantId' => $tenantId], [
            'tenantId' => ['required', Rule::exists('tenants', 'id')],
        ])->validate();

        $this->selectedTenantId = $tenantId;
        $this->selectedHallId = null;
        $this->selectedBookingId = null;
        $this->elements = [];
        $this->successMessage = null;
        $this->errorMessage = null;
        session()->forget($this->legacyPlanLockKey($tenantId));
    }

    public function updatedSelectedHallId(): void
    {
        // A booking from another tenant/hall must not survive a hall switch.
        if ($this->selectedBookingId && $this->selectedHallId) {
            $tenantId = $this->resolvedTenantId();
            $booking = $tenantId
                ? Booking::where('tenant_id', $tenantId)->whereKey($this->selectedBookingId)->first()
                : null;
            if (! $booking || $booking->hall_id !== $this->selectedHallId) {
                $this->selectedBookingId = null;
            }
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
        $this->authorizeDesignAction('plan.edit');

        validator(['type' => $type], [
            'type' => ['required', Rule::in(self::ELEMENT_TYPES)],
        ])->validate();

        $seatMap = [
            'TABLE_10' => 10,
            'TABLE_8' => 8,
            'ROUND_TABLE' => 10,
            'VIP_SOFA' => 8,
            'STAGE' => 4,
            'BUFFET' => 0,
        ];
        $nameMap = [
            'TABLE_10' => '10-Seater Round Table',
            'TABLE_8' => '8-Seater Round Table',
            'ROUND_TABLE' => 'Round Banquet Table',
            'VIP_SOFA' => 'VIP Luxury Sofa Lounge',
            'STAGE' => 'Bridal Stage Podium',
            'BUFFET' => 'Buffet Serving Line',
        ];

        $this->elements[] = [
            'id' => 'el_'.Str::lower(Str::random(8)),
            'type' => $type,
            'name' => $nameMap[$type],
            'seats' => $seatMap[$type],
            'x' => 200 + (count($this->elements) * 15) % 400,
            'y' => 200 + (count($this->elements) * 15) % 300,
        ];
    }

    public function removeElement(string $id)
    {
        $this->authorizeDesignAction('plan.edit');
        $this->elements = array_values(array_filter($this->elements, fn ($el) => is_array($el) && ($el['id'] ?? null) !== $id));
    }

    /** Strict validation of the JSON schema persisted to floor_plans.layout_data. */
    private function validatedElements(): array
    {
        $validated = [];
        $errors = [];

        foreach ($this->elements as $index => $element) {
            if (! is_array($element)) {
                $errors["elements.{$index}"] = 'Each layout element must be an array.';

                continue;
            }

            $validator = validator($element, [
                'id' => 'required|string|max:64',
                'type' => ['required', Rule::in(self::ELEMENT_TYPES)],
                'name' => 'required|string|max:120',
                'seats' => 'required|integer|min:0|max:500',
                'x' => 'required|numeric|min:0|max:10000',
                'y' => 'required|numeric|min:0|max:10000',
            ]);

            if ($validator->fails()) {
                $errors["elements.{$index}"] = implode(' ', $validator->errors()->all());

                continue;
            }

            $data = $validator->validated();
            $validated[] = [
                'id' => $data['id'],
                'type' => $data['type'],
                'name' => $data['name'],
                'seats' => (int) $data['seats'],
                'x' => (float) $data['x'],
                'y' => (float) $data['y'],
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $validated;
    }

    public function saveFloorPlan()
    {
        $this->authorizeDesignAction('plan.save');
        $tenantId = $this->targetTenantId();

        if (session()->get($this->legacyPlanLockKey($tenantId))) {
            $this->addError('elements', 'Save is disabled for this plan: its stored layout has legacy elements that cannot be normalised.');
            $this->errorMessage = 'Cannot save a loaded legacy layout as an empty plan. Review the stored record before overwriting it.';
            $this->successMessage = null;

            return;
        }

        $this->validate([
            'selectedHallId' => ['required', 'string', Rule::exists('halls', 'id')->where('tenant_id', $tenantId)->where('is_active', true)],
            'selectedBookingId' => ['nullable', 'string', Rule::exists('bookings', 'id')->where('tenant_id', $tenantId)->where('hall_id', $this->selectedHallId)],
            'planName' => 'required|string|min:2|max:120',
            'isTemplate' => 'boolean',
            'elements' => 'required|array|min:1|max:300',
        ]);

        $elements = $this->validatedElements();

        $hall = Hall::where('tenant_id', $tenantId)->where('is_active', true)->findOrFail($this->selectedHallId);
        $totalSeats = array_sum(array_column($elements, 'seats'));

        $plan = FloorPlan::updateOrCreate(
            [
                'hall_id' => $hall->id,
                'name' => $this->planName,
                'booking_id' => $this->selectedBookingId,
            ],
            [
                'tenant_id' => $tenantId,
                'layout_data' => $elements,
                'total_seats' => $totalSeats,
                'is_template' => $this->isTemplate,
            ]
        );

        AuditLog::log(
            'FLOOR_PLAN_SAVED',
            "Floor plan [{$plan->name}] saved for hall [{$hall->name}] with {$totalSeats} seats".($this->selectedBookingId ? ' (booking-bound).' : '.'),
            $tenantId,
            'FloorPlan',
            $plan->id
        );

        $this->successMessage = "Floor Plan saved successfully with {$totalSeats} capacity seats!";
        $this->errorMessage = null;
    }

    public function loadFloorPlan(string $planId): void
    {
        $this->authorizeDesignAction('plan.view');
        $tenantId = $this->targetTenantId();

        $plan = FloorPlan::where('tenant_id', $tenantId)->findOrFail($planId);

        $this->selectedHallId = $plan->hall_id;
        $this->selectedBookingId = $plan->booking_id;
        $this->planName = $plan->name;
        $this->isTemplate = (bool) $plan->is_template;
        $lockKey = $this->legacyPlanLockKey($tenantId);

        try {
            $this->elements = $this->validatedElementsFrom((array) ($plan->layout_data ?? []));
            session()->forget($lockKey);
            $this->errorMessage = null;
            $this->successMessage = "Loaded saved plan [{$plan->name}].";
        } catch (ValidationException) {
            // Keep the stored legacy layout untouched in the database and lock
            // saving so an empty editor state cannot overwrite it.
            $this->elements = [];
            session()->put($lockKey, $plan->id);
            $this->errorMessage = 'This saved plan contains legacy elements that no longer match the layout schema. Review before saving.';
            $this->successMessage = null;
        }
    }

    private function validatedElementsFrom(array $elements): array
    {
        $original = $this->elements;
        $this->elements = $elements;

        try {
            return $this->validatedElements();
        } finally {
            $this->elements = $original;
        }
    }

    public function render()
    {
        $this->authorizeDesignAction('plan.view');
        $tenantId = $this->resolvedTenantId();

        $halls = $tenantId
            ? Hall::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get()
            : collect([]);

        $bookings = $tenantId
            ? Booking::where('tenant_id', $tenantId)
                ->whereIn('status', ['HOLD', 'CONFIRMED'])
                ->orderByDesc('event_date')
                ->limit(200)
                ->get(['id', 'hall_id', 'customer_name', 'event_date', 'event_type'])
            : collect([]);

        $savedPlans = $tenantId
            ? FloorPlan::where('tenant_id', $tenantId)
                ->when($this->selectedHallId, fn ($query) => $query->where('hall_id', $this->selectedHallId))
                ->latest()
                ->limit(100)
                ->get()
            : collect([]);

        $tenantOptions = Auth::user()->isSuperAdmin()
            ? Tenant::query()->orderBy('name')->limit(200)->get(['id', 'name'])
            : collect([]);

        $totalSeats = array_sum(array_column($this->elements, 'seats'));

        return view('livewire.floor-plan-designer', [
            'halls' => $halls,
            'bookings' => $bookings,
            'savedPlans' => $savedPlans,
            'tenantOptions' => $tenantOptions,
            'totalSeats' => $totalSeats,
        ]);
    }
}
