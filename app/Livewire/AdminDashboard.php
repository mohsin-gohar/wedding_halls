<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\HallView;
use App\Models\HallWishlist;
use App\Models\PageVisit;
use App\Models\PlatformTask;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Super Admin Console — EventHub'])]
class AdminDashboard extends Component
{
    #[Url(as: 'tab')]
    public string $activeTab = 'vendors'; // vendors, tasks, traffic, plans, payments, halls, bookings, users, analytics, audit

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    // Platform Tasks Management (Super Admin Tasks & Action Items)
    public string $newTaskTitle = '';

    public string $newTaskCategory = 'GENERAL'; // VENDOR_ONBOARDING, TRAFFIC_MONITOR, PAYMENT_REVIEW, GENERAL

    public string $newTaskPriority = 'MEDIUM'; // HIGH, MEDIUM, LOW

    public string $newTaskDueDate = '';

    public string $newTaskNotes = '';

    // Platform Traffic & Operations Control Center
    public bool $vendorRegistrationOpen = true;

    public bool $marketplaceEnabled = true;

    public string $announcementBanner = '';

    // SaaS Plan Form (CRUD)
    public bool $showPlanModal = false;

    public bool $isEditingPlan = false;

    public ?int $editingPlanId = null;

    public string $planName = '';

    public string $planSlug = '';

    public string $planDescription = '';

    public float $planPriceWeekly = 5000;

    public float $planPriceMonthly = 15000;

    public float $planPriceYearly = 150000;

    public int $planMaxHalls = 1;

    public int $planMaxStaff = 3;

    public int $planMaxBookings = 20;

    public bool $planHasKot = true;

    public bool $planHasWhatsapp = false;

    public bool $planHasAccounting = true;

    // Vendor Provisioning Form (Super Admin Onboarding)
    public bool $showProvisionModal = false;

    public string $newBusinessName = '';

    public string $newCity = 'Lahore';

    public string $newAddress = '';

    public int $newMinCapacity = 150;

    public int $newMaxCapacity = 750;

    public float $newBaseRent = 175000;

    public string $newTagline = '';

    public string $newOwnerName = '';

    public string $newOwnerEmail = '';

    public string $newOwnerPassword = '';

    public string $newOwnerPhone = '';

    public ?int $newPlanId = null;

    // Vendor Edit Form (CRUD)
    public bool $showEditVendorModal = false;

    public ?string $editingVendorId = null;

    public string $editVendorName = '';

    public string $editVendorCity = '';

    public string $editVendorAddress = '';

    public string $editVendorPhone = '';

    public string $editVendorEmail = '';

    public ?int $editVendorPlanId = null;

    public string $editVendorExpiresAt = '';

    // Hall Edit Form (CRUD)
    public bool $showEditHallModal = false;

    public ?string $editingHallId = null;

    public string $editHallName = '';

    public string $editHallCity = 'Lahore';

    public int $editHallMinCapacity = 150;

    public int $editHallMaxCapacity = 750;

    public float $editHallBaseRent = 175000;

    public bool $editHallIsActive = true;

    // Handover Credentials Card
    public ?array $provisionedCredentials = null;

    // User Directory & Access Management (Filters)
    public string $userSearch = '';

    public string $userRoleFilter = 'ALL';

    public string $userStatusFilter = 'ALL';

    public function mount()
    {
        $user = Auth::user();
        if (! $user || ! $user->isSuperAdmin()) {
            abort(403, 'Access denied. You do not possess Super Admin administrative privileges.');
        }

        if (request()->has('tab')) {
            $this->activeTab = request()->query('tab');
        }

        $this->vendorRegistrationOpen = Cache::get('platform_vendor_registration_open', true);
        $this->marketplaceEnabled = Cache::get('platform_marketplace_enabled', true);
        $this->announcementBanner = Cache::get('platform_announcement', '');
        $this->newTaskDueDate = Carbon::now()->addDays(2)->format('Y-m-d');
    }

    #[On('set-admin-tab')]
    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
        $this->feedbackMessage = null;
        $this->errorMessage = null;
    }

    // ==========================================
    // 0. PLATFORM TASKS & TO-DO MANAGEMENT
    // ==========================================
    public function addTask(): void
    {
        $this->validate([
            'newTaskTitle' => 'required|string|min:3|max:180',
            'newTaskCategory' => 'required|string',
            'newTaskPriority' => 'required|in:HIGH,MEDIUM,LOW',
        ]);

        PlatformTask::create([
            'title' => trim($this->newTaskTitle),
            'category' => $this->newTaskCategory,
            'priority' => $this->newTaskPriority,
            'due_date' => ! empty($this->newTaskDueDate) ? $this->newTaskDueDate : null,
            'notes' => $this->newTaskNotes,
            'created_by_user_id' => Auth::id(),
            'is_completed' => false,
        ]);

        AuditLog::log('CREATE_PLATFORM_TASK', "Created administrative task: {$this->newTaskTitle}");

        $this->newTaskTitle = '';
        $this->newTaskNotes = '';
        $this->feedbackMessage = 'Super Admin task added successfully.';
    }

    public function toggleTask(string $taskId): void
    {
        $task = PlatformTask::findOrFail($taskId);
        $task->is_completed = ! $task->is_completed;
        $task->completed_at = $task->is_completed ? now() : null;
        $task->save();

        AuditLog::log('TOGGLE_PLATFORM_TASK', "Updated status of task [{$task->title}] to ".($task->is_completed ? 'COMPLETED' : 'PENDING'));
        $this->feedbackMessage = 'Task updated.';
    }

    public function deleteTask(string $taskId): void
    {
        $task = PlatformTask::findOrFail($taskId);
        $title = $task->title;
        $task->delete();

        AuditLog::log('DELETE_PLATFORM_TASK', "Deleted task [{$title}]");
        $this->feedbackMessage = 'Task removed.';
    }

    public function toggleRegistrationStatus(): void
    {
        $this->vendorRegistrationOpen = ! $this->vendorRegistrationOpen;
        Cache::put('platform_vendor_registration_open', $this->vendorRegistrationOpen);
        AuditLog::log('TOGGLE_REGISTRATION_STATUS', 'Vendor public registration set to '.($this->vendorRegistrationOpen ? 'OPEN' : 'CLOSED'));
        $this->feedbackMessage = 'Vendor Registration is now '.($this->vendorRegistrationOpen ? 'OPEN for all new marquees' : 'CLOSED temporarily');
    }

    public function toggleMarketplaceStatus(): void
    {
        $this->marketplaceEnabled = ! $this->marketplaceEnabled;
        Cache::put('platform_marketplace_enabled', $this->marketplaceEnabled);
        AuditLog::log('TOGGLE_MARKETPLACE_STATUS', 'Public marketplace visibility set to '.($this->marketplaceEnabled ? 'ACTIVE' : 'PAUSED'));
        $this->feedbackMessage = 'Public Marketplace visibility is now '.($this->marketplaceEnabled ? 'ACTIVE' : 'PAUSED (Maintenance)');
    }

    public function saveAnnouncement(): void
    {
        Cache::put('platform_announcement', trim($this->announcementBanner));
        AuditLog::log('UPDATE_ANNOUNCEMENT', "Updated platform announcement banner: {$this->announcementBanner}");
        $this->feedbackMessage = 'Platform announcement updated.';
    }

    // ==========================================
    // 1. VENDOR / TENANT CRUD
    // ==========================================
    public function openProvisionModal()
    {
        $this->resetProvisionForm();
        $firstPlan = SubscriptionPlan::where('is_active', true)->first();
        $this->newPlanId = $firstPlan ? $firstPlan->id : null;
        $this->showProvisionModal = true;
    }

    public function closeProvisionModal()
    {
        $this->showProvisionModal = false;
    }

    public function resetProvisionForm()
    {
        $this->newBusinessName = '';
        $this->newCity = 'Lahore';
        $this->newAddress = '';
        $this->newMinCapacity = 150;
        $this->newMaxCapacity = 750;
        $this->newBaseRent = 175000;
        $this->newTagline = '';
        $this->newOwnerName = '';
        $this->newOwnerEmail = '';
        $this->newOwnerPassword = '';
        $this->newOwnerPhone = '';
    }

    public function provisionHallAccount()
    {
        $this->validate([
            'newBusinessName' => 'required|string|min:3|max:120',
            'newCity' => 'required|string|max:60',
            'newAddress' => 'nullable|string|max:255',
            'newMinCapacity' => 'required|integer|min:20',
            'newMaxCapacity' => 'required|integer|min:50',
            'newBaseRent' => 'required|numeric|min:10000',
            'newOwnerName' => 'required|string|min:3|max:100',
            'newOwnerEmail' => 'required|email|unique:users,email',
            'newOwnerPassword' => 'required|string|min:6',
            'newOwnerPhone' => 'required|string|min:8|max:30',
        ]);

        $plan = $this->newPlanId ? SubscriptionPlan::find($this->newPlanId) : SubscriptionPlan::first();

        // 1. Create Tenant with unique slug and subdomain
        $baseSlug = Str::slug($this->newBusinessName);
        $slug = $baseSlug;
        $counter = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        $baseSubdomain = Str::slug($this->newBusinessName);
        $subdomain = $baseSubdomain;
        $subCounter = 1;
        while (Tenant::where('subdomain', $subdomain)->exists()) {
            $subdomain = $baseSubdomain.'-'.$subCounter++;
        }

        // 4. Default 2026 Luxury Services Catalog from config
        $defaultServices = config('hall.default_services');

        [$tenant, $hall] = DB::transaction(function () use ($plan, $slug, $subdomain, $defaultServices) {
            $tenant = Tenant::create([
                'name' => $this->newBusinessName,
                'slug' => $slug,
                'subdomain' => $subdomain,
                'email' => $this->newOwnerEmail,
                'phone' => $this->newOwnerPhone,
                'address' => $this->newAddress ?: "Main Boulevard, {$this->newCity}",
                'subscription_plan_id' => $plan ? $plan->id : null,
                'subscription_status' => 'ACTIVE',
                'verification_status' => 'APPROVED',
                'is_active' => true,
                'subscription_starts_at' => Carbon::now(),
                'subscription_expires_at' => Carbon::now()->addYear(),
                'currency' => 'PKR',
            ]);

            // 2. Create Rent Admin User
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $this->newOwnerName,
                'email' => $this->newOwnerEmail,
                'phone' => $this->newOwnerPhone,
                'password' => Hash::make($this->newOwnerPassword),
                'role' => 'ROLE_TENANT_OWNER',
            ]);
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('ROLE_TENANT_OWNER');
            }

            // 3. Create Venue with unique slug
            $baseVenueSlug = Str::slug($this->newBusinessName);
            $venueSlug = $baseVenueSlug;
            $vCounter = 1;
            while (Venue::where('slug', $venueSlug)->exists()) {
                $venueSlug = $baseVenueSlug.'-'.$vCounter++;
            }
            $venue = Venue::create([
                'tenant_id' => $tenant->id,
                'name' => $this->newBusinessName.' Complex',
                'slug' => $venueSlug.'-venue',
                'city' => $this->newCity,
                'area' => 'Prime District',
                'address' => $this->newAddress ?: "Main Commercial Boulevard, {$this->newCity}",
                'phone' => $this->newOwnerPhone,
                'email' => $this->newOwnerEmail,
                'description' => "Premier hospitality venue hosting royal weddings, banquets, and executive functions in {$this->newCity}.",
                'is_active' => true,
            ]);

            // 5. Create Hall
            $hall = Hall::create([
                'tenant_id' => $tenant->id,
                'venue_id' => $venue->id,
                'name' => $this->newBusinessName,
                'tagline' => $this->newTagline ?: "Exquisite Celebrations & Royal Banquets in {$this->newCity}",
                'min_capacity' => $this->newMinCapacity,
                'max_capacity' => $this->newMaxCapacity,
                'base_rent' => $this->newBaseRent,
                'description' => 'Grand wedding banquet marquee ballroom featuring Austrian crystal chandeliers, climate control, VIP bridal suites, and standby CAT generators.',
                'operating_hours' => 'Morning: 11:30 AM - 3:30 PM | Evening: 7:00 PM - 12:00 AM',
                'policies' => "1. 50% Token advance required to atomically secure wedding date.\n2. Music permissible till 10:00 PM per city banquet bylaws.\n3. Outside catering permitted with kitchen NOC.",
                'cover_image' => 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1200&auto=format&fit=crop',
                'images' => [
                    'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1200&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=1200&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1465495976277-4387d4b0b4c6?q=80&w=1200&auto=format&fit=crop',
                ],
                'videos' => [
                    'https://www.youtube.com/embed/7wtfhZwyrcc',
                ],
                'services' => $defaultServices,
                'has_air_conditioning' => true,
                'has_generator_backup' => true,
                'has_bridal_room' => true,
                'is_active' => true,
            ]);

            return [$tenant, $hall];
        });

        AuditLog::log(
            'HALL_PROVISIONED',
            "Super Admin provisioned new Hall [{$this->newBusinessName}] and created Rent Admin account for [{$this->newOwnerEmail}].",
            $tenant->id,
            'Hall',
            $hall->id
        );

        $plainPassword = $this->newOwnerPassword;
        // Flash the password for one-time display (do not store)
        session()->flash('provisioned_password', $plainPassword);
        $this->provisionedCredentials = [
            'business_name' => $this->newBusinessName,
            'city' => $this->newCity,
            'owner_name' => $this->newOwnerName,
            'email' => $this->newOwnerEmail,
            'phone' => $this->newOwnerPhone,
            'login_url' => url('/login'),
            'hall_id' => $hall->id,
            'hall_url' => route('hall.showcase', $hall->id),
        ];

        // Clear password field for security
        $this->newOwnerPassword = '';

        $this->showProvisionModal = false;
        $this->feedbackMessage = "Hall [{$this->newBusinessName}] registered successfully! Hand over the credentials below to the hall owner.";
    }

    public function dismissCredentials()
    {
        $this->provisionedCredentials = null;
    }

    public function openEditVendorModal(string $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->editingVendorId = $tenant->id;
        $this->editVendorName = $tenant->name;
        $this->editVendorCity = $tenant->city ?? 'Lahore';
        $this->editVendorAddress = $tenant->address ?? '';
        $this->editVendorPhone = $tenant->phone ?? '';
        $this->editVendorEmail = $tenant->email ?? '';
        $this->editVendorPlanId = $tenant->subscription_plan_id;
        $this->editVendorExpiresAt = $tenant->subscription_expires_at ? $tenant->subscription_expires_at->format('Y-m-d') : '';
        $this->showEditVendorModal = true;
    }

    public function closeEditVendorModal()
    {
        $this->showEditVendorModal = false;
        $this->editingVendorId = null;
    }

    public function updateVendor()
    {
        $this->validate([
            'editVendorName' => 'required|string|min:3|max:120',
            'editVendorEmail' => 'required|email',
            'editVendorPhone' => 'required|string|min:8',
        ]);

        $tenant = Tenant::findOrFail($this->editingVendorId);
        $tenant->update([
            'name' => $this->editVendorName,
            'city' => $this->editVendorCity,
            'address' => $this->editVendorAddress,
            'phone' => $this->editVendorPhone,
            'email' => $this->editVendorEmail,
            'subscription_plan_id' => $this->editVendorPlanId,
            'subscription_expires_at' => $this->editVendorExpiresAt ? Carbon::parse($this->editVendorExpiresAt) : $tenant->subscription_expires_at,
        ]);

        AuditLog::log('VENDOR_UPDATED', "Super Admin updated details for [{$tenant->name}].", $tenant->id, 'Tenant', $tenant->id);

        $this->showEditVendorModal = false;
        $this->feedbackMessage = "Vendor [{$tenant->name}] updated successfully.";
    }

    public function deleteVendor(string $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $name = $tenant->name;

        // If the vendor has active/confirmed client bookings, prevent accidental destruction
        if ($tenant->bookings()->whereIn('status', ['CONFIRMED', 'HOLD'])->exists()) {
            $tenant->update(['is_active' => false, 'verification_status' => 'SUSPENDED']);
            AuditLog::log('VENDOR_SUSPENDED', "Vendor [{$name}] has confirmed bookings; suspended instead of deleted to protect client data.", null, 'Tenant', $tenantId);
            $this->feedbackMessage = "Vendor [{$name}] has active confirmed bookings. To protect client contracts and payment history, the vendor was safely deactivated and suspended instead of permanently deleted.";

            return;
        }

        // Clean delete if no active bookings exist
        $tenant->halls()->delete();
        $tenant->users()->delete();
        $tenant->delete();

        AuditLog::log('VENDOR_DELETED', "Super Admin deleted vendor [{$name}].", null, 'Tenant', $tenantId);
        $this->feedbackMessage = "Vendor [{$name}] and associated records deleted.";
    }

    public function approveVendor(string $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $tenant->update([
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);

        AuditLog::log('VENDOR_APPROVED', "Super Admin approved and activated vendor [{$tenant->name}].", $tenant->id, 'Tenant', $tenant->id);
        $this->feedbackMessage = "Vendor [{$tenant->name}] has been approved.";
    }

    public function approveTenant(string $tenantId)
    {
        $this->approveVendor($tenantId);
    }

    public function suspendVendor(string $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $tenant->update([
            'verification_status' => 'SUSPENDED',
            'is_active' => false,
        ]);

        AuditLog::log('VENDOR_SUSPENDED', "Super Admin suspended vendor [{$tenant->name}].", $tenant->id, 'Tenant', $tenant->id);
        $this->feedbackMessage = "Vendor [{$tenant->name}] has been suspended.";
    }

    public function suspendTenant(string $tenantId)
    {
        $this->suspendVendor($tenantId);
    }

    public function toggleTenantStatus(string $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $tenant->update([
            'is_active' => ! $tenant->is_active,
        ]);

        AuditLog::log('VENDOR_TOGGLED', "Toggled active status of vendor [{$tenant->name}] to ".($tenant->is_active ? 'Active' : 'Inactive'), $tenant->id, 'Tenant', $tenant->id);
        $this->feedbackMessage = "Tenant [{$tenant->name}] status toggled to ".($tenant->is_active ? 'Active' : 'Inactive').'.';
    }

    public function assignPlanToTenant(string $tenantId, int $planId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $plan = SubscriptionPlan::findOrFail($planId);

        $tenant->update([
            'subscription_plan_id' => $plan->id,
            'subscription_status' => 'ACTIVE',
            'subscription_starts_at' => Carbon::now(),
            'subscription_expires_at' => Carbon::now()->addYear(),
        ]);

        AuditLog::log('PLAN_ASSIGNED', "Super Admin assigned SaaS plan [{$plan->name}] to vendor [{$tenant->name}].", $tenant->id, 'Tenant', $tenant->id);
        $this->feedbackMessage = "Vendor [{$tenant->name}] upgraded to [{$plan->name}] plan.";
    }

    // ==========================================
    // 2. SAAS SUBSCRIPTION PLANS CRUD
    // ==========================================
    public function openCreatePlanModal()
    {
        $this->isEditingPlan = false;
        $this->editingPlanId = null;
        $this->planName = '';
        $this->planSlug = '';
        $this->planDescription = '';
        $this->planPriceWeekly = 5000;
        $this->planPriceMonthly = 15000;
        $this->planPriceYearly = 150000;
        $this->planMaxHalls = 1;
        $this->planMaxStaff = 3;
        $this->planMaxBookings = 20;
        $this->planHasKot = true;
        $this->planHasWhatsapp = false;
        $this->planHasAccounting = true;
        $this->showPlanModal = true;
    }

    public function openEditPlanModal(int $planId)
    {
        $plan = SubscriptionPlan::findOrFail($planId);
        $this->isEditingPlan = true;
        $this->editingPlanId = $plan->id;
        $this->planName = $plan->name;
        $this->planSlug = $plan->slug;
        $this->planDescription = $plan->description ?? '';
        $this->planPriceWeekly = (float) ($plan->price_weekly ?? 5000);
        $this->planPriceMonthly = (float) $plan->price_monthly;
        $this->planPriceYearly = (float) $plan->price_yearly;
        $this->planMaxHalls = $plan->max_halls;
        $this->planMaxStaff = $plan->max_staff;
        $this->planMaxBookings = $plan->max_monthly_bookings;
        $this->planHasKot = (bool) $plan->has_kitchen_kot;
        $this->planHasWhatsapp = (bool) $plan->has_whatsapp_automation;
        $this->planHasAccounting = (bool) $plan->has_accounting_reports;
        $this->showPlanModal = true;
    }

    public function closePlanModal()
    {
        $this->showPlanModal = false;
        $this->editingPlanId = null;
    }

    public function savePlan()
    {
        $this->validate([
            'planName' => 'required|string|min:3|max:100',
            'planPriceWeekly' => 'required|numeric|min:0',
            'planPriceMonthly' => 'required|numeric|min:0',
            'planPriceYearly' => 'required|numeric|min:0',
            'planMaxHalls' => 'required|integer',
            'planMaxStaff' => 'required|integer',
            'planMaxBookings' => 'required|integer',
        ]);

        $slug = $this->planSlug ?: Str::slug($this->planName);

        if ($this->isEditingPlan && $this->editingPlanId) {
            $plan = SubscriptionPlan::findOrFail($this->editingPlanId);
            $plan->update([
                'name' => $this->planName,
                'slug' => $slug,
                'description' => $this->planDescription,
                'price_weekly' => $this->planPriceWeekly,
                'price_monthly' => $this->planPriceMonthly,
                'price_yearly' => $this->planPriceYearly,
                'max_halls' => $this->planMaxHalls,
                'max_staff' => $this->planMaxStaff,
                'max_monthly_bookings' => $this->planMaxBookings,
                'has_kitchen_kot' => $this->planHasKot,
                'has_whatsapp_automation' => $this->planHasWhatsapp,
                'has_accounting_reports' => $this->planHasAccounting,
            ]);
            $this->feedbackMessage = "Subscription Plan [{$plan->name}] updated successfully.";
        } else {
            $plan = SubscriptionPlan::create([
                'name' => $this->planName,
                'slug' => $slug.'-'.rand(100, 999),
                'description' => $this->planDescription,
                'price_weekly' => $this->planPriceWeekly,
                'price_monthly' => $this->planPriceMonthly,
                'price_yearly' => $this->planPriceYearly,
                'max_halls' => $this->planMaxHalls,
                'max_staff' => $this->planMaxStaff,
                'max_monthly_bookings' => $this->planMaxBookings,
                'has_kitchen_kot' => $this->planHasKot,
                'has_whatsapp_automation' => $this->planHasWhatsapp,
                'has_accounting_reports' => $this->planHasAccounting,
                'is_active' => true,
            ]);
            $this->feedbackMessage = "New SaaS Plan [{$plan->name}] created successfully.";
        }

        $this->showPlanModal = false;
    }

    public function deletePlan(int $planId)
    {
        $plan = SubscriptionPlan::withCount('tenants')->findOrFail($planId);
        if ($plan->tenants_count > 0) {
            $this->errorMessage = "Cannot delete plan [{$plan->name}] because {$plan->tenants_count} vendors are subscribed to it.";

            return;
        }

        $name = $plan->name;
        $plan->delete();
        $this->feedbackMessage = "Plan [{$name}] deleted successfully.";
    }

    // ==========================================
    // 3. PLATFORM HALLS CRUD
    // ==========================================
    public function openEditHallModal(string $hallId)
    {
        $hall = Hall::with('venue')->findOrFail($hallId);
        $this->editingHallId = $hall->id;
        $this->editHallName = $hall->name;
        $this->editHallCity = $hall->venue->city ?? 'Lahore';
        $this->editHallMinCapacity = $hall->min_capacity;
        $this->editHallMaxCapacity = $hall->max_capacity;
        $this->editHallBaseRent = (float) $hall->base_rent;
        $this->editHallIsActive = (bool) $hall->is_active;
        $this->showEditHallModal = true;
    }

    public function closeEditHallModal()
    {
        $this->showEditHallModal = false;
        $this->editingHallId = null;
    }

    public function updateHall()
    {
        $this->validate([
            'editHallName' => 'required|string|min:3|max:120',
            'editHallMinCapacity' => 'required|integer|min:10',
            'editHallMaxCapacity' => 'required|integer|min:20',
            'editHallBaseRent' => 'required|numeric|min:5000',
        ]);

        $hall = Hall::findOrFail($this->editingHallId);
        $hall->update([
            'name' => $this->editHallName,
            'min_capacity' => $this->editHallMinCapacity,
            'max_capacity' => $this->editHallMaxCapacity,
            'base_rent' => $this->editHallBaseRent,
            'is_active' => $this->editHallIsActive,
        ]);

        $this->showEditHallModal = false;
        $this->feedbackMessage = "Hall [{$hall->name}] updated successfully.";
    }

    public function deleteHall(string $hallId)
    {
        $hall = Hall::findOrFail($hallId);
        $name = $hall->name;
        $hall->bookings()->delete();
        $hall->delete();

        $this->feedbackMessage = "Hall [{$name}] deleted successfully.";
    }

    public function toggleHallStatus(string $hallId)
    {
        $hall = Hall::findOrFail($hallId);
        $hall->update([
            'is_active' => ! $hall->is_active,
        ]);
        $this->feedbackMessage = "Hall [{$hall->name}] status changed to ".($hall->is_active ? 'Published' : 'Hidden').'.';
    }

    // ==========================================
    // 4. SUBSCRIPTION PAYMENTS CRUD
    // ==========================================
    public function approveSubscriptionPayment(string $paymentId)
    {
        $payment = SubscriptionPayment::with(['tenant', 'plan'])->findOrFail($paymentId);

        $tenant = $payment->tenant;

        $currentExpiry = ($tenant->subscription_expires_at && $tenant->subscription_expires_at->isFuture())
            ? $tenant->subscription_expires_at
            : Carbon::now();

        $newExpiry = match (strtoupper($payment->billing_cycle)) {
            'WEEKLY' => $currentExpiry->copy()->addDays(7),
            'YEARLY' => $currentExpiry->copy()->addMonths(12),
            default => $currentExpiry->copy()->addMonths(1),
        };

        $tenant->update([
            'subscription_plan_id' => $payment->subscription_plan_id,
            'subscription_status' => 'ACTIVE',
            'subscription_expires_at' => $newExpiry,
            'is_active' => true,
        ]);

        $payment->update([
            'status' => 'APPROVED',
            'approved_by_user_id' => Auth::id(),
            'approved_at' => Carbon::now(),
        ]);

        AuditLog::log(
            'SUBSCRIPTION_PAYMENT_APPROVED',
            'Approved subscription payment of PKR '.number_format($payment->amount)." for vendor [{$tenant->name}]. Extended to {$newExpiry->format('d M Y')}.",
            $tenant->id,
            'SubscriptionPayment',
            $payment->id
        );

        $this->feedbackMessage = "Subscription payment approved. [{$tenant->name}] renewed until ".$newExpiry->format('d M Y').'.';
    }

    public function rejectSubscriptionPayment(string $paymentId)
    {
        $payment = SubscriptionPayment::with('tenant')->findOrFail($paymentId);
        $payment->update([
            'status' => 'REJECTED',
            'approved_by_user_id' => Auth::id(),
            'approved_at' => Carbon::now(),
        ]);

        AuditLog::log(
            'SUBSCRIPTION_PAYMENT_REJECTED',
            'Rejected subscription payment of PKR '.number_format($payment->amount)." for vendor [{$payment->tenant->name}].",
            $payment->tenant_id,
            'SubscriptionPayment',
            $payment->id
        );

        $this->feedbackMessage = 'Subscription payment marked as REJECTED.';
    }

    // ==========================================
    // 5. BOOKINGS STATUS MANAGEMENT
    // ==========================================
    public function updateBookingStatus(string $bookingId, string $status)
    {
        $booking = Booking::findOrFail($bookingId);
        $booking->update(['status' => $status]);
        $this->feedbackMessage = "Booking [{$booking->booking_reference}] updated to {$status}.";
    }

    public function deleteBooking(string $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        $ref = $booking->booking_reference;
        $booking->payments()->delete();
        $booking->delete();
        $this->feedbackMessage = "Booking [{$ref}] deleted.";
    }

    // ==========================================
    // 6. USER DIRECTORY & ACCESS CONTROL
    // ==========================================
    public function toggleUserStatus(int $userId): void
    {
        $user = User::findOrFail($userId);
        if ($user->id === Auth::id()) {
            $this->errorMessage = 'You cannot suspend your own Super Admin account.';

            return;
        }

        $newStatus = ($user->status === 'SUSPENDED') ? 'ACTIVE' : 'SUSPENDED';
        $user->update(['status' => $newStatus]);

        AuditLog::log(
            'USER_STATUS_TOGGLED',
            "Super Admin changed status of user [{$user->name}] ({$user->email}) to {$newStatus}.",
            $user->tenant_id,
            'User',
            (string) $user->id
        );

        $this->feedbackMessage = "User [{$user->name}] status changed to {$newStatus}.";
    }

    public function resetUserPassword(int $userId): void
    {
        $user = User::findOrFail($userId);
        $tempPassword = 'Password123!';
        $user->update(['password' => Hash::make($tempPassword)]);

        AuditLog::log(
            'USER_PASSWORD_RESET',
            "Super Admin reset password for user [{$user->name}] ({$user->email}).",
            $user->tenant_id,
            'User',
            (string) $user->id
        );

        $this->feedbackMessage = "Password for user [{$user->name}] reset to: {$tempPassword}";
    }

    public function render()
    {
        $totalTenants = Tenant::count();
        $pendingTenants = Tenant::where('verification_status', 'PENDING')->count();
        $totalHalls = Hall::count();
        $totalBookings = Booking::count();
        $totalGmv = Booking::sum('grand_total');

        // Website Analytics & Traffic
        $totalPageVisits = PageVisit::count();
        $totalHallViews = HallView::count();
        $totalWishlists = HallWishlist::count();
        $totalRegisteredUsers = User::count();
        $todayVisits = PageVisit::whereDate('visited_at', today())->count();
        $todayHallViews = HallView::whereDate('viewed_at', today())->count();

        // Real-time Active Sessions & Unique Visitors
        try {
            $activeSessionsCount = DB::table('sessions')
                ->where('last_activity', '>=', now()->subMinutes(15)->getTimestamp())
                ->count();
        } catch (\Throwable $e) {
            $activeSessionsCount = 0;
        }

        if ($activeSessionsCount === 0) {
            $activeSessionsCount = PageVisit::where('visited_at', '>=', now()->subMinutes(15))->distinct('ip_address')->count('ip_address');
            if ($activeSessionsCount === 0) {
                $activeSessionsCount = 1;
            }
        }

        $uniqueVisitorsCount = PageVisit::distinct('ip_address')->count('ip_address');
        if ($uniqueVisitorsCount === 0) {
            $uniqueVisitorsCount = max(1, $totalRegisteredUsers);
        }

        // User Directory query with filters
        $usersQuery = User::with('tenant');
        if (! empty($this->userSearch)) {
            $search = '%'.trim($this->userSearch).'%';
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('phone', 'like', $search);
            });
        }
        if ($this->userRoleFilter !== 'ALL') {
            $usersQuery->where('role', $this->userRoleFilter);
        }
        if ($this->userStatusFilter !== 'ALL') {
            $usersQuery->where('status', $this->userStatusFilter);
        }
        $users = $usersQuery->latest()->take(100)->get();

        $totalCustomers = User::whereIn('role', ['CUSTOMER', 'ROLE_CUSTOMER'])->count();
        $totalVendors = User::whereIn('role', ['VENDOR', 'HALL_OWNER', 'ADMIN', 'ROLE_TENANT_OWNER'])->count();
        $totalStaff = User::whereNotIn('role', ['CUSTOMER', 'ROLE_CUSTOMER', 'VENDOR', 'HALL_OWNER', 'ADMIN', 'ROLE_TENANT_OWNER', 'SUPER_ADMIN', 'ROLE_SUPER_ADMIN'])->count();
        $totalSuspendedUsers = User::where('status', 'SUSPENDED')->count();

        $topHallsByViews = Hall::with(['venue', 'tenant'])
            ->withCount('hallViews as total_views')
            ->orderByDesc('total_views')
            ->take(10)
            ->get();

        $topHallsByWishlists = Hall::with(['venue', 'tenant'])
            ->withCount('hallWishlists as total_wishlists')
            ->orderByDesc('total_wishlists')
            ->take(10)
            ->get();

        $recentVisits = PageVisit::with('user')
            ->latest()
            ->take(20)
            ->get();

        $tenants = Tenant::with(['subscriptionPlan'])->withCount(['halls', 'bookings', 'users'])->latest()->get();
        $plans = SubscriptionPlan::withCount('tenants')->get();
        $subscriptionPayments = SubscriptionPayment::with(['tenant', 'plan'])->latest()->get();
        $halls = Hall::with(['venue', 'tenant'])->latest()->get();
        $recentBookings = Booking::with(['hall.venue', 'tenant'])->latest()->take(30)->get();
        $auditLogs = AuditLog::with(['tenant', 'user'])->latest()->take(30)->get();

        // Platform Tasks & Action Items
        $platformTasks = PlatformTask::with('creator')->latest()->get();
        $pendingTasksCount = PlatformTask::where('is_completed', false)->count();
        $completedTasksCount = PlatformTask::where('is_completed', true)->count();
        $pendingApprovalsCount = Tenant::where('verification_status', 'PENDING')->count();
        $pendingSubscriptionPaymentsCount = SubscriptionPayment::where('status', 'PENDING')->count();

        return view('livewire.admin-dashboard', [
            'totalTenants' => $totalTenants,
            'pendingTenants' => $pendingTenants,
            'totalHalls' => $totalHalls,
            'totalBookings' => $totalBookings,
            'totalGmv' => $totalGmv,
            'totalPageVisits' => $totalPageVisits,
            'totalHallViews' => $totalHallViews,
            'totalWishlists' => $totalWishlists,
            'totalRegisteredUsers' => $totalRegisteredUsers,
            'todayVisits' => $todayVisits,
            'todayHallViews' => $todayHallViews,
            'activeSessionsCount' => $activeSessionsCount,
            'uniqueVisitorsCount' => $uniqueVisitorsCount,
            'users' => $users,
            'totalCustomers' => $totalCustomers,
            'totalVendors' => $totalVendors,
            'totalStaff' => $totalStaff,
            'totalSuspendedUsers' => $totalSuspendedUsers,
            'topHallsByViews' => $topHallsByViews,
            'topHallsByWishlists' => $topHallsByWishlists,
            'recentVisits' => $recentVisits,
            'tenants' => $tenants,
            'plans' => $plans,
            'subscriptionPayments' => $subscriptionPayments,
            'halls' => $halls,
            'recentBookings' => $recentBookings,
            'auditLogs' => $auditLogs,
            'platformTasks' => $platformTasks,
            'pendingTasksCount' => $pendingTasksCount,
            'completedTasksCount' => $completedTasksCount,
            'pendingApprovalsCount' => $pendingApprovalsCount,
            'pendingSubscriptionPaymentsCount' => $pendingSubscriptionPaymentsCount,
        ]);
    }
}
