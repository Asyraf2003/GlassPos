<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Support\ReportPeriodDateLabelFormatter;

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

        $periodContext = ReportPeriodDateLabelFormatter::context(
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null,
        );

        return [
            'title' => 'Stok dan Nilai Persediaan',
            'periodLabelCaption' => $periodContext['label'],
            'periodLabel' => $periodContext['value'],
            'referenceDateLabel' => $this->formatDate($this->stringValue($filters['reference_date'] ?? '')),
            'generatedAt' => $this->clock->now()->format('d/m/Y H:i'),
            'summaryItems' => $this->summaryItems($summary),
        ];
    }

    private function summaryItems(array $summary): array
    {
        return [
            ['label' => 'Produk Tercatat di Stok', 'value' => $this->integerValue($summary['snapshot_product_rows'] ?? 0)],
            ['label' => 'Produk Bergerak', 'value' => $this->integerValue($summary['movement_product_rows'] ?? 0)],
            ['label' => 'Total Stok Tersedia', 'value' => $this->integerValue($summary['total_qty_on_hand'] ?? 0)],
            ['label' => 'Nilai Modal Stok', 'value' => $this->rupiah($summary['total_inventory_value_rupiah'] ?? 0)],
            ['label' => 'Validasi Sistem', 'value' => 'Bagian ini mengecek apakah ringkasan stok saat ini cocok dengan riwayat keluar-masuk barang. Nilai sehat untuk selisih stok dan nilai adalah 0.'],
            ['label' => 'Nilai Pembanding Avg x Qty', 'value' => $this->rupiah($summary['total_inventory_value_by_average_rupiah'] ?? 0)],
            ['label' => 'Selisih Pembulatan Modal', 'value' => $this->rupiah($summary['total_rounding_residual_rupiah'] ?? 0)],
            ['label' => 'Selisih Stok vs Riwayat', 'value' => $this->integerValue($summary['total_ledger_qty_diff'] ?? 0)],
            ['label' => 'Selisih Nilai vs Riwayat', 'value' => $this->rupiah($summary['total_ledger_value_diff_rupiah'] ?? 0)],
            ['label' => 'Barang Masuk dari Supplier', 'value' => $this->integerValue($summary['period_supply_in_qty'] ?? 0)],
            ['label' => 'Barang Keluar Terjual/Dipakai', 'value' => $this->integerValue($summary['period_sale_out_qty'] ?? 0)],
            ['label' => 'Barang Balik dari Refund', 'value' => $this->integerValue($summary['period_refund_reversal_qty'] ?? 0)],
            ['label' => 'Barang Koreksi/Revisi', 'value' => $this->integerValue($summary['period_revision_correction_qty'] ?? 0)],
            ['label' => 'Perubahan Stok Bersih', 'value' => $this->integerValue($summary['period_net_qty_delta'] ?? 0)],
            ['label' => 'Nilai Masuk Periode', 'value' => $this->rupiah($summary['period_total_in_cost_rupiah'] ?? 0)],
            ['label' => 'Nilai Keluar Periode', 'value' => $this->rupiah($summary['period_total_out_cost_rupiah'] ?? 0)],
            ['label' => 'Perubahan Modal Stok Bersih', 'value' => $this->rupiah($summary['period_net_cost_delta_rupiah'] ?? 0)],
            ['label' => 'Produk Aman', 'value' => $this->integerValue($summary['stock_safe_product_rows'] ?? 0)],
            ['label' => 'Produk Low', 'value' => $this->integerValue($summary['stock_low_product_rows'] ?? 0)],
            ['label' => 'Produk Critical', 'value' => $this->integerValue($summary['stock_critical_product_rows'] ?? 0)],
            ['label' => 'Produk Belum Konfigurasi Threshold', 'value' => $this->integerValue($summary['stock_unconfigured_product_rows'] ?? 0)],
        ];
    }

}
