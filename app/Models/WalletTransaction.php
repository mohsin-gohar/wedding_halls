<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasUuids;

    protected $table = 'wallet_transactions';

    protected $fillable = [
        'tenant_id', 'vendor_wallet_id', 'type', 'source', 'amount',
        'balance_after', 'service_booking_id', 'vendor_payout_id', 'reference', 'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public const TYPES = ['CREDIT', 'DEBIT'];

    public const SOURCES = ['BOOKING_PAYMENT', 'COMMISSION', 'PAYOUT', 'REFUND', 'ADJUSTMENT', 'SUBSCRIPTION'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(VendorWallet::class, 'vendor_wallet_id');
    }

    public function serviceBooking(): BelongsTo
    {
        return $this->belongsTo(ServiceBooking::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(VendorPayout::class, 'vendor_payout_id');
    }
}
