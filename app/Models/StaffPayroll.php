<?php

namespace App\Models;

use App\Concerns\GuardsCashOnlyMethod;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffPayroll extends Model
{
    use GuardsCashOnlyMethod, HasFactory, HasUuids;

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

    protected function cashOnlyMethodField(): string
    {
        return 'payment_method';
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public const MAX_MONEY_CENTS = 999_999_999_999;

    public const MAX_MONEY_AMOUNT = 9_999_999_999.99;

    public const MAX_RATE_AMOUNT = 99_999_999.99;

    public const MAX_HOURS = 9_999.99;

    public static function calculateNet(float $basic, float $otHours, float $otRate, float $allowances, float $deductions): float
    {
        foreach ([$basic, $otHours, $otRate, $allowances, $deductions] as $amount) {
            if (! is_finite($amount) || $amount < 0) {
                throw new \\InvalidArgumentException('Payroll values must be finite and non-negative.');
            }
        }

        if ($basic > self::MAX_MONEY_AMOUNT
            || $allowances > self::MAX_MONEY_AMOUNT
            || $deductions > self::MAX_MONEY_AMOUNT
            || $otRate > self::MAX_RATE_AMOUNT
            || $otHours > self::MAX_HOURS) {
            throw new \\InvalidArgumentException('Payroll values exceed the supported storage range.');
        }

        // Work in paisa to avoid binary-float drift in payroll totals.
        $basicCents = (int) round($basic * 100);
        $hoursHundredths = (int) round($otHours * 100);
        $rateCents = (int) round($otRate * 100);
        $overtimeCents = (int) round(($hoursHundredths * $rateCents) / 100);
        $allowanceCents = (int) round($allowances * 100);
        $deductionCents = (int) round($deductions * 100);

        $netCents = max(0, $basicCents + $overtimeCents + $allowanceCents - $deductionCents);

        if ($netCents > self::MAX_MONEY_CENTS) {
            throw new \\InvalidArgumentException('Calculated net salary exceeds the supported storage range.');
        }

        return $netCents / 100;
    }
}
