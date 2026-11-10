<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformMetrics extends Model
{
    use HasFactory;

    protected $table = 'platform_metrics';

    protected $fillable = [
        'tenant_id',
        'metric_type', // bookings, revenue, attendance, inventory
        'metric_date',
        'metric_value',
        'metadata', // JSON data
    ];

    protected $casts = [
        'metric_date' => 'date',
        'metadata' => 'array',
    ];

    // No direct relationships - used for analytics aggregation

    /** * Scope: Only specific metric type */
    public function scopeByType($query, $type)
    {
        return $query->where('metric_type', $type);
    }

    /** * Scope: For a specific date */
    public function scopeOnDate($query, $date)
    {
        return $query->where('metric_date', $date);
    }

    /** * Scope: For a specific tenant */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /** * Get formatted value */
    public function getFormattedValueAttribute(): string
    {
        return number_format($this->metric_value);
    }
}