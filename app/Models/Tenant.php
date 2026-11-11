<?php

namespace App\Models;

use App\Services\SubscriptionService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Tenant extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'tenants';

    protected $fillable = [
        // Core identity
        'user_id',
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

        // Subscription & billing
        'subscription_plan',
        'subscription_plan_id',
        'subscription_status',
        'billing_cycle',
        'subscription_starts_at',
        'subscription_expires_at',
        'currency',
        'billing_enabled',
        'payment_gateway',
        'timezone',
        'plan',
        'settings',

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
        'free_access_days',
        'kitchen_module_enabled',

        // Status
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'website_settings' => 'array',
        'settings' => 'array',
        'subscription_starts_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
        'billing_enabled' => 'boolean',
        'kitchen_module_enabled' => 'boolean',
        'free_access_days' => 'integer',
        'max_halls' => 'integer',
        'max_staff' => 'integer',
        'max_bookings_per_day' => 'integer',
    ];

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

    /** * A tenant has many reviews */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** * A tenant has many chat messages */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(HallChatMessage::class);
    }

    /** * A tenant has many webhook logs */
    public function whatsappWebhookLogs(): HasMany
    {
        return $this->hasMany(WhatsappWebhookLog::class);
    }

    /** * The subscription plan this tenant belongs to */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /** * Get tenant subscription */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
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
        return $this->is_active && $this->verification_status === 'APPROVED';
    }

    /** * Check if free access is still active */
    public function hasFreeAccess(): bool
    {
        if ($this->free_access_days <= 0) return false;
        if (!$this->trial_ends_at) return false;
        return now() < $this->trial_ends_at;
    }

    /** * Get formatted capacity */
    public function getMaxCapacityAttribute(): string
    {
        return $this->max_halls . ' halls / ' . $this->max_staff . ' staff';
    }
}
