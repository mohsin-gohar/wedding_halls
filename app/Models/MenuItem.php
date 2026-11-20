<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'category',
        'name',
        'description',
        'unit_cost',
        'raw_meat_ratio_kg',
        'raw_rice_ratio_kg',
        'raw_oil_ratio_liters',
        'min_guest_count',
        'is_active',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'raw_meat_ratio_kg' => 'decimal:4',
        'raw_rice_ratio_kg' => 'decimal:4',
        'raw_oil_ratio_liters' => 'decimal:4',
        'min_guest_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /** Matches the menu_items.category enum created in 2026_10_09_000002. */
    public function categoryOptions(): array
    {
        return [
            'STARTERS',
            'MAINS',
            'RICE',
            'BREADS',
            'DESSERTS',
            'BEVERAGES',
            'SALADS',
        ];
    }

    public function cuisineOptions(): array
    {
        return [
            'MUGHAL',
            'CONTINENTAL',
            'DESI',
            'ITALIAN',
            'CHINESE',
            'ARABIC',
            'FRENCH',
        ];
    }

    public function isStarter(): bool
    {
        return in_array($this->category, ['STARTERS', 'STARTER'], true);
    }

    public function isMain(): bool
    {
        return in_array($this->category, ['MAINS', 'MAIN'], true);
    }

    public function isDessert(): bool
    {
        return in_array($this->category, ['DESSERTS', 'DESSERT'], true);
    }

    public function isBeverage(): bool
    {
        return in_array($this->category, ['BEVERAGES', 'BEVERAGE'], true);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function pricePerHead(int $guestCount): float
    {
        return $this->unit_cost * $guestCount;
    }

    public function meetsMinGuestCount(int $guestCount): bool
    {
        // Legacy rows have no minimum; treat them as available for any count
        // instead of dereferencing a missing attribute.
        $minimum = $this->min_guest_count === null ? 0 : (int) $this->min_guest_count;

        return $guestCount >= $minimum;
    }

    /** Backwards-compatible alias used by older catering menu callers. */
    public function servesGuests(int $guestCount): bool
    {
        return $this->meetsMinGuestCount($guestCount);
    }
}
