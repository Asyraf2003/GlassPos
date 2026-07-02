<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Support\ReportPeriodDateLabelFormatter;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class OperationalProfitReportExcelSummarySheetWriter
{
    public function __construct(
        private readonly TransactionReportExcelTableWriter $tables,
    ) {}

    public function write(Worksheet $sheet, array $row, array $filters): void
    {
        $periodContext = ReportPeriodDateLabelFormatter::context($filters['date_from'] ?? null, $filters['date_to'] ?? null);

        $sheet->setTitle('Ringkasan');
        $sheet->setCellValue('A1', 'Ringkasan Kas Operasional');
        $sheet->setCellValue('A2', $periodContext['label']);
        $sheet->setCellValue('B2', $periodContext['value']);
        $sheet->setCellValue('A3', 'Dasar Tanggal');
        $sheet->setCellValue('B3', 'Tanggal kejadian komponen kas dan biaya');

        $this->tables->writeTable($sheet, 5, ['Metrik', 'Nilai'], [
            ['Uang Diterima', (int) ($row['cash_in_rupiah'] ?? 0)],
            ['Uang Dikembalikan', (int) ($row['refunded_rupiah'] ?? 0)],
            ['Biaya Barang Luar', (int) ($row['external_purchase_cost_rupiah'] ?? 0)],
            ['Modal Barang Stok', (int) ($row['store_stock_cogs_rupiah'] ?? 0)],
            ['Total Modal Produk', (int) ($row['product_purchase_cost_rupiah'] ?? 0)],
            ['Biaya Operasional', (int) ($row['operational_expense_rupiah'] ?? 0)],
            ['Gaji Dibayar', (int) ($row['payroll_disbursement_rupiah'] ?? 0)],
            ['Kasbon/Hutang Karyawan', (int) ($row['employee_debt_cash_out_rupiah'] ?? 0)],
            ['Sisa Kas Operasional', (int) ($row['cash_operational_profit_rupiah'] ?? 0)],
        ]);

        $this->tables->autosize($sheet, 2);
    }
}
