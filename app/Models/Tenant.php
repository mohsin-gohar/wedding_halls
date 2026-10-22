<?php

namespace App\Models;

use App\Services\SubscriptionService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'subdomain',
        'email',
        'phone',
        'address',
        'city',
        'province',
        'logo',
        'subscription_plan',
        'subscription_plan_id',
        'subscription_status',
        'billing_cycle',
        'subscription_starts_at',
        'subscription_expires_at',
        'verification_status',
        'business_type',
        'ntn_number',
        'owner_cnic',
        'verification_notes',
        'website_settings',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'website_settings' => 'array',
        'subscription_starts_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
    ];

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

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    public function halls(): HasMany
    {
        return $this->hasMany(Hall::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function staffAttendances(): HasMany
    {
        return $this->hasMany(StaffAttendance::class);
    }

    public function staffLeaves(): HasMany
    {
        return $this->hasMany(StaffLeave::class);
    }

    public function staffPayrolls(): HasMany
    {
        return $this->hasMany(StaffPayroll::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
