<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Support\ViewDateFormatter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class EmployeeDebtReportExcelSummarySheetWriter
{
    public function __construct(
        private readonly TransactionReportExcelTableWriter $tables,
    ) {}

    public function write(Worksheet $sheet, array $summary, array $filters, array $temporalRows = []): void
    {
        $sheet->setTitle('Ringkasan');
        $sheet->setCellValue('A1', 'Laporan Hutang Karyawan');
        $sheet->setCellValue('A2', 'Periode');
        $sheet->setCellValue('B2', ViewDateFormatter::range($filters['date_from'] ?? null, $filters['date_to'] ?? null));
        $sheet->setCellValue('A3', 'Dasar Tanggal');
        $sheet->setCellValue('B3', 'Aktivitas periode; posisi per akhir periode');

        $this->tables->writeTable($sheet, 5, ['Metrik', 'Nilai'], array_map(
            static fn (array $row): array => [$row['label'], (int) $row['value']],
            $temporalRows,
        ));

        $this->tables->autosize($sheet, 2);
    }
}
