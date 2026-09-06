<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Support\ViewDateFormatter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class ServicePackageProfitBreakdownExcelSummarySheetWriter
{
    public function __construct(
        private readonly TransactionReportExcelTableWriter $tables,
    ) {
    }

    public function write(Worksheet $sheet, array $summary, array $filters): void
    {
        $sheet->setTitle('Ringkasan');
        $sheet->setCellValue('A1', 'Laba Paket Service');

        $this->tables->writeTable($sheet, 2, ['Metrik', 'Nilai'], [
            ['Periode', ViewDateFormatter::range($filters['date_from'] ?? null, $filters['date_to'] ?? null)],
            ['Jumlah Paket', (int) ($summary['total_packages'] ?? 0)],
            ['Nilai Paket Terjual', (int) ($summary['package_sold_amount_rupiah'] ?? 0)],
            ['Total Sparepart', (int) ($summary['parts_total_rupiah'] ?? 0)],
            ['HPP Sparepart', (int) ($summary['sparepart_cogs_rupiah'] ?? 0)],
            ['Margin Sparepart', (int) ($summary['sparepart_margin_rupiah'] ?? 0)],
            ['Bagian Jasa 20%', (int) ($summary['service_fee_rupiah'] ?? 0)],
            ['Keuntungan Toko dari Jasa 80%', (int) ($summary['package_profit_rupiah'] ?? 0)],
            ['Total Nilai Jasa', (int) ($summary['total_service_component_rupiah'] ?? 0)],
            ['Refund Komponen Produk', (int) ($summary['refunded_product_component_rupiah'] ?? 0)],
            ['Refund Komponen Service', (int) ($summary['refunded_service_component_rupiah'] ?? 0)],
            ['Laba Kotor Paket untuk Toko', (int) ($summary['total_package_gross_profit_rupiah'] ?? 0)],
        ]);

        $this->tables->autosize($sheet, 2);
    }
}
