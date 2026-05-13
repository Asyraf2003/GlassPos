<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Support\ViewDateFormatter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class TransactionReportExcelDetailSheetWriter
{
    public function __construct(
        private readonly TransactionReportExcelTableWriter $tables,
    ) {}

    public function write(Worksheet $sheet, array $rows): void
    {
        $sheet->setTitle('Rincian Nota');
        $values = [];

        foreach (array_values($rows) as $index => $row) {
            $values[] = [
                $index + 1,
                (string) ($row['note_id'] ?? ''),
                ViewDateFormatter::display($row['transaction_date'] ?? null),
                (string) ($row['customer_name'] ?? ''),
                (int) ($row['gross_transaction_rupiah'] ?? 0),
                (int) ($row['allocated_payment_rupiah'] ?? 0),
                (int) ($row['refunded_rupiah'] ?? 0),
                (int) ($row['refund_due_rupiah'] ?? 0),
                (int) ($row['net_cash_collected_rupiah'] ?? 0),
                (int) ($row['outstanding_rupiah'] ?? 0),
                (string) ($row['payment_status_label'] ?? ''),
            ];
        }

        $this->tables->writeTable($sheet, 1, [
            'No',
            'ID Nota',
            'Tanggal Transaksi',
            'Nama Customer',
            'Nilai Bruto Transaksi',
            'Pembayaran Dialokasikan',
            'Dana Dikembalikan',
            'Refund Due',
            'Kas Bersih',
            'Sisa Tagihan',
            'Status Pembayaran',
        ], $values);

        $this->tables->autosize($sheet, 11);
    }
}
