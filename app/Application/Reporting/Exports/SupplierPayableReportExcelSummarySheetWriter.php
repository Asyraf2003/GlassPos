<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Application\Reporting\Services\SupplierPayableTemporalSummaryRows;
use App\Support\ViewDateFormatter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class SupplierPayableReportExcelSummarySheetWriter
{
    public function __construct(
        private readonly TransactionReportExcelTableWriter $tables,
    ) {}

    public function write(Worksheet $sheet, array $summary, array $filters): void
    {
        $sheet->setTitle('Ringkasan');
        $sheet->setCellValue('A1', 'Hutang Pemasok');
        $sheet->setCellValue('A2', 'Periode');
        $sheet->setCellValue('B2', ($filters['period_mode'] ?? '') === 'all' ? 'Seluruh Periode' : ViewDateFormatter::range($filters['date_from'] ?? null, $filters['date_to'] ?? null));
        $sheet->setCellValue('A3', 'Dasar Tanggal');
        $sheet->setCellValue('B3', 'Aktivitas periode dan posisi akhir');
        $sheet->setCellValue('A4', 'Tanggal Referensi');
        $sheet->setCellValue('B4', ViewDateFormatter::display($filters['date_to'] ?? $filters['reference_date'] ?? null));

        $this->tables->writeTable($sheet, 6, ['Metrik', 'Nilai'], [
            ['Total Faktur', (int) ($summary['total_rows'] ?? 0)],
            ['Total Tagihan', (int) ($summary['grand_total_rupiah'] ?? 0)],
            ['Total Dibayar', (int) ($summary['total_paid_rupiah'] ?? 0)],
            ['Outstanding', (int) ($summary['outstanding_rupiah'] ?? 0)],
            ['Belum Jatuh Tempo', (int) ($summary['not_due_rows'] ?? 0)],
            ['Jatuh Tempo Hari Ini', (int) ($summary['due_today_rows'] ?? 0)],
            ['Lewat Jatuh Tempo', (int) ($summary['overdue_rows'] ?? 0)],
            ['Sisa Hutang Lewat Tempo', (int) ($summary['overdue_outstanding_rupiah'] ?? 0)],
            ['Belum Lunas', (int) ($summary['open_rows'] ?? 0)],
            ['Lunas', (int) ($summary['settled_rows'] ?? 0)],
            ['Jumlah Receipt', (int) ($summary['receipt_count'] ?? 0)],
            ['Total Qty Diterima', (int) ($summary['total_received_qty'] ?? 0)],
        ]);

        $temporal = SupplierPayableTemporalSummaryRows::build($summary, $filters['date_from'] ?? null, $filters['date_to'] ?? null);
        $this->tables->writeTable($sheet, 21, ['Posisi dan aktivitas', 'Rupiah'], array_map(static fn (array $row): array => [$row['label'], $row['value']], $temporal));

        $this->tables->autosize($sheet, 2);
    }
}
