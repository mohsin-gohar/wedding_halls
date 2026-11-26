<?php

namespace Tests\Unit;

use App\Models\StaffPayroll;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class StaffPayrollMoneyTest extends TestCase
{
    public function test_payroll_net_is_calculated_in_paisa(): void
    {
        self::assertSame(
            127.81,
            StaffPayroll::calculateNet(100.00, 1.25, 10.25, 20.00, 5.00)
        );
    }

    public function test_payroll_net_rejects_values_above_decimal_storage_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        StaffPayroll::calculateNet(9_999_999_999.99, 1, 1, 0, 0);
    }

    public function test_payroll_net_rejects_non_finite_values(): void
    {
        $this->expectException(InvalidArgumentException::class);

        StaffPayroll::calculateNet(INF, 0, 0, 0, 0);
    }

    public function test_payroll_net_rejects_negative_values(): void
    {
        $this->expectException(InvalidArgumentException::class);

        StaffPayroll::calculateNet(100, 0, 0, -1, 0);
    }
}
