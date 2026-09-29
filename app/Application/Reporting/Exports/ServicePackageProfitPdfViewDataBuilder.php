<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Application\Reporting\Exports\Concerns\FormatsPdfReportValues;
use App\Application\Reporting\Services\ReportTemporalContext;
use App\Ports\Out\ClockPort;

final class ServicePackageProfitPdfViewDataBuilder
{
    use FormatsPdfReportValues;

    public function __construct(private readonly ClockPort $clock) {}

    public function build(array $dataset, array $filters): array
    {
        $labels = [
            'total_packages' => 'Jumlah Paket',
            'package_sold_amount_rupiah' => 'Nilai Paket Terjual',
            'parts_total_rupiah' => 'Total Sparepart',
            'sparepart_cogs_rupiah' => 'HPP Sparepart',
            'sparepart_margin_rupiah' => 'Margin Sparepart',
            'service_fee_rupiah' => 'Bagian Jasa 20%',
            'package_profit_rupiah' => 'Keuntungan Toko dari Jasa 80%',
            'total_service_component_rupiah' => 'Total Nilai Jasa',
            'refunded_product_component_rupiah' => 'Refund Komponen Produk',
            'refunded_service_component_rupiah' => 'Refund Komponen Service',
            'total_package_gross_profit_rupiah' => 'Laba Kotor Paket untuk Toko',
        ];
        $items = [];
        foreach ($labels as $key => $label) {
            $value = (int) ($dataset['summary'][$key] ?? 0);
            $items[] = ['label' => $label, 'value' => $key === 'total_packages' ? $value : $this->rupiah($value)];
        }

        return [
            'temporalContext' => ReportTemporalContext::description('ServicePackageProfit', $filters),
            'title' => 'Laba Paket Service',
            'periodLabel' => $this->formatRange($filters['date_from'], $filters['date_to']),
            'generatedAt' => $this->clock->now()->format('d/m/Y H:i'),
            'detailTables' => ServicePackageReportDetailTables::build($dataset),
            'summaryItems' => $items,
        ];
    }
}
