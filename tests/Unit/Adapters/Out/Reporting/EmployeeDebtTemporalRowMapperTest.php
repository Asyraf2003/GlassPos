<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Out\Reporting;

use App\Adapters\Out\Reporting\EmployeeDebtTemporalRowMapper;
use PHPUnit\Framework\TestCase;

final class EmployeeDebtTemporalRowMapperTest extends TestCase
{
    public function test_employee_name_fallback_keeps_nullable_and_legacy_rows_valid(): void
    {
        $row = (object) [
            'id' => 'debt', 'employee_id' => 'employee', 'created_at' => '2026-07-01',
            'total_debt' => 100001, 'all_adjustments' => 0, 'closing_adjustments' => 0,
            'closing_paid' => 100000, 'opening_adjustments' => 0, 'opening_paid' => 0,
            'period_payments' => 100000, 'period_reversals' => 0, 'notes' => null,
        ];
        $legacy = EmployeeDebtTemporalRowMapper::map($row, '2026-09-01');
        self::assertSame('-', $legacy['employee_name']);
        self::assertSame(1, $legacy['remaining_balance']);
        self::assertSame('unpaid', $legacy['status']);
        $row->employee_name = null;
        self::assertSame($legacy, EmployeeDebtTemporalRowMapper::map($row, '2026-09-01'));
        $row->employee_name = 'Montir A';
        self::assertSame(
            array_replace($legacy, ['employee_name' => 'Montir A']),
            EmployeeDebtTemporalRowMapper::map($row, '2026-09-01'),
        );
    }
}
