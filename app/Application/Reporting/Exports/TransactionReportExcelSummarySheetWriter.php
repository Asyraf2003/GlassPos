<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Support\ViewDateFormatter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class TransactionReportExcelSummarySheetWriter
{
    public function __construct(
        private readonly TransactionReportExcelTableWriter $tables,
    ) {}

    public function write(Worksheet $sheet, array $summary, array $filters): void
    {
        $sheet->setTitle('Ringkasan');
        $sheet->setCellValue('A1', 'Laporan Transaksi');
        $sheet->setCellValue('A2', 'Periode');
        $sheet->setCellValue('B2', ViewDateFormatter::range($filters['date_from'] ?? null, $filters['date_to'] ?? null));
        $sheet->setCellValue('A3', 'Dasar Tanggal');
        $sheet->setCellValue('B3', 'Tanggal transaksi nota');

        $this->tables->writeTable($sheet, 5, ['Metrik', 'Nilai'], [
            ['Jumlah Nota', (int) ($summary['total_rows'] ?? 0)],
            ['Total Bruto Transaksi', (int) ($summary['gross_transaction_rupiah'] ?? 0)],
            ['Total Pembayaran Dialokasikan', (int) ($summary['allocated_payment_rupiah'] ?? 0)],
            ['Total Dana Dikembalikan', (int) ($summary['refunded_rupiah'] ?? 0)],
            ['Total Refund Due', (int) ($summary['refund_due_rupiah'] ?? 0)],
            ['Total Surplus Refund Paid', (int) ($summary['surplus_refund_paid_rupiah'] ?? 0)],
            ['Total Sisa Refund Due', (int) ($summary['remaining_refund_due_rupiah'] ?? 0)],
            ['Total Kas Bersih', (int) ($summary['net_cash_collected_rupiah'] ?? 0)],
            ['Total Sisa Tagihan', (int) ($summary['outstanding_rupiah'] ?? 0)],
            ['Nota Lunas', (int) ($summary['settled_rows'] ?? 0)],
            ['Nota Sisa Tagihan', (int) ($summary['outstanding_rows'] ?? 0)],
        ]);

        $this->tables->autosize($sheet, 2);
    }
}
