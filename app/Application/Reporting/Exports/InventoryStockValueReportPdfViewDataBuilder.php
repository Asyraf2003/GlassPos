<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Application\Reporting\Exports\Concerns\FormatsPdfReportValues;
use App\Ports\Out\ClockPort;

final class InventoryStockValueReportPdfViewDataBuilder
{
    use FormatsPdfReportValues;

    public function __construct(
        private readonly ClockPort $clock,
    ) {
    }

    public function build(array $dataset, array $filters): array
    {
        $summary = is_array($dataset['summary'] ?? null) ? $dataset['summary'] : [];

        return [
            'title' => 'Stok dan Nilai Persediaan',
            'periodLabel' => $this->formatRange(
                $this->stringValue($filters['date_from'] ?? ''),
                $this->stringValue($filters['date_to'] ?? ''),
            ),
            'referenceDateLabel' => $this->formatDate($this->stringValue($filters['reference_date'] ?? '')),
            'generatedAt' => $this->clock->now()->format('d/m/Y H:i'),
            'summaryItems' => $this->summaryItems($summary),
        ];
    }

    private function summaryItems(array $summary): array
    {
        return [
            ['label' => 'Produk Snapshot', 'value' => $this->integerValue($summary['snapshot_product_rows'] ?? 0)],
            ['label' => 'Produk Bermutasi', 'value' => $this->integerValue($summary['movement_product_rows'] ?? 0)],
            ['label' => 'Qty Tersedia', 'value' => $this->integerValue($summary['total_qty_on_hand'] ?? 0)],
            ['label' => 'Nilai Persediaan', 'value' => $this->rupiah($summary['total_inventory_value_rupiah'] ?? 0)],
            ['label' => 'Qty Masuk Pembelian', 'value' => $this->integerValue($summary['period_supply_in_qty'] ?? 0)],
            ['label' => 'Qty Keluar Penjualan', 'value' => $this->integerValue($summary['period_sale_out_qty'] ?? 0)],
            ['label' => 'Qty Balik Refund/Reversal', 'value' => $this->integerValue($summary['period_refund_reversal_qty'] ?? 0)],
            ['label' => 'Qty Koreksi/Revisi', 'value' => $this->integerValue($summary['period_revision_correction_qty'] ?? 0)],
            ['label' => 'Selisih Qty Periode', 'value' => $this->integerValue($summary['period_net_qty_delta'] ?? 0)],
            ['label' => 'Nilai Masuk Periode', 'value' => $this->rupiah($summary['period_total_in_cost_rupiah'] ?? 0)],
            ['label' => 'Nilai Keluar Periode', 'value' => $this->rupiah($summary['period_total_out_cost_rupiah'] ?? 0)],
            ['label' => 'Selisih Nilai Pokok Periode', 'value' => $this->rupiah($summary['period_net_cost_delta_rupiah'] ?? 0)],
            ['label' => 'Produk Aman', 'value' => $this->integerValue($summary['stock_safe_product_rows'] ?? 0)],
            ['label' => 'Produk Low', 'value' => $this->integerValue($summary['stock_low_product_rows'] ?? 0)],
            ['label' => 'Produk Critical', 'value' => $this->integerValue($summary['stock_critical_product_rows'] ?? 0)],
            ['label' => 'Produk Belum Konfigurasi Threshold', 'value' => $this->integerValue($summary['stock_unconfigured_product_rows'] ?? 0)],
        ];
    }

}
