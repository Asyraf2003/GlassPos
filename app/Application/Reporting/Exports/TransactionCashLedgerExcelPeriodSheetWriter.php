<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Support\ViewDateFormatter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class TransactionCashLedgerExcelPeriodSheetWriter
{
    public function __construct(
        private readonly TransactionReportExcelTableWriter $tables,
    ) {}

    public function write(Worksheet $sheet, array $rows): void
    {
        $sheet->setTitle('Rekap Per Tanggal');
        $values = [];

        foreach (array_values($rows) as $row) {
            $values[] = [
                ViewDateFormatter::display($row['period_label'] ?? null),
                (int) ($row['total_events'] ?? 0),
                (int) ($row['total_in_rupiah'] ?? 0),
                (int) ($row['cash_in_rupiah'] ?? 0),
                (int) ($row['transfer_in_rupiah'] ?? 0),
                (int) ($row['cash_out_rupiah'] ?? 0),
                (int) ($row['net_amount_rupiah'] ?? 0),
            ];
        }

        $this->tables->writeTable($sheet, 1, [
            'Tanggal',
            'Total Kejadian',
            'Kas Masuk',
            'Tunai Masuk',
            'Transfer Masuk',
            'Kas Keluar',
            'Nilai Bersih',
        ], $values);

        $this->tables->autosize($sheet, 7);
    }
}
