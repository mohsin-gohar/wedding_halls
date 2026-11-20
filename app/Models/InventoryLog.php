<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLog extends Model
{
    use HasFactory, HasUuids;

    /** Explicit, exhaustive set of stock movement types. */
    public const TYPES = ['STOCK_IN', 'STOCK_OUT', 'ADJUSTMENT', 'WASTAGE'];

    protected $table = 'inventory_logs';

    protected $fillable = [
        'tenant_id',
        'inventory_item_id',
        'type', // STOCK_IN, STOCK_OUT, ADJUSTMENT, WASTAGE
        'quantity',
        'unit_price',
        'total_cost',
        'reference_note',
        'logged_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
