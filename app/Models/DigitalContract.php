<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DigitalContract extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'booking_id',
        'tenant_id',
        'client_id',
        'vendor_id',
        'contract_number',
        'terms_conditions',
        'status',
        'signature_type',
        'signature_url',
    ];

    protected $casts = [
        'booking_id' => 'uuid',
        'tenant_id' => 'uuid',
        'client_id' => 'uuid',
        'vendor_id' => 'uuid',
        'contract_number' => 'string',
        'status' => 'string',
        'signature_type' => 'string',
        'signature_url' => 'string',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            'DRAFT' => 'Draft',
            'SENT' => 'Sent',
            'SIGNED' => 'Signed',
            'EXPIRED' => 'Expired',
            default => $this->status,
        };
    }

    public function isDraft(): bool
    {
        return $this->status === 'DRAFT';
    }

    public function isSigned(): bool
    {
        return $this->status === 'SIGNED';
    }

    public function isExpired(): bool
    {
        return $this->status === 'EXPIRED';
    }
}