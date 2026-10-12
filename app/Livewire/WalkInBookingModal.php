<?php

namespace App\Livewire;

use App\Exceptions\SlotAlreadyBookedException;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\MenuItem;
use App\Models\Package;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WalkInBookingModal extends Component
{
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
        $this->isStaff = $user && ($user->isTenantOwner() || $user->isManager() || $user->isFrontDesk() || $user->isSuperAdmin());
    }

    protected function initDefaultHall(): void
    {
        $user = Auth::user();

        if (! $user) {
            // Public guest browsing the marketplace: show all approved halls and
            // pre-select the first as a sensible default; the guest confirms the choice.
            $firstHall = Hall::where('is_active', true)
                ->whereHas('tenant', fn ($t) => $t->where('is_active', true)->where('verification_status', 'APPROVED'))
                ->first();
        } elseif ($this->isStaff && $user->tenant_id) {
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

    public function openModalWithData(array $payload = [])
    {
        $this->resetErrorBag();
        $this->errorMessage = null;
        $this->createdBooking = null;
        $this->step = 1;
        $this->determineStaffMode();
        $this->isOpen = true;

        if (! empty($payload['hall_id'])) {
            $hall = Hall::find($payload['hall_id']);
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
            $this->package_id = $payload['package_id'];
            $pkg = Package::find($this->package_id);
            if ($pkg) {
                $this->per_head_rate = (float) $pkg->per_head_price;
            }
        }

        $this->checkAvailability();
        $this->calculateTotals();
    }

    public function updatedHallId($value)
    {
        $hall = Hall::find($value);
        if ($hall) {
            $user = Auth::user();
            if ($this->isStaff && ! $user->isSuperAdmin() && $hall->tenant_id !== $user->tenant_id) {
                $this->errorMessage = 'Unauthorized venue space.';

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

        $exists = Booking::where('hall_id', $this->hall_id)
            ->whereDate('event_date', $this->event_date)
            ->where('slot_type', $this->slot_type)
            ->whereIn('status', ['HOLD', 'CONFIRMED'])
            ->exists();

        if ($exists) {
            $this->isSlotAvailable = false;
            $this->slotMessage = '⚠️ Shift is already reserved! Choose another date or shift.';
        } else {
            $this->isSlotAvailable = true;
            $this->slotMessage = '✅ Slot is 100% available for booking!';
        }
    }

    public function lookupClient()
    {
        $this->clientFound = false;

        // Security: Only authorized staff may perform client lookups
        if (! $this->isStaff) {
            return;
        }

        $user = Auth::user();
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
        if ($packageId) {
            $pkg = Package::find($packageId);
            if ($pkg) {
                $this->per_head_rate = (float) $pkg->per_head_price;
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

    public function toggleAddon(string $addonKey, float $cost)
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
        // Addons map
        $addonPrices = [
            'sparklers' => 15000.00,
            'dj_lighting' => 30000.00,
            'ac_extra' => 20000.00,
            'valet' => 12000.00,
            'stage_flowers' => 35000.00,
        ];

        $addons = 0.00;
        foreach ($this->selected_addons as $k) {
            if (isset($addonPrices[$k])) {
                $addons += $addonPrices[$k];
            }
        }
        $this->addons_total = $addons;

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

    public function goToStep(int $targetStep)
    {
        $this->errorMessage = null;

        if ($targetStep === 2) {
            $this->validate([
                'hall_id' => 'required|exists:halls,id',
                'event_date' => 'required|date|after_or_equal:today',
                'slot_type' => 'required|in:AFTERNOON,EVENING,NIGHT,FULL_DAY',
            ]);
            $this->checkAvailability();
            if (! $this->isSlotAvailable) {
                $this->errorMessage = 'Cannot proceed: Selected shift is already reserved.';

                return;
            }
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
        ]);

        $hall = Hall::findOrFail($this->hall_id);
        $user = Auth::user();

        // Server-Side Authorization & Tenant Isolation Checks
        if ($this->isStaff && ! $user->isSuperAdmin() && $hall->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized venue space for the current tenant context.');
        }

        // Authoritative Server-Side Base Rent Check
        $this->hall_rent = (float) $hall->base_rent;

        if ($this->package_id) {
            $pkg = Package::findOrFail($this->package_id);
            if ($this->isStaff && ! $user->isSuperAdmin() && $pkg->tenant_id && $pkg->tenant_id !== $user->tenant_id) {
                abort(403, 'Unauthorized package selection.');
            }
            $this->per_head_rate = (float) $pkg->per_head_price;
        }

        $this->calculateTotals();

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
            $this->errorMessage = 'Booking failed: '.$e->getMessage();
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

        if ($this->isStaff && ! $user->isSuperAdmin() && $user->tenant_id) {
            $halls = Hall::where('tenant_id', $user->tenant_id)->where('is_active', true)->get();
            $packages = Package::where('is_active', true)
                ->where(function ($q) use ($user) {
                    $q->whereNull('tenant_id')->orWhere('tenant_id', $user->tenant_id);
                })->get();
        } elseif ($this->hall_id) {
            $selectedHall = Hall::find($this->hall_id);
            $tenantId = $selectedHall ? $selectedHall->tenant_id : null;
            $halls = Hall::where('is_active', true)
                ->whereHas('tenant', fn ($t) => $t->where('is_active', true)->where('verification_status', 'APPROVED'))
                ->get();
            $packages = Package::where('is_active', true)
                ->where(function ($q) use ($tenantId) {
                    $q->whereNull('tenant_id');
                    if ($tenantId) {
                        $q->orWhere('tenant_id', $tenantId);
                    }
                })->get();
        } else {
            $halls = Hall::where('is_active', true)
                ->whereHas('tenant', fn ($t) => $t->where('is_active', true)->where('verification_status', 'APPROVED'))
                ->get();
            $packages = Package::where('is_active', true)->get();
        }

        $menuItems = MenuItem::where('is_active', true)->get();

        return view('livewire.walk-in-booking-modal', [
            'halls' => $halls,
            'packages' => $packages,
            'menuItems' => $menuItems,
        ]);
    }
}
