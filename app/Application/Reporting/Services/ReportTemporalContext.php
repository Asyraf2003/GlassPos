<?php

declare(strict_types=1);

namespace App\Application\Reporting\Services;

final class ReportTemporalContext
{
    public static function description(string $report, array $filters): string
    {
        $cutoff = (string) ($filters['date_to'] ?? '');

        return match ($report) {
            'TransactionReport' => 'Mode as-of per '.$cutoff.': nota memakai revisi transaksi yang berlaku pada cutoff. Pembayaran, refund, refund-due, pengembalian surplus, dan cancellation dibatasi sampai cutoff; kejadian setelah cutoff tidak menulis ulang posisi historis. Arus uang periode tersedia di Buku Kas Transaksi.',
            'ServicePackageProfit', 'ServicePackageProfitBreakdown', 'ServicePackageProfitBreakdownReport' => 'Mode as-of per '.$cutoff.': paket memakai snapshot revisi yang berlaku pada cutoff. Nilai paket/dekomposisi berasal dari snapshot transaksi; COGS dan refund dibatasi sampai cutoff, sehingga edit/refund setelah cutoff tidak menulis ulang laporan lama.',
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
