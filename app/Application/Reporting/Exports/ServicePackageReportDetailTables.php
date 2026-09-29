<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

final class ServicePackageReportDetailTables
{
    public static function build(array $dataset): array
    {
        return [ReportDetailTableFormatter::table('Rincian paket pada periode transaksi', [
            'note_id' => 'Nota', 'work_item_id' => 'Paket', 'transaction_date' => 'Tanggal', 'customer_name' => 'Pelanggan',
            'package_sold_amount_rupiah' => 'Nilai Paket', 'parts_total_rupiah' => 'Sparepart',
            'sparepart_cogs_rupiah' => 'HPP', 'sparepart_margin_rupiah' => 'Margin',
            'service_price_rupiah' => 'Jasa', 'package_base_service_price_rupiah' => 'Jasa Dasar',
            'package_service_extra_rupiah' => 'Jasa Tambahan', 'package_profit_rupiah' => 'Bagian Toko',
            'total_service_component_rupiah' => 'Total Jasa', 'refunded_product_component_rupiah' => 'Refund Produk',
            'refunded_service_component_rupiah' => 'Refund Jasa', 'total_package_gross_profit_rupiah' => 'Laba Kotor',
        ], $dataset['rows'] ?? [])];
    }
}
