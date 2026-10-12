<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UtilityLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'generator_run_hours',
        'diesel_liters_consumed',
        'diesel_rate_per_liter',
        'wapda_units_consumed',
        'wapda_unit_rate',
        'total_utility_cost',
        'logged_by',
        'notes',
    ];

    protected $casts = [
        'generator_run_hours' => 'decimal:2',
        'diesel_liters_consumed' => 'decimal:2',
        'diesel_rate_per_liter' => 'decimal:2',
        'wapda_units_consumed' => 'decimal:2',
        'wapda_unit_rate' => 'decimal:2',
        'total_utility_cost' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function calculateTotalCost(): float
    {
        $dieselCost = (float) $this->diesel_liters_consumed * (float) $this->diesel_rate_per_liter;
        $wapdaCost = (float) $this->wapda_units_consumed * (float) $this->wapda_unit_rate;
        return round($dieselCost + $wapdaCost, 2);
    }
}
