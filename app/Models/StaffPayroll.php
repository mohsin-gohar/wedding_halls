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

    /**
     * Calculate overtime pay in integer paisa to avoid binary floating-point drift.
     */
    public static function calculateOvertimePay(float $otHours, float $otRate): float
    {
        return self::calculateOvertimePaisa($otHours, $otRate) / 100;
    }

    private static function calculateOvertimePaisa(float $otHours, float $otRate): int
    {
        foreach (['overtime hours' => $otHours, 'overtime rate' => $otRate] as $field => $value) {
            if (! is_finite($value) || $value < 0) {
                throw new \InvalidArgumentException("Payroll {$field} must be a finite, non-negative number.");
            }
        }

        $maximumPaisa = 999_999_999_999;
        $roundedRate = round($otRate, 2);
        if ($roundedRate > ($maximumPaisa / 100)) {
            throw new \InvalidArgumentException('Payroll overtime rate exceeds the supported storage limit.');
        }

        $ratePaisa = (int) round($roundedRate * 100);
        if ($otHours > 10_000) {
            throw new \InvalidArgumentException('Payroll overtime hours exceed the supported limit.');
        }

        $hoursHundredths = (int) round($otHours * 100);

        $overtimePaisa = intdiv(($hoursHundredths * $ratePaisa) + 50, 100);
        if ($overtimePaisa > $maximumPaisa) {
            throw new \InvalidArgumentException('Payroll overtime pay exceeds the supported storage limit.');
        }

        return $overtimePaisa;
    }

    /**
     * Calculate payroll in integer paisa to avoid binary floating-point drift.
     *
     * Values are validated against the DECIMAL(12,2) storage range before
     * conversion; callers cannot persist infinities, NaN, or overflowing totals.
     */
    public static function calculateNet(float $basic, float $otHours, float $otRate, float $allowances, float $deductions): float
    {
        $values = compact('basic', 'otHours', 'otRate', 'allowances', 'deductions');

        foreach ($values as $field => $value) {
            if (! is_finite($value) || $value < 0) {
                throw new \InvalidArgumentException("Payroll {$field} must be a finite, non-negative number.");
            }
        }

        $maximumPaisa = 999_999_999_999;
        $toPaisa = static function (float $amount, string $field) use ($maximumPaisa): int {
            $rounded = round($amount, 2);
            if ($rounded > ($maximumPaisa / 100)) {
                throw new \InvalidArgumentException("Payroll {$field} exceeds the supported storage limit.");
            }

            return (int) round($rounded * 100);
        };

        $basicPaisa = $toPaisa($basic, 'basic salary');
        $overtimePaisa = self::calculateOvertimePaisa($otHours, $otRate);
        $allowancesPaisa = $toPaisa($allowances, 'allowances');
        $deductionsPaisa = $toPaisa($deductions, 'deductions');
        $netPaisa = $basicPaisa + $overtimePaisa + $allowancesPaisa - $deductionsPaisa;

        if ($netPaisa > $maximumPaisa) {
            throw new \InvalidArgumentException('Payroll net salary exceeds the supported storage limit.');
        }

        return max(0, $netPaisa) / 100;
    }
}
