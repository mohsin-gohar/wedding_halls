<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'category',
        'name',
        'description',
        'unit_cost',
        'raw_meat_ratio_kg',
        'raw_rice_ratio_kg',
        'raw_oil_ratio_liters',
        'is_active',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'raw_meat_ratio_kg' => 'decimal:4',
        'raw_rice_ratio_kg' => 'decimal:4',
        'raw_oil_ratio_liters' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
