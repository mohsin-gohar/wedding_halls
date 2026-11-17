<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollPolicy extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'overtime_rate_per_hour',
        'late_deduction_per_day',
        'absence_deduction_per_day',
        'half_day_deduction',
        'unpaid_leave_deduction_per_day',
        'default_basic_salary',
        'updated_by',
    ];

    protected $casts = [
        'overtime_rate_per_hour' => 'decimal:2',
        'late_deduction_per_day' => 'decimal:2',
        'absence_deduction_per_day' => 'decimal:2',
        'half_day_deduction' => 'decimal:2',
        'unpaid_leave_deduction_per_day' => 'decimal:2',
        'default_basic_salary' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** Tenant-specific policy with safe defaults, created on first use. */
    public static function forTenant(string $tenantId): self
    {
        return static::firstOrCreate(['tenant_id' => $tenantId]);
    }
}
