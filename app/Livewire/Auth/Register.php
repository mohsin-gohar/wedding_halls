<?php

namespace App\Livewire\Auth;

use App\Models\Hall;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;

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

    protected $rules = [
        'hallName' => 'required|string|min:3|max:120',
        'ownerName' => 'required|string|min:3|max:100',
        'email' => 'required|email|unique:users,email|unique:tenants,email',
        'phone' => 'required|string|min:10|max:25',
        'city' => 'required|string|max:80',
        'capacity' => 'required|integer|min:50|max:5000',
        'baseRent' => 'required|numeric|min:10000',
        'password' => 'required|string|min:6|confirmed',
    ];

    public function mount()
    {
        $isOpen = Cache::get('platform_vendor_registration_open', true);
        if (! $isOpen) {
            session()->flash('error', 'Vendor self-registration is currently paused by platform administrators.');
        }
    }

    public function register()
    {
        $isOpen = Cache::get('platform_vendor_registration_open', true);
        if (! $isOpen) {
            $this->addError('hallName', 'Vendor self-registration is currently paused. Please contact platform concierge.');

            return;
        }

        $this->validate();

        $user = DB::transaction(function () {
            // 1. Create Tenant
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
                'subscription_plan' => $defaultPlan?->name ?? 'Starter Marquee',
                'subscription_plan_id' => $defaultPlan?->id,
                'subscription_status' => 'TRIAL',
                'billing_cycle' => 'MONTHLY',
                'subscription_starts_at' => now(),
                'subscription_expires_at' => now()->addDays($defaultPlan?->trial_days ?? 14),
                'verification_status' => 'PENDING',
                'currency' => 'PKR',
                'is_active' => true,
            ]);

            // 2. Create Venue
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

            // 3. Create Hall
            Hall::create([
                'tenant_id' => $tenant->id,
                'venue_id' => $venue->id,
                'name' => $this->hallName,
                'min_capacity' => (int) ($this->capacity * 0.4),
                'max_capacity' => $this->capacity,
                'base_rent' => $this->baseRent,
                'description' => 'Luxury banqueting hall space with central climate control, power backup, and VIP bridal room.',
                'cover_image' => 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1200&auto=format&fit=crop',
                'images' => [
                    'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1200&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=1200&auto=format&fit=crop',
                ],
                'videos' => [
                    'https://www.youtube.com/embed/7wtfhZwyrcc',
                ],
                'has_air_conditioning' => true,
                'has_generator_backup' => true,
                'has_bridal_room' => true,
                'is_active' => true,
            ]);

            // 4. Create User
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
        session()->flash('message', 'Congratulations! Your hall has been registered. You can now add your images & videos.');

        return redirect()->route('hall.media');
    }

    public function render()
    {
        return view('livewire.auth.register')
            ->layout('components.layouts.auth', ['title' => 'Register Your Venue — EventHub']);
    }
}
