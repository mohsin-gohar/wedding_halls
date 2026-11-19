<?php

namespace App\Livewire;

use App\Exceptions\ServiceBookingException;
use App\Models\ServiceBooking;
use App\Models\Tenant;
use App\Models\VendorCategory;
use App\Models\VendorPackage;
use App\Models\VendorService;
use App\Services\ServiceBookingService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Direct "Book Now" flow (Phase 4 — universal service & booking engine).
 *
 * Creates an ACCEPTED ServiceBooking straight from a vendor's service or
 * package card, without the RFQ round-trip. Validation adapts to the vendor
 * category's booking_mode (hours for HOURLY, guests for PER_HEAD, start time
 * for APPOINTMENT…) exactly like the quote request form, but the price is
 * resolved server-side by ServiceBookingService — there is no price input
 * on this form at all, so a tampered amount can never reach the database.
 */
#[Layout('components.layouts.public', ['title' => 'Book Now | Riwaj Grand'])]
class DirectBooking extends Component
{
    public Tenant $vendor;

    public ?VendorCategory $category = null;

    #[Url]
    public ?string $service = null;

    #[Url]
    public ?string $package = null;

    // Form fields
    public string $eventName = '';

    public string $eventDate = '';

    public string $startTime = '';

    public ?float $hours = null;

    public ?int $guestCount = null;

    public string $requirements = '';

    public bool $submitted = false;

    public ?ServiceBooking $booking = null;

    public ?string $errorMessage = null;

    public function mount(string $slug): void
    {
        // Guests are sent to login with an intended return URL so the draft
        // survives authentication (same pattern as the quote request form).
        if (! auth()->check()) {
            session(['url.intended' => url()->full()]);

            $this->redirect(route('login'));

            return;
        }

        $this->vendor = Tenant::query()
            ->where('slug', $slug)
            ->where('marketplace_visible', true)
            ->where('is_active', true)
            ->where('verification_status', 'APPROVED')
            ->with('vendorCategory')
            ->firstOrFail();

        $this->category = $this->vendor->vendorCategory;
        $this->requirements = '';
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

    /** The selected package, verified to belong to this vendor (IDOR-safe). */
    public function getSelectedPackageProperty(): ?VendorPackage
    {
        if (! $this->package) {
            return null;
        }

        return VendorPackage::query()
            ->where('tenant_id', $this->vendor->id)
            ->where('is_active', true)
            ->find($this->package);
    }

    public function getBookableProperty(): VendorService|VendorPackage|null
    {
        return $this->selectedService ?? $this->selectedPackage;
    }

    public function getBookingModeProperty(): string
    {
        return $this->category?->booking_mode ?? 'DATE_EVENT';
    }

    /** Live server-computed price for the current form state (null = quote-only). */
    public function getPricePreviewProperty(): ?float
    {
        $bookable = $this->bookable;

        if (! $bookable) {
            return null;
        }

        return app(ServiceBookingService::class)->previewDirectPrice(
            $bookable,
            $this->bookingMode,
            [
                'guest_count' => $this->guestCount,
                'hours' => $this->hours,
            ]
        );
    }

    /**
     * Whether this item can be booked directly at all (i.e. it carries a
     * fixed base price). Probe uses neutral units (1 guest / 1 hour) so a
     * PER_HEAD or HOURLY item reads as bookable before the input is filled.
     */
    public function getCanDirectBookProperty(): bool
    {
        $bookable = $this->bookable;

        if (! $bookable) {
            return false;
        }

        $mode = $this->bookingMode;

        return app(ServiceBookingService::class)->previewDirectPrice($bookable, $mode, [
            'guest_count' => $mode === 'PER_HEAD' ? 1 : null,
            'hours' => $mode === 'HOURLY' ? 1 : null,
        ]) !== null;
    }

    public function rules(): array
    {
        $mode = $this->bookingMode;

        $rules = [
            'eventName' => ['required', 'string', 'max:150'],
            'eventDate' => ['required', 'date', 'after_or_equal:today'],
            'requirements' => ['nullable', 'string', 'max:5000'],
        ];

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

        $this->errorMessage = null;

        $bookable = $this->bookable;

        if (! $bookable) {
            $this->addError('eventName', 'Please open this page from a service or package to book it directly.');

            return;
        }

        // Abuse control: max 10 direct bookings per user per hour.
        $throttleKey = 'direct-bookings|'.auth()->id();
        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $this->addError('eventName', "Bohat zyada bookings bhej di hain. Baraye meherbani {$seconds} seconds intezar karein.");

            return;
        }

        $validated = $this->validate();

        try {
            $booking = app(ServiceBookingService::class)->createDirectBooking(
                $this->vendor,
                (int) auth()->id(),
                $bookable,
                [
                    'event_name' => $validated['eventName'],
                    'event_date' => $validated['eventDate'],
                    'start_time' => $validated['startTime'] ?? null,
                    'hours' => $validated['hours'] ?? null,
                    'guest_count' => $validated['guestCount'] ?? null,
                    'requirements' => $validated['requirements'] !== '' ? $validated['requirements'] : null,
                ]
            );
        } catch (ServiceBookingException $e) {
            $this->errorMessage = $e->getMessage();

            return;
        }

        RateLimiter::hit($throttleKey, 3600);

        $this->booking = $booking;
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.direct-booking')
            ->layout('components.layouts.public', [
                'title' => 'Book '.$this->vendor->name.' | Riwaj Grand',
            ]);
    }
}
