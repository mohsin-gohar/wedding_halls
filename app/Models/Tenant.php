<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'tenants';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'area',
        'city',
        'country',
        'address',
        'email',
        'phone',
        'is_active',
        'verification_status',
        'max_halls',
        'max_staff',
        'max_bookings_per_day',
        'free_access_days',
        'billing_enabled',
        'kitchen_module_enabled',
        'payment_gateway',
        'timezone',
        'currency',
        'plan',
        'settings',
        'subscription_plan_id',
        'subscription_status',
        'billing_cycle',
        'subscription_starts_at',
        'subscription_expires_at',
        'trial_ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'billing_enabled' => 'boolean',
        'kitchen_module_enabled' => 'boolean',
        'free_access_days' => 'integer',
        'max_halls' => 'integer',
        'max_staff' => 'integer',
        'max_bookings_per_day' => 'integer',
        'subscription_starts_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    // RELATIONSHIPS

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

    /** * A tenant has many halls (alias for venues->halls) */
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

    /** * A tenant has many payments */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
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

    /** * Get tenant subscription */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /** * Get tenant subscription plan */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
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
        if ($this->free_access_days <= 0) {
            return false;
        }
        if (! $this->trial_ends_at) {
            return false;
        }

        return now() < $this->trial_ends_at;
    }

    /** * Check if tenant is pending verification */
    public function isPending(): bool
    {
        return $this->verification_status === 'PENDING';
    }

    /** * Check if tenant is approved */
    public function isApproved(): bool
    {
        return $this->verification_status === 'APPROVED';
    }

    /** * Check if tenant subscription is active */
    public function isSubscriptionActive(): bool
    {
        return $this->subscription_status === 'ACTIVE' || $this->hasFreeAccess();
    }

    /** * Check if tenant can add more halls */
    public function canAddHall(): bool
    {
        if ($this->max_halls <= 0) {
            return true;
        } // Unlimited

        return $this->halls()->count() < $this->max_halls;
    }

    /** * Check if tenant can add more staff */
    public function canAddStaff(): bool
    {
        if ($this->max_staff <= 0) {
            return true;
        } // Unlimited

        return $this->users()->count() < $this->max_staff;
    }

    /** * Get formatted capacity */
    public function getMaxCapacityAttribute(): string
    {
        return $this->max_halls.' halls / '.$this->max_staff.' staff';
    }
}
