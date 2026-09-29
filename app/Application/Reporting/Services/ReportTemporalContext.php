<?php

declare(strict_types=1);

namespace App\Application\Reporting\Services;

final class ReportTemporalContext
{
    public static function description(string $report, array $filters): string
    {
        $cutoff = (string) ($filters['date_to'] ?? '');

        return match ($report) {
            'TransactionReport' => 'Mode current: nota dipilih berdasarkan tanggal transaksi. Nilai dan status memakai revisi saat ini; pembayaran nota terpilih dibatasi sampai akhir periode, sedangkan refund dan settlement masih memakai state current. Ini bukan posisi historis per akhir periode. Arus uang periode tersedia di Buku Kas Transaksi.',
            'ServicePackageProfit', 'ServicePackageProfitBreakdown', 'ServicePackageProfitBreakdownReport' => 'Mode current: paket dipilih berdasarkan tanggal transaksi. Nilai paket memakai detail revisi saat ini; COGS dan refund kumulatif mengikuti paket terpilih. Ini bukan snapshot historis per akhir periode. Harga katalog dan AVG saat ini tidak dipakai untuk menghitung nilai paket.',
            'EmployeeDebtReport' => 'Mode as-of: saldo membawa kasbon sebelum periode; pembayaran, penyesuaian dan reversal dibatasi sampai '.$cutoff.'. Aktivitas periode dipisahkan dari saldo awal dan akhir.',
            'SupplierPayableReport' => $cutoff === ''
                ? 'Mode current: seluruh faktur non-void dan pembayaran aktif saat ini, tanpa filter bulan pengiriman. Jatuh tempo hanya menentukan urgensi.'
                : 'Mode as-of per '.$cutoff.': saldo membawa faktur sebelum periode. Versi, void dan reversal setelah cutoff tidak mengubah posisi historis. Faktur legacy tanpa versi memakai tanggal pengiriman sebagai basis; receipt/qty adalah penerimaan bruto sampai cutoff.',
            'InventoryStockValueReport' => 'Mode as-of per '.$cutoff.': posisi dari seluruh mutasi sampai cutoff; mutasi periode terpisah. Produk tanpa riwayat tidak ditaksir. Nama dan threshold memakai konfigurasi current. Diagnostik membandingkan proyeksi current dengan seluruh riwayat.',
            'PayrollReport' => 'Flow pencairan pada periode, dengan status reversal sampai akhir periode. Reversal setelah cutoff tidak menghapus periode lama; reversal tidak dianggap sebagai uang masuk.',
            'OperationalExpenseReport' => 'Flow biaya bertanggal dalam periode, dengan status penghapusan sampai akhir periode. Penghapusan setelah cutoff tidak menghapus periode lama dan bukan bukti uang masuk.',
            'OperationalProfitReport' => 'Flow kas operasional periode berdasarkan tanggal pembayaran/refund, mutasi modal, biaya dan pencairan. Koreksi stok supplier yang dikecualikan ADR-0037 tetap dikecualikan.',
            'TransactionCashLedger' => 'Ledger actual: pembayaran dan refund mengikuti tanggal kejadian uang dalam periode, termasuk kejadian untuk nota dari bulan sebelumnya.',
            default => 'Periode aktivitas dan posisi mengikuti label metrik.',
        };
    }
}
