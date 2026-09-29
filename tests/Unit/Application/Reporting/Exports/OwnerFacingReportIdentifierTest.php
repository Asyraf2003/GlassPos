<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Reporting\Exports;

use App\Application\Reporting\Exports\EmployeeDebtReportPdfViewDataBuilder;
use App\Application\Reporting\Exports\InventoryReportDetailTables;
use App\Application\Reporting\Exports\ServicePackageReportDetailTables;
use App\Application\Reporting\Exports\TransactionCashLedgerPdfViewDataBuilder;
use App\Ports\Out\ClockPort;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class OwnerFacingReportIdentifierTest extends TestCase
{
    public function test_cash_ledger_hides_event_id_but_keeps_note(): void
    {
        $data = (new TransactionCashLedgerPdfViewDataBuilder($this->clock()))->build(
            ['rows' => [], 'summary' => []],
            ['date_from' => '2026-09-01', 'date_to' => '2026-09-30'],
        );

        $columns = $data['detailTables'][0]['columns'];

        self::assertArrayHasKey('note_label', $columns);
        self::assertArrayNotHasKey('source_id', $columns);
    }

    public function test_employee_debt_uses_employee_name_and_hides_internal_ids(): void
    {
        $data = (new EmployeeDebtReportPdfViewDataBuilder($this->clock()))->build(
            ['rows' => [], 'summary' => [], 'period_rows' => [], 'status_rows' => [], 'temporal_summary_rows' => []],
            ['date_from' => '2026-09-01', 'date_to' => '2026-09-30'],
        );

        $columns = $data['detailTables'][0]['columns'];

        self::assertSame('Karyawan', $columns['employee_name']);
        self::assertArrayNotHasKey('employee_id', $columns);
        self::assertArrayNotHasKey('debt_id', $columns);
    }

    public function test_inventory_uses_product_code_instead_of_product_id(): void
    {
        $tables = InventoryReportDetailTables::build([
            'snapshot_rows' => [],
            'movement_rows' => [],
            'current_diagnostic_rows' => [],
        ], '30/09/2026');

        foreach ($tables as $table) {
            self::assertSame('Kode Barang', $table['columns']['kode_barang']);
            self::assertArrayNotHasKey('product_id', $table['columns']);
        }
    }

    public function test_service_package_uses_line_number_instead_of_work_item_id(): void
    {
        $tables = ServicePackageReportDetailTables::build(['rows' => []]);
        $columns = $tables[0]['columns'];

        self::assertSame('Nota', $columns['note_id']);
        self::assertSame('Baris Paket', $columns['package_line_no']);
        self::assertArrayNotHasKey('work_item_id', $columns);
    }

    private function clock(): ClockPort
    {
        return new class implements ClockPort
        {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-09-30 08:00:00');
            }
        };
    }
}
