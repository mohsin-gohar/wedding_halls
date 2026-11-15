<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorPayout extends Model
{
    use HasUuids;

    protected $table = 'vendor_payouts';

    protected $fillable = [
        'tenant_id', 'amount', 'method', 'account_details',
        'status', 'reference', 'note', 'processed_by', 'requested_at', 'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'account_details' => 'array',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public const STATUSES = ['REQUESTED', 'PROCESSING', 'PAID', 'REJECTED'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
