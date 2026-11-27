<?php

namespace App\Livewire;

use App\Exceptions\SlotAlreadyBookedException;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\MenuItem;
use App\Models\Package;
use App\Services\BookingService;
use App\Support\PermissionMatrix;
use App\Support\ShiftWindows;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Eager;
use Livewire\Component;

#[Eager]
class WalkInBookingModal extends Component
{
    private const DEFAULT_ADDON_PRICES = [
        'sparklers' => 15000.00,
        'dj_lighting' => 30000.00,
        'ac_extra' => 20000.00,
        'valet' => 12000.00,
        'stage_flowers' => 35000.00,
    ];

    public bool $isOpen = false;

    public int $step = 1;

    // Mode flag: staff terminal vs public inquiry
    public bool $isStaff = false;

    // Step 1: Venue & Slot
    public ?string $hall_id = null;

    public string $event_date = '';

    public string $slot_type = 'EVENING';

    public string $event_type = 'Barat';

    public bool $isSlotAvailable = true;

    public ?string $slotMessage = null;

    // Step 2: Client Profile
    public string $customer_phone = '';

    public string $customer_name = '';

    public ?string $customer_cnic = '';

    public ?string $customer_email = '';

    public bool $clientFound = false;

    // Step 3: Catering & Financials
    public int $guest_count = 250;

    public ?string $package_id = null;

    public float $per_head_rate = 1850.00;

    public float $hall_rent = 120000.00;

    public array $addonPrices = self::DEFAULT_ADDON_PRICES;

    public array $selected_addons = [];

    public float $addons_total = 0.00;

    public float $discount_amount = 0.00;

    public float $tax_rate_percent = 5.00; // 5% provincial service tax

    public float $tax_amount = 0.00;

    public float $grand_total = 0.00;

    // Step 4: Advance Deposit (Staff terminal only)
    public float $advance_paid = 50000.00;

    public float $balance_due = 0.00;

    public string $payment_method = 'CASH';

    public ?string $transaction_reference = null;

    public ?string $notes = null;

    // Created booking reference
    public ?Booking $createdBooking = null;

    public ?string $errorMessage = null;

    protected $listeners = [
        'openWalkInModal' => 'openModalWithData',
        'open-walk-in-modal' => 'openModalWithData',
    ];

    public function mount()
    {
        $this->event_date = Carbon::tomorrow()->format('Y-m-d');
        $this->determineStaffMode();
        $this->initDefaultHall();
        $this->calculateTotals();
    }

    protected function determineStaffMode(): void
    {
        $user = Auth::user();

        // Single source of truth for venue-side users (includes floor staff).
        $this->isStaff = (bool) ($user?->isVenueOperationsStaff()
            && (PermissionMatrix::userCan($user, 'bookings.create')
                || PermissionMatrix::userCan($user, 'bookings.manage')));
    }

    protected function initDefaultHall(): void
    {
        $user = Auth::user();

        if (! $user || ! $this->isStaff) {
            // Public guest browsing the marketplace: only centrally visible halls.
            $firstHall = Hall::publicVisible()->with('tenant')->first();
        } elseif ($user->tenant_id) {
            // Tenant staff (Owner / Manager / Front Desk, or a Super Admin assigned to a
            // business): strictly scope the walk-in terminal to the operator's own tenant.
            // Never silently preselect another business's hall.
            $firstHall = Hall::where('tenant_id', $user->tenant_id)->where('is_active', true)->first();
        } else {
            // Platform-wide Super Admin with no assigned tenant: require an explicit venue
            // selection instead of defaulting to an arbitrary hall.
            $firstHall = null;
        }

        if ($firstHall) {
            $this->hall_id = $firstHall->id;
            $this->hall_rent = (float) $firstHall->base_rent;
        }
    }

    /**
     * Resolve halls only within the current public or staff tenant context.
     */
    private function hallQueryForCurrentUser()
    {
        $user = Auth::user();
        $query = $this->isStaff ? Hall::query() : Hall::publicVisible();

        if ($this->isStaff && ! $user?->isSuperAdmin()) {
            $query->where('tenant_id', $user?->tenant_id);
        }

        return $query;
    }

    public function openModalWithData(array $payload = [])
    {
        $this->resetErrorBag();
        $this->errorMessage = null;
        $this->createdBooking = null;
        $this->step = 1;
        $this->determineStaffMode();
        $this->isOpen = true;

        if (! empty($payload['hall_id'])) {
            $hall = $this->hallQueryForCurrentUser()->with('tenant')->find($payload['hall_id']);
            if ($hall) {
                // If staff mode, ensure hall belongs to tenant
                $user = Auth::user();
                if ($this->isStaff && ! $user->isSuperAdmin() && $hall->tenant_id !== $user->tenant_id) {
                    $this->errorMessage = 'Unauthorized venue space selected.';

                    return;
                }
                $this->hall_id = $hall->id;
                $this->hall_rent = (float) $hall->base_rent;
            }
        } elseif (! $this->hall_id) {
            $this->initDefaultHall();
        }

        if (! empty($payload['event_date'])) {
            $this->event_date = $payload['event_date'];
        }
        if (! empty($payload['slot_type'])) {
            $this->slot_type = strtoupper($payload['slot_type']);
        }
        if (! empty($payload['package_id'])) {
            $selectedHall = $this->hall_id ? $this->hallQueryForCurrentUser()->find($this->hall_id) : null;
            $pkg = $this->scopedPackage(
                (string) $payload['package_id'],
                (string) ($selectedHall?->tenant_id ?? '')
            );

            if ($pkg) {
                $this->package_id = $pkg->id;
                $this->per_head_rate = (float) $pkg->per_head_price;
            } else {
                $this->package_id = null;
                $this->errorMessage = 'Selected package is not available for this venue.';
            }
        }

        $this->checkAvailability();
        $this->calculateTotals();
    }

    public function updatedHallId($value)
    {
        $hall = $this->hallQueryForCurrentUser()->with('tenant')->find($value);
        if ($hall) {
            $user = Auth::user();
            if ($this->isStaff && ! $user->isSuperAdmin() && $hall->tenant_id !== $user->tenant_id) {
                $this->errorMessage = 'Unauthorized venue space.';
                $this->hall_id = null;
                $this->hall_rent = 0.0;

                return;
            }
            $this->hall_rent = (float) $hall->base_rent;
        }
        $this->checkAvailability();
        $this->calculateTotals();
    }

    public function updatedEventDate()
    {
        $this->checkAvailability();
    }

    public function updatedSlotType()
    {
        $this->checkAvailability();
    }

    public function checkAvailability()
    {
        if (! $this->hall_id || ! $this->event_date || ! $this->slot_type) {
            return;
        }

        $exists = ! app(BookingService::class)->checkSlotAvailability(
            $this->hall_id,
            $this->event_date,
            $this->slot_type
        );

        if ($exists) {
            $this->isSlotAvailable = false;
            $this->slotMessage = 'âš ï¸ Shift is already reserved! Choose another date or shift.';
        } else {
            $this->isSlotAvailable = true;
            $this->slotMessage = 'âœ… Slot is 100% available for booking!';
        }
    }

    public function lookupClient()
    {
        $this->clientFound = false;

        // The public Livewire state is user-controlled. Recompute staff mode
        // from the authenticated account before any tenant customer lookup.
        $this->determineStaffMode();
        $user = Auth::user();

        if (! $this->isStaff || ! $user
            || (! PermissionMatrix::userCan($user, 'bookings.view')
                && ! PermissionMatrix::userCan($user, 'bookings.create')
                && ! PermissionMatrix::userCan($user, 'bookings.manage'))) {
            return;
        }
        $phone = trim($this->customer_phone);
        $cnic = trim((string) $this->customer_cnic);

        if (! $phone && ! $cnic) {
            return;
        }

        // Strict Tenant Scoping: Never leak another tenant's customer data
        $query = Booking::query();
        if (! $user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }

        if ($phone && $cnic) {
            $query->where(function ($q) use ($phone, $cnic) {
                $q->where('customer_phone', $phone)->orWhere('customer_cnic', $cnic);
            });
        } elseif ($phone) {
            $query->where('customer_phone', $phone);
        } else {
            $query->where('customer_cnic', $cnic);
        }

        $pastBooking = $query->latest()->first();

        if ($pastBooking) {
            $this->customer_name = $pastBooking->customer_name;
            $this->customer_phone = $pastBooking->customer_phone;
            $this->customer_cnic = $pastBooking->customer_cnic;
            $this->customer_email = $pastBooking->customer_email;
            $this->clientFound = true;
        }
    }

    public function updatedPackageId($packageId)
    {
        if ($packageId && $this->hall_id) {
            $hall = $this->hallQueryForCurrentUser()->find($this->hall_id);

            if ($hall) {
                $pkg = $this->scopedPackage((string) $packageId, (string) $hall->tenant_id);
                if ($pkg) {
                    $this->per_head_rate = (float) $pkg->per_head_price;
                }
            }
        }
        $this->calculateTotals();
    }

    public function updatedGuestCount()
    {
        $this->calculateTotals();
    }

    public function updatedPerHeadRate()
    {
        $this->calculateTotals();
    }

    public function updatedHallRent()
    {
        $this->calculateTotals();
    }

    public function updatedDiscountAmount()
    {
        $this->calculateTotals();
    }

    public function updatedTaxRatePercent()
    {
        $this->calculateTotals();
    }

    public function updatedAdvancePaid()
    {
        $this->calculateTotals();
    }

    public function toggleAddon(string $addonKey)
    {
        if (in_array($addonKey, $this->selected_addons)) {
            $this->selected_addons = array_values(array_diff($this->selected_addons, [$addonKey]));
        } else {
            $this->selected_addons[] = $addonKey;
        }
        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        // Addons price map (editable per-addon, so middle-class / custom setups
        // can price add-ons the way they want instead of hard-coded list prices).
        $this->addons_total = 0.00;
        foreach ($this->selected_addons as $k) {
            if (isset($this->addonPrices[$k])) {
                $this->addons_total += $this->addonPrices[$k];
            }
        }

        $catering = (float) $this->per_head_rate * (int) max(1, $this->guest_count);
        $subtotal = $catering + (float) $this->hall_rent + $this->addons_total - (float) $this->discount_amount;
        $subtotal = max(0, $subtotal);

        $this->tax_amount = round(($subtotal * (float) $this->tax_rate_percent) / 100, 2);
        $this->grand_total = round($subtotal + $this->tax_amount, 2);

        if ($this->isStaff) {
            $this->balance_due = max(0, round($this->grand_total - (float) $this->advance_paid, 2));
        } else {
            $this->advance_paid = 0.00;
            $this->balance_due = $this->grand_total;
        }
    }

    public function updatedAddonPrices()
    {
        $this->calculateTotals();
    }

    public function goToStep(int $targetStep)
    {
        $this->errorMessage = null;

        if ($targetStep === 2) {
            $this->checkAvailability();
            if (! $this->isSlotAvailable) {
                $this->errorMessage = 'Cannot proceed: Selected shift is already reserved. Pick another date or shift.';

                return;
            }

            $this->validate([
                'hall_id' => 'required|exists:halls,id',
                'event_date' => 'required|date|after_or_equal:today',
                'slot_type' => 'required|in:AFTERNOON,EVENING,NIGHT,FULL_DAY',
            ]);
        }

        if ($targetStep === 3) {
            $this->validate([
                'customer_name' => 'required|string|min:3|max:120',
                'customer_phone' => 'required|string|min:7|max:30',
                'customer_email' => 'nullable|email|max:120',
            ]);
        }

        if ($targetStep === 4) {
            $this->validate([
                'guest_count' => 'required|integer|min:20',
                'per_head_rate' => 'required|numeric|min:0',
            ]);
            $this->calculateTotals();
        }

        $this->step = $targetStep;
    }

    public function confirmBooking(BookingService $bookingService)
    {
        $this->errorMessage = null;
        $this->determineStaffMode();

        $this->validate([
            'hall_id' => 'required|exists:halls,id',
            'event_date' => 'required|date|after_or_equal:today',
            'slot_type' => 'required|in:AFTERNOON,EVENING,NIGHT,FULL_DAY',
            'customer_name' => 'required|string|min:3|max:120',
            'customer_phone' => 'required|string|min:7|max:30',
            'customer_email' => 'nullable|email|max:120',
            'guest_count' => 'required|integer|min:20',
            'advance_paid' => 'required|numeric|min:0',
            'payment_method' => 'required|in:CASH',
            'transaction_reference' => 'nullable|string|max:100',
        ]);

        $hall = $this->hallQueryForCurrentUser()->with('tenant')->findOrFail($this->hall_id);
        $user = Auth::user();

        // Server-Side Authorization & Tenant Isolation Checks
        if ($this->isStaff && ! $user->isSuperAdmin() && $hall->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized venue space for the current tenant context.');
        }

        if ($hall->max_capacity && $this->guest_count > $hall->max_capacity) {
            $this->errorMessage = "The guest count ({$this->guest_count}) exceeds the maximum hall capacity of {$hall->max_capacity}.";

            return;
        }

        // Authoritative server-side pricing: never trust client-provided rates.
        $this->hall_rent = (float) $hall->base_rent;
        $this->tax_rate_percent = 5.00;
        $this->addonPrices = self::DEFAULT_ADDON_PRICES;

        if (! $this->isStaff) {
            // Public inquiries cannot negotiate discounts or pay a deposit.
            $this->discount_amount = 0.00;
            $this->advance_paid = 0.00;
        }

        if ($this->package_id) {
            $pkg = $this->scopedPackage($this->package_id, (string) $hall->tenant_id);

            if (! $pkg) {
                $this->errorMessage = 'The selected package is not available for this venue.';

                return;
            }

            $this->per_head_rate = (float) $pkg->per_head_price;
        } elseif (! $this->isStaff) {
            $this->per_head_rate = 0.00;
        }

        $this->calculateTotals();

        if ($this->isStaff && $this->advance_paid > $this->grand_total + 0.009) {
            $this->errorMessage = 'Cash advance cannot exceed the booking total.';

            return;
        }

        // Public vs Staff distinction:
        // - Staff walk-in terminal: Creates CONFIRMED booking, collects token deposit, creates KOT.
        // - Public guest inquiry: Creates INQUIRY booking (pipeline: NEW_INQUIRY), no payment accepted, awaiting staff review.
        $bookingStatus = $this->isStaff ? 'CONFIRMED' : 'INQUIRY';
        $pipelineStage = $this->isStaff ? 'CONFIRMED' : 'NEW_INQUIRY';
        $advancePaid = $this->isStaff ? (float) $this->advance_paid : 0.00;

        $data = [
            'tenant_id' => $hall->tenant_id,
            'hall_id' => $this->hall_id,
            'package_id' => $this->package_id,
            'customer_id' => ($user && $user->isCustomer()) ? $user->id : null,
            'created_by_user_id' => $user ? $user->id : null,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_cnic' => $this->isStaff ? $this->customer_cnic : null,
            'customer_email' => $this->customer_email,
            'event_type' => $this->event_type,
            'event_date' => $this->event_date,
            'slot_type' => $this->slot_type,
            'guest_count' => $this->guest_count,
            'per_head_rate' => $this->per_head_rate,
            'hall_rent' => $this->hall_rent,
            'addons_total' => $this->addons_total,
            'discount_amount' => $this->isStaff ? $this->discount_amount : 0.00,
            'tax_amount' => $this->tax_amount,
            'grand_total' => $this->grand_total,
            'advance_paid' => $advancePaid,
            'balance_due' => max(0, $this->grand_total - $advancePaid),
            'payment_method' => $this->payment_method,
            'transaction_reference' => $this->transaction_reference,
            'status' => $bookingStatus,
            'pipeline_stage' => $pipelineStage,
            'notes' => $this->notes ?: ($this->isStaff ? 'Walk-in front desk reservation.' : 'Public customer booking inquiry.'),
            'addons_json' => $this->selected_addons,
        ];

        try {
            $this->createdBooking = $bookingService->createReservation($data);
            $this->step = 5; // Success state
            $this->dispatch('bookingCreated');
        } catch (SlotAlreadyBookedException $e) {
            $this->errorMessage = $e->getMessage();
            $this->step = 1;
        } catch (\Exception $e) {
            // Never surface raw SQL / driver text to staff or guests. Log the
            // full technical context and show a friendly, actionable message.
            Log::error('Walk-in booking failed', [
                'trace' => $e->getTraceAsString(),
                'hall_id' => $this->hall_id,
                'event_date' => $this->event_date,
                'slot_type' => $this->slot_type,
            ]);

            $this->errorMessage = 'We could not save this reservation just now. Please try again; if the problem continues, call the venue directly so our team can confirm your booked shift.';
        }
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->step = 1;
        $this->createdBooking = null;
        $this->errorMessage = null;
    }

    public function render()
    {
        $user = Auth::user();
        $this->determineStaffMode();

        $selectedHall = null;
        if ($this->hall_id) {
            $selectedHall = $this->hallQueryForCurrentUser()->with('tenant')->find($this->hall_id);
        }

        if ($this->isStaff && ! $user?->isSuperAdmin() && $user?->tenant_id) {
            // Staff terminal may use all active halls in its own tenant.
            $halls = Hall::with('tenant')
                ->where('tenant_id', $user->tenant_id)
                ->where('is_active', true)
                ->get();
            $tenantScopeId = (string) $user->tenant_id;
        } else {
            // Every guest-facing hall list uses the central visibility scope.
            $halls = Hall::publicVisible()->with('tenant')->get();
            $tenantScopeId = $selectedHall?->tenant_id ? (string) $selectedHall->tenant_id : null;
        }

        $packages = Package::where('is_active', true)
            ->where(function ($q) use ($tenantScopeId): void {
                $q->whereNull('tenant_id');
                if ($tenantScopeId) {
                    $q->orWhere('tenant_id', $tenantScopeId);
                }
            })->get();

        $menuItems = MenuItem::where('is_active', true)
            ->where(function ($q) use ($tenantScopeId): void {
                $q->whereNull('tenant_id');
                if ($tenantScopeId) {
                    $q->orWhere('tenant_id', $tenantScopeId);
                }
            })->get();

        $shiftWindows = $selectedHall?->tenant?->shift_windows;

        return view('livewire.walk-in-booking-modal', [
            'halls' => $halls,
            'packages' => $packages,
            'menuItems' => $menuItems,
            'shiftLabels' => [
                'AFTERNOON' => ShiftWindows::labelForSlot($shiftWindows, 'AFTERNOON'),
                'EVENING' => ShiftWindows::labelForSlot($shiftWindows, 'EVENING'),
                'NIGHT' => ShiftWindows::labelForSlot($shiftWindows, 'NIGHT'),
                'FULL_DAY' => ShiftWindows::labelForSlot($shiftWindows, 'FULL_DAY'),
            ],
        ]);
    }

    /**
     * A package is usable only when global or owned by the selected hall's
     * tenant. Public inquiries additionally require that tenant to pass the
     * central public-visibility scope; marketplace opt-in governs marketplace
     * listings, not an already-visible venue's own booking form.
     */
    private function scopedPackage(string $packageId, string $tenantId): ?Package
    {
        return Package::query()
            ->whereKey($packageId)
            ->where('is_active', true)
            ->where(function ($query) use ($tenantId): void {
                $query->whereNull('tenant_id')->orWhere(function ($tenantQuery) use ($tenantId): void {
                    $tenantQuery->where('tenant_id', $tenantId);
                    if (! $this->isStaff) {
                        $tenantQuery->whereHas('tenant', fn ($tenant) => $tenant->publicVisible());
                    }
                });
            })
            ->first();
    }
}
