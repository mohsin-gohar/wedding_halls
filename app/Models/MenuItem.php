<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'is_active',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'raw_meat_ratio_kg' => 'decimal:4',
        'raw_rice_ratio_kg' => 'decimal:4',
        'raw_oil_ratio_liters' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function categoryOptions(): array
    {
        return [
            'STARTER',
            'MAIN',
            'DESSERT',
            'BEVERAGE',
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
        return $this->category === 'STARTER';
    }

    public function isMain(): bool
    {
        return $this->category === 'MAIN';
    }

    public function isDessert(): bool
    {
        return $this->category === 'DESSERT';
    }

    public function isBeverage(): bool
    {
        return $this->category === 'BEVERAGE';
    }

    public function pricePerHead(int $guestCount): decimal
    {
        return $this->unit_cost * $guestCount;
    }

    public function meetsMinGuestCount(int $guestCount): bool
    {
        return $guestCount >= $this->min_guest_count;
    }
}
