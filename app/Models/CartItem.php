<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'cart_id',
        'bookable_type',
        'bookable_id',
        'quantity',
        'vendor_share',
        'vendor_commission',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'vendor_share' => 'decimal:2',
        'vendor_commission' => 'decimal:2',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }

    public function vendorShare(): decimal
    {
        return $this->vendor_share;
    }

    public function commissionRate(): decimal
    {
        return $this->vendor_commission;
    }

    public function calculateVendorCommission(float $baseRate): void
    {
        // Calculate commission based on tenant's subscription tier
        $tenant = $this->cart?->tenant;
        $commissionRate = $tenant?->commissionRate() ?? 20.00;
        $this->vendor_commission = $this->vendor_share * ($commissionRate / 100);
    }
}
