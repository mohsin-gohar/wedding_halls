<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'tenant_id',
        'total_amount',
        'currency',
        'status',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'currency' => 'string',
        'status' => 'string',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function totalBeforeCommission(): float
    {
        return (float) $this->items()->sum(fn ($item) => (float) $item->vendor_share + (float) $item->vendor_commission);
    }

    public function totalVendorCommission(): float
    {
        return (float) $this->items()->sum(fn ($item) => (float) $item->vendor_commission);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'PENDING' => 'Pending',
            'CONFIRMED' => 'Confirmed',
            'PAID' => 'Paid',
            'FAILED' => 'Failed',
            'COMPLETED' => 'Completed',
            default => $this->status,
        };
    }

    public function canCheckout(): bool
    {
        return $this->status === 'CONFIRMED' && $this->totalAmount > 0;
    }
}
