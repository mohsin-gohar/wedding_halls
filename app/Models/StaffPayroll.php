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
        'present_days',
        'absent_days',
        'late_days',
        'half_days',
        'unpaid_leave_days',
        'overtime_pay',
        'late_deduction',
        'absence_deduction',
        'half_day_deduction',
        'unpaid_leave_deduction',
        'bonuses',
        'advances',
        'other_deductions',
        'net_salary',
        'status', // DRAFT, APPROVED, PAID
        'paid_at',
        'approved_by',
        'approved_at',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'allowances' => 'decimal:2',
        'deductions' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'late_deduction' => 'decimal:2',
        'absence_deduction' => 'decimal:2',
        'half_day_deduction' => 'decimal:2',
        'unpaid_leave_deduction' => 'decimal:2',
        'bonuses' => 'decimal:2',
        'advances' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'paid_at' => 'datetime',
        'approved_at' => 'datetime',
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
