<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffPayroll extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'staff_payrolls';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'month_year',
        'basic_salary',
        'overtime_hours',
        'overtime_rate',
        'allowances',
        'deductions',
        'net_salary',
        'status', // DRAFT, APPROVED, PAID
        'paid_at',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'allowances' => 'decimal:2',
        'deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function calculateNet(float $basic, float $otHours, float $otRate, float $allowances, float $deductions): float
    {
        $otPay = $otHours * $otRate;
        return max(0, round($basic + $otPay + $allowances - $deductions, 2));
    }
}
