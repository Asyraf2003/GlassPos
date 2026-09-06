<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Support\ViewDateFormatter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class ServicePackageProfitBreakdownExcelDetailSheetWriter
{
    public function __construct(
        private readonly TransactionReportExcelTableWriter $tables,
    ) {
    }

    public function write(Worksheet $sheet, array $rows): void
    {
        $sheet->setTitle('Detail Paket');

        $values = [];

        foreach (array_values($rows) as $index => $row) {
            $values[] = [
                $index + 1,
                (string) ($row['note_id'] ?? ''),
                (string) ($row['work_item_id'] ?? ''),
                ViewDateFormatter::display($row['transaction_date'] ?? null),
                (string) ($row['customer_name'] ?? ''),
                (int) ($row['package_sold_amount_rupiah'] ?? 0),
                (int) ($row['parts_total_rupiah'] ?? 0),
                (int) ($row['sparepart_cogs_rupiah'] ?? 0),
                (int) ($row['sparepart_margin_rupiah'] ?? 0),
                (int) ($row['service_price_rupiah'] ?? 0),
                (int) ($row['package_base_service_price_rupiah'] ?? 0),
                (int) ($row['package_service_extra_rupiah'] ?? 0),
                (int) ($row['package_profit_rupiah'] ?? 0),
                (int) ($row['total_service_component_rupiah'] ?? 0),
                (int) ($row['refunded_product_component_rupiah'] ?? 0),
                (int) ($row['refunded_service_component_rupiah'] ?? 0),
                (int) ($row['total_package_gross_profit_rupiah'] ?? 0),
            ];
        }

        $this->tables->writeTable($sheet, 1, [
            'No',
            'ID Nota',
            'ID Work Item',
            'Tanggal Transaksi',
            'Customer',
            'Nilai Paket Terjual',
            'Total Sparepart',
            'HPP Sparepart',
            'Margin Sparepart',
            'Bagian Jasa 20%',
            'Base Service Snapshot',
            'Service Extra Snapshot',
            'Keuntungan Toko dari Jasa 80%',
            'Total Nilai Jasa',
            'Refund Komponen Produk',
            'Refund Komponen Service',
            'Laba Kotor Paket untuk Toko',
        ], $values);

        $this->tables->autosize($sheet, 17);
    }
}
