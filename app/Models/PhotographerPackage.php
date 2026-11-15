<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PhotographerPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'hourly_rate',
        'full_day_rate',
        'includes_drone',
        'includes_sameday_edit',
        'addons',
        'is_active',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'full_day_rate' => 'decimal:2',
        'includes_drone' => 'boolean',
        'includes_sameday_edit' => 'boolean',
        'addons' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function bookings(): MorphMany
    {
        return $this->morphMany(ServiceBooking::class, 'bookable');
    }

    public function isBasic(): bool
    {
        return $this->name === 'BASIC' || $this->slug === 'basic';
    }

    public function isGold(): bool
    {
        return $this->name === 'GOLD' || $this->slug === 'gold';
    }

    public function isPlatinum(): bool
    {
        return $this->name === 'PLATINUM' || $this->slug === 'platinum';
    }

    public function priceForGuestCount(int $guestCount): decimal
    {
        // Full day rate typically covers 6-8 hours
        $dayRate = $this->full_day_rate;
        $hourlyEquivalent = $this->hourly_rate * 6; // Assume 6 hour minimum

        return max($dayRate, $hourlyEquivalent);
    }

    public function addonPrice(string $addon): decimal
    {
        $addons = $this->addons ?? [];

        return $addons[$addon] ?? 0;
    }
}
