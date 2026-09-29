<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

final class InventoryReportDetailTables
{
    public static function build(array $dataset, string $asOf): array
    {
        return [
            ReportDetailTableFormatter::table('Posisi stok per '.$asOf, [
                'kode_barang' => 'Kode Barang', 'nama_barang' => 'Barang', 'current_qty_on_hand' => 'Qty',
                'current_avg_cost_rupiah' => 'Rata-rata Modal', 'current_inventory_value_rupiah' => 'Nilai Stok',
                'current_rounding_residual_rupiah' => 'Residual Pembulatan',
            ], $dataset['snapshot_rows'] ?? []),
            ReportDetailTableFormatter::table('Mutasi periode', [
                'kode_barang' => 'Kode Barang', 'nama_barang' => 'Barang', 'supply_in_qty' => 'Masuk Supplier',
                'sale_out_qty' => 'Keluar', 'refund_reversal_qty' => 'Refund', 'revision_correction_qty' => 'Koreksi',
                'net_qty_delta' => 'Qty Bersih', 'total_in_cost_rupiah' => 'Nilai Masuk',
                'total_out_cost_rupiah' => 'Nilai Keluar', 'net_cost_delta_rupiah' => 'Nilai Bersih',
            ], $dataset['movement_rows'] ?? []),
            ReportDetailTableFormatter::table('Diagnostik proyeksi saat ini (bukan saldo historis)', [
                'kode_barang' => 'Kode Barang', 'nama_barang' => 'Barang', 'ledger_qty_diff' => 'Selisih Qty',
                'ledger_value_diff_rupiah' => 'Selisih Nilai',
            ], array_values(array_filter($dataset['current_diagnostic_rows'] ?? [], static fn (array $row): bool => (int) ($row['ledger_qty_diff'] ?? 0) !== 0 || (int) ($row['ledger_value_diff_rupiah'] ?? 0) !== 0))),
        ];
    }
}
