<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrder extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'tenant_id',
        'po_number',
        'supplier_name',
        'total_amount',
        'status', // ORDERED, RECEIVED, PAID, CANCELLED
        'order_date',
        'items_json',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'order_date' => 'date',
        'items_json' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
