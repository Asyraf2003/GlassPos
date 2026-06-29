<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use Carbon\CarbonImmutable;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class InventoryStockValueReportExcelSummarySheetWriter
{
    public function write(Worksheet $sheet, array $summary, array $filters): void
    {
        $sheet->setTitle('Ringkasan');

        $rows = [
            ['Stok dan Nilai Persediaan', null],
            ['Rentang movement', $this->formatRange($filters['date_from'] ?? null, $filters['date_to'] ?? null)],
            ['Mode periode', $this->periodModeLabel($filters['period_mode'] ?? 'monthly')],
            ['Tanggal referensi', $this->formatDate($filters['reference_date'] ?? null)],
            [null, null],
            ['Produk Snapshot', $this->int($summary['snapshot_product_rows'] ?? 0)],
            ['Produk Bermutasi', $this->int($summary['movement_product_rows'] ?? 0)],
            ['Qty Tersedia', $this->int($summary['total_qty_on_hand'] ?? 0)],
            ['Nilai Persediaan', $this->int($summary['total_inventory_value_rupiah'] ?? 0)],
            ['Qty Masuk Pembelian', $this->int($summary['period_supply_in_qty'] ?? 0)],
            ['Qty Keluar Penjualan', $this->int($summary['period_sale_out_qty'] ?? 0)],
            ['Qty Balik Refund/Reversal', $this->int($summary['period_refund_reversal_qty'] ?? 0)],
            ['Qty Koreksi/Revisi', $this->int($summary['period_revision_correction_qty'] ?? 0)],
            ['Selisih Qty Periode', $this->int($summary['period_net_qty_delta'] ?? 0)],
            ['Nilai Masuk Periode', $this->int($summary['period_total_in_cost_rupiah'] ?? 0)],
            ['Nilai Keluar Periode', $this->int($summary['period_total_out_cost_rupiah'] ?? 0)],
            ['Selisih Nilai Pokok Periode', $this->int($summary['period_net_cost_delta_rupiah'] ?? 0)],
            ['Produk Aman', $this->int($summary['stock_safe_product_rows'] ?? 0)],
            ['Produk Low', $this->int($summary['stock_low_product_rows'] ?? 0)],
            ['Produk Critical', $this->int($summary['stock_critical_product_rows'] ?? 0)],
            ['Produk Belum Konfigurasi Threshold', $this->int($summary['stock_unconfigured_product_rows'] ?? 0)],
            [null, null],
            ['Diagnostik Internal', null],
            ['Nilai Berdasar Avg x Qty', $this->int($summary['total_inventory_value_by_average_rupiah'] ?? 0)],
            ['Residual Pembulatan HPP', $this->int($summary['total_rounding_residual_rupiah'] ?? 0)],
            ['Selisih Qty Ledger', $this->int($summary['total_ledger_qty_diff'] ?? 0)],
            ['Selisih Nilai Ledger', $this->int($summary['total_ledger_value_diff_rupiah'] ?? 0)],
        ];

        foreach ($rows as $index => $row) {
            $excelRow = $index + 1;
            $sheet->setCellValue('A'.$excelRow, $row[0]);
            $sheet->setCellValue('B'.$excelRow, $row[1]);
        }

        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A6:A26')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(38);
        $sheet->getColumnDimension('B')->setWidth(24);
    }

    private function int(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function formatRange(mixed $from, mixed $to): string
    {
        return $this->formatDate($from).' s/d '.$this->formatDate($to);
    }

    private function formatDate(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '-';
        }

        return CarbonImmutable::parse($value)->format('d/m/Y');
    }

    private function periodModeLabel(mixed $value): string
    {
        return match ($value) {
            'daily' => 'Harian',
            'weekly' => 'Mingguan',
            'custom' => 'Custom',
            default => 'Bulanan',
        };
    }
}
