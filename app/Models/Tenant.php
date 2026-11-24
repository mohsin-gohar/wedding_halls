<?php

namespace App\Models;

use App\Services\SubscriptionService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Tenant extends Model
{
    use HasFactory, HasUuids;

    public const VERIFICATION_STATUS_APPROVED = 'APPROVED';

    protected $table = 'tenants';

    protected $fillable = [
        // Core identity
        'name',
        'slug',
        'subdomain',
        'email',
        'phone',
        'address',
        'logo',
        'area',
        'city',
        'province',
        'country',

        // Multi-vendor ecosystem
        'vendor_category_id',
        'about',
        'cover_image',
        'service_areas',
        'rating_avg',
        'rating_count',
        'min_budget',
        'max_budget',
        'is_featured',
        'featured_until',
        'verified_at',
        'onboarding_completed_at',
        'onboarding_step',
        'marketplace_visible',
        'profile_views',

        // Subscription & billing
        'subscription_plan',
        'subscription_plan_id',
        'subscription_status',
        'billing_cycle',
        'subscription_starts_at',
        'subscription_expires_at',
        'trial_ends_at',
        'currency',
        'billing_enabled',
        'timezone',
        'plan',
        'settings',
        'weekly_off_days',
        'shift_windows',
        'shift_buffer_minutes',
        'hold_expiry_hours',

        // Verification
        'verification_status',
        'business_type',
        'ntn_number',
        'owner_cnic',
        'verification_notes',
        'website_settings',

        // Limits & modules (WIP enterprise fields)
        'max_halls',
        'max_staff',
        'max_bookings_per_day',
        'commission_rate',
        'free_access_days',
        'kitchen_module_enabled',

        // Status
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'website_settings' => 'array',
        'settings' => 'array',
        'weekly_off_days' => 'array',
        'shift_windows' => 'array',
        'shift_buffer_minutes' => 'integer',
        'hold_expiry_hours' => 'integer',
        'service_areas' => 'array',
        'subscription_starts_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'verified_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
        'featured_until' => 'date:Y-m-d',
        'billing_enabled' => 'boolean',
        'kitchen_module_enabled' => 'boolean',
        'free_access_days' => 'integer',
        'max_halls' => 'integer',
        'max_staff' => 'integer',
        'max_bookings_per_day' => 'integer',
        'commission_rate' => 'decimal:2',
        'rating_avg' => 'decimal:2',
        'rating_count' => 'integer',
        'min_budget' => 'decimal:2',
        'max_budget' => 'decimal:2',
        'is_featured' => 'boolean',
        'marketplace_visible' => 'boolean',
        'onboarding_step' => 'integer',
        'profile_views' => 'integer',
    ];

    /**
     * Weekday numbers (Carbon: 0 = Sunday … 6 = Saturday) that are weekly offs.
     * Missing configuration keeps Sunday so existing venues do not change.
     * An explicit empty list means the venue has no weekly off.
     *
     * @return array<int, int>
     */
    public function weeklyOffDays(): array
    {
        if ($this->weekly_off_days === null) {
            return [0];
        }

        $days = array_values(array_unique(array_map('intval', (array) $this->weekly_off_days)));

        return array_values(array_filter($days, fn (int $day) => $day >= 0 && $day <= 6));
    }

    // ─── RELATIONSHIPS ───────────────────────────────────────────

    /** * A tenant has many users (staff/owners) */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** * A tenant has many venues (halls) */
    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    /** * The marketplace category this vendor belongs to. */
    public function vendorCategory(): BelongsTo
    {
        return $this->belongsTo(VendorCategory::class);
    }

    /** * A tenant has many generic services (any category). */
    public function vendorServices(): HasMany
    {
        return $this->hasMany(VendorService::class);
    }

    /** * A tenant has many bundle packages (any category). */
    public function vendorPackages(): HasMany
    {
        return $this->hasMany(VendorPackage::class);
    }

    /** * A tenant's portfolio media (images + videos). */
    public function portfolioMedia(): HasMany
    {
        return $this->hasMany(VendorPortfolioMedia::class)->orderBy('sort');
    }

    /** * A tenant's verification documents. */
    public function vendorDocuments(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }

    /** * A tenant's availability slots. */
    public function availability(): HasMany
    {
        return $this->hasMany(VendorAvailability::class);
    }

    /** * A tenant's generic service bookings (non-hall categories). */
    public function serviceBookings(): HasMany
    {
        return $this->hasMany(ServiceBooking::class);
    }

    /** * A tenant's service payments. */
    public function servicePayments(): HasMany
    {
        return $this->hasMany(ServicePayment::class);
    }

    /** * A tenant's RFQ bids (vendor staff users' bids via users.tenant_id). */
    public function rfqBids(): HasManyThrough
    {
        return $this->hasManyThrough(RFQBid::class, User::class, 'tenant_id', 'vendor_id', 'id', 'id');
    }

    /** * RFQ requests targeted at this vendor. */
    public function rfqTargets(): HasMany
    {
        return $this->hasMany(RfqTarget::class);
    }

    /** * A tenant has many halls */
    public function halls(): HasMany
    {
        return $this->hasMany(Hall::class);
    }

    /** * A tenant has many bookings */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** * A tenant has many packages */
    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    /** * A tenant has many menu items */
    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    /** * A tenant has many payments */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** * A tenant has many subscription payments */
    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /** * A tenant has many expenses */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /** * A tenant has many audit logs */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /** * A tenant's wallet ledger transactions (financial audit trail). */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /** * A tenant's payout requests. */
    public function payouts(): HasMany
    {
        return $this->hasMany(VendorPayout::class);
    }

    /** * A tenant's support tickets. */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    /** * A tenant has many staff attendances */
    public function staffAttendances(): HasMany
    {
        return $this->hasMany(StaffAttendance::class);
    }

    /** * A tenant has many leave applications */
    public function staffLeaves(): HasMany
    {
        return $this->hasMany(StaffLeave::class);
    }

    /** * A tenant has many payroll records */
    public function staffPayrolls(): HasMany
    {
        return $this->hasMany(StaffPayroll::class);
    }

    /** * A tenant has many notifications */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /** * A tenant has many inventory items */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    /** * A tenant has many purchase orders */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /** * A tenant has many floor plans */
    public function floorPlans(): HasMany
    {
        return $this->hasMany(FloorPlan::class);
    }

    /** * Reviews targeting this vendor (polymorphic: reviewable_type = Tenant). */
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /** * Published (approved) reviews targeting this vendor — marketplace display. */
    public function approvedReviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable')->where('status', 'approved');
    }

    /** * The physical locations (branches) this business operates */
    public function vendorLocations(): HasMany
    {
        return $this->hasMany(VendorLocation::class);
    }

    /** * A tenant has many chat messages (stored per hall) */
    public function chatMessages(): HasManyThrough
    {
        return $this->hasManyThrough(
            HallChatMessage::class,
            Hall::class,
            'tenant_id',   // Foreign key on halls
            'hall_id',     // Foreign key on hall_chat_messages
            'id',          // Local key on tenants
            'id'           // Local key on halls
        );
    }

    /** * The subscription plan this tenant belongs to */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /** * The most recent subscription payment for this tenant */
    public function latestSubscriptionPayment(): HasOne
    {
        return $this->hasOne(SubscriptionPayment::class)->latestOfMany();
    }

    /**
     * Platform commission rate for this vendor, expressed as a percentage
     * (tenants.commission_rate, e.g. 20.00 = 20%).
     */
    public function commissionRate(): float
    {
        return (float) ($this->commission_rate ?? 0);
    }

    // ─── PUBLIC VISIBILITY SCOPES ───────────────────────────────

    /**
     * Only approved, active tenants with an unexpired entitlement may appear
     * in public listings. A paid subscription retains access through the
     * configured grace period; an ended trial without a paid expiry does not.
     */
    public function scopePublicVisible($query)
    {
        $now = now();
        $graceBoundary = $now->copy()->subDays(SubscriptionService::GRACE_PERIOD_DAYS);

        return $query
            ->where('tenants.is_active', true)
            ->where('tenants.verification_status', self::VERIFICATION_STATUS_APPROVED)
            ->whereIn('tenants.subscription_status', ['ACTIVE', 'TRIAL', 'GRACE_PERIOD'])
            ->where(function ($entitlement) use ($graceBoundary): void {
                $entitlement->whereNull('tenants.subscription_expires_at')
                    ->orWhere('tenants.subscription_expires_at', '>=', $graceBoundary);
            })
            ->where(function ($trial) use ($now): void {
                $trial->whereNotNull('tenants.subscription_expires_at')
                    ->orWhere('tenants.subscription_status', '!=', 'TRIAL')
                    ->orWhereNull('tenants.trial_ends_at')
                    ->orWhere('tenants.trial_ends_at', '>=', $now);
            });
    }

    /** Vendor-facing marketplace visibility additionally requires the opt-in flag. */
    public function scopeMarketplaceVisible($query)
    {
        return $query->publicVisible()->where('tenants.marketplace_visible', true);
    }

    // ─── STATE CHECKS ────────────────────────────────────────────

    public function isApproved(): bool
    {
        return $this->verification_status === 'APPROVED' && $this->is_active;
    }

    public function isPending(): bool
    {
        return $this->verification_status === 'PENDING';
    }

    public function isSubscriptionActive(): bool
    {
        if (in_array($this->subscription_status, ['ACTIVE', 'TRIAL'])) {
            if ($this->subscription_expires_at && $this->subscription_expires_at->isPast()) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function isLocked(): bool
    {
        return app(SubscriptionService::class)->isLocked($this);
    }

    public function canAddHall(): bool
    {
        if (! $this->subscriptionPlan) {
            return true; // Default fallback
        }
        if ($this->subscriptionPlan->max_halls === -1) {
            return true;
        }

        return $this->halls()->count() < $this->subscriptionPlan->max_halls;
    }

    public function canAddStaff(): bool
    {
        if (! $this->subscriptionPlan) {
            return true;
        }
        if ($this->subscriptionPlan->max_staff === -1) {
            return true;
        }

        return $this->users()->count() < $this->subscriptionPlan->max_staff;
    }

    /** * Check if tenant is active and verified */
    public function isVerified(): bool
    {
        return $this->is_active && $this->verification_status === 'APPROVED';
    }

    /** * Check if tenant can accept bookings */
    public function canAcceptBookings(): bool
    {
        return $this->is_active
            && $this->verification_status === 'APPROVED'
            && ! $this->isLocked();
    }

    /** * Check if free access is still active */
    public function hasFreeAccess(): bool
    {
        if ($this->free_access_days <= 0) {
            return false;
        }
        if (! $this->trial_ends_at) {
            return false;
        }

        return now() < $this->trial_ends_at;
    }

    /** * Get formatted capacity */
    public function getMaxCapacityAttribute(): string
    {
        return $this->max_halls.' halls / '.$this->max_staff.' staff';
    }
}
