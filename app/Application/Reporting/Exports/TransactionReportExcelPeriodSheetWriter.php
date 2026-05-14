<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Support\ViewDateFormatter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class TransactionReportExcelPeriodSheetWriter
{
    public function __construct(
        private readonly TransactionReportExcelTableWriter $tables,
    ) {}

    public function write(Worksheet $sheet, array $rows): void
    {
        $sheet->setTitle('Rekap Per Tanggal');
        $values = [];

        foreach ($rows as $row) {
            $values[] = [
                ViewDateFormatter::display($row['period_label'] ?? null),
                (int) ($row['total_rows'] ?? 0),
                (int) ($row['gross_transaction_rupiah'] ?? 0),
                (int) ($row['allocated_payment_rupiah'] ?? 0),
                (int) ($row['refunded_rupiah'] ?? 0),
                (int) ($row['refund_due_rupiah'] ?? 0),
                (int) ($row['surplus_refund_paid_rupiah'] ?? 0),
                (int) ($row['remaining_refund_due_rupiah'] ?? 0),
                (int) ($row['net_cash_collected_rupiah'] ?? 0),
                (int) ($row['outstanding_rupiah'] ?? 0),
            ];
        }

        $this->tables->writeTable($sheet, 1, [
            'Tanggal',
            'Jumlah Nota',
            'Nilai Bruto Transaksi',
            'Pembayaran Dialokasikan',
            'Dana Dikembalikan',
            'Refund Due',
            'Surplus Refund Paid',
            'Sisa Refund Due',
            'Kas Bersih',
            'Sisa Tagihan',
        ], $values);

        $this->tables->autosize($sheet, 10);
    }
}
