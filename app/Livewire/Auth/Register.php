<?php

namespace App\Livewire\Auth;

use App\Models\Hall;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VendorCategory;
use App\Models\Venue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Generic vendor (business) self-registration.
 *
 * Every tenant is an independent business — venue, photography studio,
 * catering company, car rental, decor house, planner… The business category
 * decides the onboarding path: only SHIFT_VENUE (hall) selections create a
 * Venue + Hall (the legacy flow, preserved); all other categories register
 * a plain business tenant with no venue-specific fields or records.
 */
class Register extends Component
{
    public string $hallName = '';

    public string $ownerName = '';

    public string $email = '';

    public string $phone = '';

    public string $city = 'Lahore';

    public int $capacity = 500;

    public float $baseRent = 150000;

    public string $password = '';

    public string $password_confirmation = '';

    /** Selected business category (top-level). Null → legacy venue path. */
    public ?int $vendorCategoryId = null;

    /** Active top-level categories offered during registration. */
    public array $categories = [];

    protected $rules = [
        'hallName' => 'required|string|min:3|max:120',
        'ownerName' => 'required|string|min:3|max:100',
        'email' => 'required|email|unique:users,email|unique:tenants,email',
        'phone' => 'required|string|min:10|max:25',
        'city' => 'required|string|max:80',
        'vendorCategoryId' => 'nullable|integer|exists:vendor_categories,id',
        'password' => 'required|string|min:8|confirmed',
    ];

    public function mount()
    {
        $isOpen = Cache::get(
            'platform_vendor_registration_open',
            (bool) config('platform.vendor_registration_open', true)
        );
        if (! $isOpen) {
            session()->flash('error', 'Vendor self-registration is currently paused by platform administrators.');
        }

        $this->categories = VendorCategory::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('name')
            ->get(['id', 'name', 'booking_mode'])
            ->map(fn ($c) => [
                'id' => (int) $c->id,
                'name' => $c->name,
                'booking_mode' => $c->booking_mode,
            ])
            ->values()
            ->toArray();

        if ($this->vendorCategoryId === null) {
            // Legacy default: venue onboarding (also the behaviour when no
            // taxonomy rows exist yet, e.g. unseeded environments).
            $venueCategory = collect($this->categories)->firstWhere('booking_mode', 'SHIFT_VENUE');
            $this->vendorCategoryId = $venueCategory['id'] ?? null;
        }
    }

    /**
     * Whether the current selection follows the hall/venue onboarding path.
     */
    public function isVenueSelection(): bool
    {
        if ($this->vendorCategoryId === null) {
            return true; // Legacy behaviour: no category selected → venue.
        }

        $category = collect($this->categories)->firstWhere('id', $this->vendorCategoryId);

        if (! $category) {
            // Selected id is not in the active top-level list (deleted or
            // inactive meanwhile) — fall back to venue rules so the form
            // still behaves predictably; validation will catch bad ids.
            return true;
        }

        return $category['booking_mode'] === 'SHIFT_VENUE';
    }

    public function register()
    {
        $isOpen = Cache::get(
            'platform_vendor_registration_open',
            (bool) config('platform.vendor_registration_open', true)
        );
        if (! $isOpen) {
            $this->addError('hallName', 'Vendor self-registration is currently paused. Please contact platform concierge.');

            return;
        }

        $throttleKey = 'register|'.request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('hallName', "Aap dobara se register koshish nahi kar saktay. Baraye meherbani {$seconds} seconds intezar karein. (Too many registration attempts. Please wait {$seconds} seconds.)");

            return;
        }
        RateLimiter::hit($throttleKey, 60);

        $rules = $this->rules;

        // Venue-specific fields are only demanded for venue onboarding —
        // a photographer or caterer never sees or validates hall capacity.
        if ($this->isVenueSelection()) {
            $rules['capacity'] = 'required|integer|min:50|max:5000';
            $rules['baseRent'] = 'required|numeric|min:10000';
        }

        $this->validate($rules);

        $isVenue = $this->isVenueSelection();
        $selectedCategoryId = $this->vendorCategoryId;

        $user = DB::transaction(function () use ($isVenue, $selectedCategoryId) {
            // 1. Create Tenant (an independent business)
            $slug = Str::slug($this->hallName).'-'.Str::lower(Str::random(5));
            $defaultPlan = SubscriptionPlan::where('slug', 'starter-marquee')->first()
                ?? SubscriptionPlan::where('is_active', true)->orderBy('price_monthly', 'asc')->first();

            $tenant = Tenant::create([
                'name' => $this->hallName,
                'slug' => $slug,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->city.', Pakistan',
                'city' => $this->city,
                'vendor_category_id' => $selectedCategoryId,
                'subscription_plan' => $defaultPlan?->name ?? 'Starter Marquee',
                'subscription_plan_id' => $defaultPlan?->id,
                'subscription_status' => 'TRIAL',
                'billing_cycle' => 'MONTHLY',
                'subscription_starts_at' => now(),
                'subscription_expires_at' => now()->addDays(
                    (int) config('platform.free_access_days', 30)
                ),
                'verification_status' => 'PENDING',
                'currency' => 'PKR',
                'is_active' => true,
            ]);

            if ($isVenue) {
                // 2a. Create Venue (venue businesses only — legacy flow)
                $venue = Venue::create([
                    'tenant_id' => $tenant->id,
                    'name' => $this->hallName.' Complex',
                    'slug' => Str::slug($this->hallName.'-complex'),
                    'city' => $this->city,
                    'address' => $this->city.', Pakistan',
                    'phone' => $this->phone,
                    'email' => $this->email,
                    'is_active' => true,
                ]);

                // 2b. Create Hall
                Hall::create([
                    'tenant_id' => $tenant->id,
                    'venue_id' => $venue->id,
                    'name' => $this->hallName,
                    'min_capacity' => (int) ($this->capacity * 0.4),
                    'max_capacity' => $this->capacity,
                    'base_rent' => $this->baseRent,
                    'description' => 'Luxury banqueting hall space with central climate control, power backup, and VIP bridal room.',
                    'cover_image' => null,
                    'images' => [],
                    'videos' => [],
                    'has_air_conditioning' => true,
                    'has_generator_backup' => true,
                    'has_bridal_room' => true,
                    'is_active' => true,
                ]);
            }

            // 3. Create User
            $owner = User::create([
                'tenant_id' => $tenant->id,
                'name' => $this->ownerName,
                'email' => $this->email,
                'phone' => $this->phone,
                'role' => 'ROLE_TENANT_OWNER',
                'password' => Hash::make($this->password),
            ]);

            if (method_exists($owner, 'assignRole')) {
                $owner->assignRole('ROLE_TENANT_OWNER');
            }

            return $owner;
        });

        Auth::login($user);
        session()->regenerate();

        if (! $isVenue) {
            session()->flash(
                'message',
                'Congratulations! Your business has been registered and is now under platform review. '
                .'Complete your profile, services and portfolio to go live on the marketplace.'
            );

            return redirect()->route($this->postRegistrationRoute());
        }

        session()->flash(
            'message',
            'Congratulations! Your hall has been registered and is now under platform review. '
            .'The moment Riwaj Grand approves it, your hall goes live publicly. Meanwhile you can complete your images & videos.'
        );

        return redirect()->route('hall.media');
    }

    /**
     * Category-aware landing dashboard for non-venue businesses.
     */
    private function postRegistrationRoute(): string
    {
        $map = [
            'photography' => 'vendor.dashboard.photography',
            'catering' => 'vendor.dashboard.catering',
            'decoration' => 'vendor.dashboard.decoration',
            'beauty-bridal' => 'vendor.dashboard.beauty-bridal',
            'wedding-planners' => 'vendor.dashboard.wedding-planners',
        ];

        if ($this->vendorCategoryId !== null) {
            $slug = VendorCategory::query()
                ->where('id', $this->vendorCategoryId)
                ->value('slug');

            if (is_string($slug) && isset($map[$slug])) {
                return $map[$slug];
            }
        }

        return 'vendor.dashboard.generic';
    }

    public function render()
    {
        return view('livewire.auth.register')
            ->layout('components.layouts.auth', ['title' => 'Register Your Business — Riwaj Grand']);
    }
}
