<?php

namespace App\Livewire;

use App\Models\RFQ;
use App\Models\RfqTarget;
use App\Models\Tenant;
use App\Models\VendorCategory;
use App\Models\VendorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Category-aware quote request form (Phase 2 — marketplace).
 *
 * Creates an OPEN RFQ aimed at the requested vendor (rfqs + rfq_targets)
 * so the vendor sees it on their quotes board. Validation adapts to the
 * vendor category's booking_mode (hours for HOURLY, guests for PER_HEAD,
 * start time for APPOINTMENT…), matching the generic booking engine.
 */
#[Layout('components.layouts.public', ['title' => 'Request a Quote | Riwaj Grand'])]
class VendorQuoteRequest extends Component
{
    #[Locked]
    public Tenant $vendor;

    #[Locked]
    public ?VendorCategory $category;

    #[Url]
    public ?string $service = null;

    // Form fields
    public string $eventName = '';

    public string $eventDate = '';

    public string $startTime = '';

    public ?float $hours = null;

    public ?int $guestCount = null;

    public string $budgetRange = 'not-sure';

    public string $city = '';

    public string $requirements = '';

    public bool $submitted = false;

    public ?RFQ $rfq = null;

    public function mount(string $slug): void
    {
        // Guests are sent to login with an intended return URL ( honoured by
        // the login flow ) so the quote draft survives authentication.
        if (! auth()->check()) {
            session(['url.intended' => url()->full()]);

            $this->redirect(route('login'));

            return;
        }

        $this->vendor = Tenant::query()
            ->marketplaceVisible()
            ->where('slug', $slug)
            ->with('vendorCategory')
            ->firstOrFail();

        $this->category = $this->vendor->vendorCategory;
        $this->city = $this->vendor->city ?? '';
    }

    /** The selected service, verified to belong to this vendor (IDOR-safe). */
    public function getSelectedServiceProperty(): ?VendorService
    {
        if (! $this->service) {
            return null;
        }

        return VendorService::query()
            ->where('tenant_id', $this->vendor->id)
            ->where('is_active', true)
            ->find($this->service);
    }

    public function rules(): array
    {
        $mode = $this->category?->booking_mode ?? 'DATE_EVENT';

        // Base rules for every category…
        $rules = [
            'eventName' => ['required', 'string', 'max:150'],
            'eventDate' => ['required', 'date', 'after_or_equal:today'],
            'city' => ['nullable', 'string', 'max:80'],
            'budgetRange' => ['nullable', 'in:under-50k,50k-150k,150k-300k,300k-plus,not-sure'],
            'requirements' => ['nullable', 'string', 'max:5000'],
        ];

        // …then category-specific required fields (booking_mode driven).
        switch ($mode) {
            case 'HOURLY':
                $rules['hours'] = ['required', 'numeric', 'min:1', 'max:24'];
                $rules['guestCount'] = ['nullable', 'integer', 'min:1', 'max:20000'];
                break;
            case 'PER_HEAD':
                $rules['guestCount'] = ['required', 'integer', 'min:1', 'max:20000'];
                break;
            case 'APPOINTMENT':
                $rules['startTime'] = ['required', 'date_format:H:i'];
                break;
            default:
                $rules['guestCount'] = ['nullable', 'integer', 'min:1', 'max:20000'];
                $rules['hours'] = ['nullable', 'numeric', 'min:1', 'max:24'];
                $rules['startTime'] = ['nullable', 'date_format:H:i'];
        }

        return $rules;
    }

    public function save(): void
    {
        // Server-side auth re-check (route middleware alone is not enough
        // for subsequent Livewire update requests).
        if (! auth()->check()) {
            $this->redirect(route('login'));

            return;
        }

        // Abuse control: max 10 open quote requests per user per hour.
        $throttleKey = 'quote-requests|'.auth()->id();
        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $this->addError('requirements', "Bohat zyada quote requests bhej di hain. Baraye meherbani {$seconds} seconds intezar karein.");

            return;
        }

        $validated = $this->validate();

        $budgetLabels = [
            'under-50k' => 'PKR 0 – 50,000',
            '50k-150k' => 'PKR 50,000 – 150,000',
            '150k-300k' => 'PKR 150,000 – 300,000',
            '300k-plus' => 'PKR 300,000+',
            'not-sure' => 'Not specified',
        ];

        $service = $this->selectedService;
        $mode = $this->category?->booking_mode ?? 'DATE_EVENT';

        $rfq = DB::transaction(function () use ($validated, $budgetLabels, $service, $mode) {
            // Recheck under a tenant-row lock: Livewire properties can outlive
            // a vendor's subscription or marketplace suspension.
            $vendor = Tenant::query()
                ->whereKey($this->vendor->id)
                ->lockForUpdate()
                ->first();

            if (! $vendor || ! $vendor->marketplace_visible || ! $vendor->canAcceptBookings()) {
                return null;
            }

            $rfq = RFQ::create([
                'client_id' => auth()->id(),
                'tenant_id' => $vendor->id,
                'vendor_category_id' => $vendor->vendor_category_id,
                'title' => $validated['eventName'],
                'event_date' => $validated['eventDate'],
                'city' => $validated['city'] ?: $vendor->city,
                'guest_count' => $validated['guestCount'] ?? null,
                'budget_range' => $budgetLabels[$validated['budgetRange'] ?? 'not-sure'] ?? 'Not specified',
                'requirements' => $validated['requirements'] !== '' ? $validated['requirements'] : 'Quote request from '.$vendor->name.' profile page.',
                'status' => 'OPEN',
                'preferences' => [
                    'booking_mode' => $mode,
                    'start_time' => $validated['startTime'] ?? null,
                    'hours' => $validated['hours'] ?? null,
                    'service_id' => $service?->id,
                    'service_name' => $service?->name,
                ],
            ]);

            // Target this vendor explicitly — their quotes board reads rfq_targets.
            RfqTarget::firstOrCreate([
                'rfq_id' => $rfq->id,
                'tenant_id' => $vendor->id,
            ]);

            return $rfq;
        });

        if (! $rfq) {
            $this->addError('requirements', 'This vendor is currently unavailable for new quote requests.');

            return;
        }

        RateLimiter::hit($throttleKey, 3600);

        $this->rfq = $rfq;
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.vendor-quote-request')
            ->layout('components.layouts.public', [
                'title' => 'Request a Quote — '.$this->vendor->name.' | Riwaj Grand',
            ]);
    }
}
