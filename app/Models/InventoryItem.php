<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'inventory_items';

    protected $fillable = [
        'tenant_id',
        'name',
        'category', // RAW_FOOD, BEVERAGES, CROCKERY, LINEN, FUEL, CLEANING, OTHER
        'unit', // KG, LITRE, PIECES, BAGS, CYLINDERS
        'current_stock',
        'min_threshold',
        'unit_cost',
        'supplier_name',
        'supplier_phone',
        'is_active',
    ];

    protected $casts = [
        'current_stock' => 'decimal:2',
        'min_threshold' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(InventoryLog::class, 'inventory_item_id')->latest();
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->min_threshold;
    }
}
