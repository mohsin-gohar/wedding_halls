<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGateway extends Model
{
    protected $table = 'payment_gateways';

    protected $fillable = [
        'tenant_id', 'gateway_name', 'gateway_slug', 'status', 'currency', 'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public const STATUSES = ['ACTIVE', 'INACTIVE'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }
}
