<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class TransactionReportExcelCustomerSheetWriter
{
    public function __construct(
        private readonly TransactionReportExcelTableWriter $tables,
    ) {}

    public function write(Worksheet $sheet, array $rows): void
    {
        $sheet->setTitle('Rekap Per Pelanggan');
        $values = [];

        foreach ($rows as $row) {
            $values[] = [
                (string) ($row['customer_name'] ?? ''),
                (int) ($row['total_rows'] ?? 0),
                (int) ($row['gross_transaction_rupiah'] ?? 0),
                (int) ($row['allocated_payment_rupiah'] ?? 0),
                (int) ($row['refunded_rupiah'] ?? 0),
                (int) ($row['refund_due_rupiah'] ?? 0),
                (int) ($row['surplus_refund_paid_rupiah'] ?? 0),
                (int) ($row['remaining_refund_due_rupiah'] ?? 0),
                (int) ($row['net_cash_collected_rupiah'] ?? 0),
                (int) ($row['outstanding_rupiah'] ?? 0),
            ];
        }

        $this->tables->writeTable($sheet, 1, [
            'Nama Pelanggan',
            'Jumlah Nota',
            'Total Nilai Transaksi',
            'Pembayaran Dialokasikan',
            'Dana Dikembalikan',
            'Pengembalian Belum Dibayar',
            'Pengembalian Surplus Sudah Dibayar',
            'Sisa Pengembalian Belum Dibayar',
            'Kas Bersih',
            'Sisa Tagihan',
        ], $values);

        $this->tables->autosize($sheet, 10);
    }
}
