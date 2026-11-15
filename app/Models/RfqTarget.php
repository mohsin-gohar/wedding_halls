<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqTarget extends Model
{
    use HasUuids;

    protected $table = 'rfq_targets';

    protected $fillable = ['rfq_id', 'tenant_id', 'viewed_at'];

    protected $casts = ['viewed_at' => 'datetime'];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RFQ::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
