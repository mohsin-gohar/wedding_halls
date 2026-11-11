<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_weekly',
        'price_monthly',
        'price_yearly',
        'max_halls',
        'max_staff',
        'max_monthly_bookings',
        'has_custom_domain',
        'has_kitchen_kot',
        'has_whatsapp_automation',
        'has_accounting_reports',
        'trial_days',
        'is_active',
    ];

    protected $casts = [
        'price_weekly' => 'float',
        'price_monthly' => 'float',
        'price_yearly' => 'float',
        'max_halls' => 'integer',
        'max_staff' => 'integer',
        'max_monthly_bookings' => 'integer',
        'has_custom_domain' => 'boolean',
        'has_kitchen_kot' => 'boolean',
        'has_whatsapp_automation' => 'boolean',
        'has_accounting_reports' => 'boolean',
        'trial_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function isFreeTier(): bool
    {
        return $this->name === 'FREE' || $this->slug === 'free';
    }

    public function isProTier(): bool
    {
        return $this->name === 'PRO' || $this->slug === 'pro';
    }

    public function isEnterpriseTier(): bool
    {
        return $this->name === 'ENTERPRISE' || $this->slug === 'enterprise';
    }

    public function commissionRate(): float
    {
        return match ($this->name) {
            'FREE' => 20.00,
            'PRO' => 15.00,
            'ENTERPRISE' => 10.00,
            default => 20.00,
        };
    }
}
