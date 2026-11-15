<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RFQ extends Model
{
    use HasFactory;

    protected $table = 'rfqs';

    protected $fillable = [
        'client_id',
        'tenant_id',
        'vendor_category_id',
        'title',
        'event_date',
        'city',
        'guest_count',
        'budget_range',
        'requirements',
        'status',
        'preferences',
    ];

    protected $casts = [
        'budget_range' => 'string',
        'requirements' => 'string',
        'preferences' => 'array',
        'status' => 'string',
        'event_date' => 'date',
        'guest_count' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function vendorCategory(): BelongsTo
    {
        return $this->belongsTo(VendorCategory::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(RfqTarget::class, 'rfq_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function bids(): HasMany
    {
        return $this->hasMany(RFQBid::class, 'rfq_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'OPEN';
    }

    public function isClosed(): bool
    {
        return $this->status === 'CLOSED' || $this->status === 'AWARDED' || $this->status === 'CANCELLED';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'OPEN' => 'Open',
            'CLOSED' => 'Closed',
            'AWARDED' => 'Awarded',
            'CANCELLED' => 'Cancelled',
            default => $this->status,
        };
    }
}
