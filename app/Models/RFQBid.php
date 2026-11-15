<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RFQBid extends Model
{
    use HasFactory;

    protected $table = 'rfq_bids';

    protected $fillable = [
        'rfq_id',
        'vendor_id',
        'bid_amount',
        'proposal',
        'status',
    ];

    protected $casts = [
        'bid_amount' => 'decimal:2',
        'status' => 'string',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RFQ::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'PENDING' => 'Pending',
            'ACCEPTED' => 'Accepted',
            'REJECTED' => 'Rejected',
            'AWARDED' => 'Awarded',
            default => $this->status,
        };
    }

    public function isAccepted(): bool
    {
        return $this->status === 'ACCEPTED';
    }

    public function isAwarded(): bool
    {
        return $this->status === 'AWARDED';
    }

    public function isRejected(): bool
    {
        return $this->status === 'REJECTED';
    }
}
