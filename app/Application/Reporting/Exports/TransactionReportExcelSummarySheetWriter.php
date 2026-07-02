<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Support\ReportPeriodDateLabelFormatter;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class TransactionReportExcelSummarySheetWriter
{
    public function __construct(
        private readonly TransactionReportExcelTableWriter $tables,
    ) {}

    public function write(Worksheet $sheet, array $summary, array $filters): void
    {
        $periodContext = ReportPeriodDateLabelFormatter::context($filters['date_from'] ?? null, $filters['date_to'] ?? null);

        $sheet->setTitle('Ringkasan');
        $sheet->setCellValue('A1', 'Laporan Transaksi');
        $sheet->setCellValue('A2', $periodContext['label']);
        $sheet->setCellValue('B2', $periodContext['value']);
        $sheet->setCellValue('A3', 'Dasar Tanggal');
        $sheet->setCellValue('B3', 'Tanggal transaksi nota');

        $this->tables->writeTable($sheet, 5, ['Metrik', 'Nilai'], [
            ['Jumlah Nota', (int) ($summary['total_rows'] ?? 0)],
            ['Total Nilai Nota', (int) ($summary['gross_transaction_rupiah'] ?? 0)],
            ['Total Pembayaran Masuk ke Nota', (int) ($summary['allocated_payment_rupiah'] ?? 0)],
            ['Total Uang Refund Dibayar', (int) ($summary['refunded_rupiah'] ?? 0)],
            ['Total Refund yang Harus Dibayar', (int) ($summary['refund_due_rupiah'] ?? 0)],
            ['Total Kelebihan Bayar Sudah Dikembalikan', (int) ($summary['surplus_refund_paid_rupiah'] ?? 0)],
            ['Total Sisa Refund Belum Dibayar', (int) ($summary['remaining_refund_due_rupiah'] ?? 0)],
            ['Total Uang Bersih Diterima', (int) ($summary['net_cash_collected_rupiah'] ?? 0)],
            ['Total Sisa Tagihan Customer', (int) ($summary['outstanding_rupiah'] ?? 0)],
            ['Nota Selesai', (int) ($summary['settled_rows'] ?? 0)],
            ['Nota Belum Selesai', (int) ($summary['outstanding_rows'] ?? 0)],
        ]);

        $this->tables->autosize($sheet, 2);
    }
}
