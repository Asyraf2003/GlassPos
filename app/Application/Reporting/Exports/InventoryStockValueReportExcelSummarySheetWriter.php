<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Support\ViewDateFormatter;
use Carbon\CarbonImmutable;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class InventoryStockValueReportExcelSummarySheetWriter
{
    public function write(Worksheet $sheet, array $summary, array $filters): void
    {
        $sheet->setTitle('Ringkasan');

        $periodContext = ViewDateFormatter::reportPeriodContext($filters['date_from'] ?? null, $filters['date_to'] ?? null);

        $rows = [
            ['Stok dan Nilai Persediaan', null],
            [$periodContext['label'], $periodContext['value']],
            ['Mode periode', $this->periodModeLabel($filters['period_mode'] ?? 'monthly')],
            ['Tanggal referensi', $this->formatDate($filters['reference_date'] ?? null)],
            [null, null],
            ['Produk Tercatat di Stok', $this->int($summary['snapshot_product_rows'] ?? 0)],
            ['Produk Bergerak', $this->int($summary['movement_product_rows'] ?? 0)],
            ['Total Stok Tersedia', $this->int($summary['total_qty_on_hand'] ?? 0)],
            ['Nilai Modal Stok', $this->int($summary['total_inventory_value_rupiah'] ?? 0)],
            ['Barang Masuk dari Supplier', $this->int($summary['period_supply_in_qty'] ?? 0)],
            ['Barang Keluar Terjual/Dipakai', $this->int($summary['period_sale_out_qty'] ?? 0)],
            ['Barang Balik dari Refund', $this->int($summary['period_refund_reversal_qty'] ?? 0)],
            ['Barang Koreksi/Revisi', $this->int($summary['period_revision_correction_qty'] ?? 0)],
            ['Perubahan Stok Bersih', $this->int($summary['period_net_qty_delta'] ?? 0)],
            ['Nilai Masuk Periode', $this->int($summary['period_total_in_cost_rupiah'] ?? 0)],
            ['Nilai Keluar Periode', $this->int($summary['period_total_out_cost_rupiah'] ?? 0)],
            ['Perubahan Modal Stok Bersih', $this->int($summary['period_net_cost_delta_rupiah'] ?? 0)],
            ['Produk Aman', $this->int($summary['stock_safe_product_rows'] ?? 0)],
            ['Produk Low', $this->int($summary['stock_low_product_rows'] ?? 0)],
            ['Produk Critical', $this->int($summary['stock_critical_product_rows'] ?? 0)],
            ['Produk Belum Konfigurasi Threshold', $this->int($summary['stock_unconfigured_product_rows'] ?? 0)],
            [null, null],
            ['Validasi Sistem', null],
            ['Catatan Validasi Sistem', 'Bagian ini mengecek apakah ringkasan stok saat ini cocok dengan riwayat keluar-masuk barang. Nilai sehat untuk selisih stok dan nilai adalah 0.'],
            ['Nilai Pembanding Avg x Qty', $this->int($summary['total_inventory_value_by_average_rupiah'] ?? 0)],
            ['Selisih Pembulatan Modal', $this->int($summary['total_rounding_residual_rupiah'] ?? 0)],
            ['Selisih Stok vs Riwayat', $this->int($summary['total_ledger_qty_diff'] ?? 0)],
            ['Selisih Nilai vs Riwayat', $this->int($summary['total_ledger_value_diff_rupiah'] ?? 0)],
        ];

        foreach ($rows as $index => $row) {
            $excelRow = $index + 1;
            $sheet->setCellValue('A'.$excelRow, $row[0]);
            $sheet->setCellValue('B'.$excelRow, $row[1]);
        }

        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A6:A27')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(38);
        $sheet->getColumnDimension('B')->setWidth(82);
    }

    private function int(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
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
