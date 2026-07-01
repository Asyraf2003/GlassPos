<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Support\ViewDateFormatter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class TransactionCashLedgerExcelDetailSheetWriter
{
    private readonly TransactionCashLedgerExportLabelFormatter $labels;

    public function __construct(
        private readonly TransactionReportExcelTableWriter $tables,
        ?TransactionCashLedgerExportLabelFormatter $labels = null,
    ) {
        $this->labels = $labels ?? new TransactionCashLedgerExportLabelFormatter();
    }

    public function write(Worksheet $sheet, array $rows): void
    {
        $sheet->setTitle('Detail Event Kas');
        $values = [];

        foreach (array_values($rows) as $index => $row) {
            $values[] = [
                $index + 1,
                ViewDateFormatter::display($row['event_date'] ?? null),
                (string) ($row['note_id'] ?? ''),
                (string) ($row['note_label'] ?? ''),
                $this->labels->eventTypeLabel((string) ($row['event_type'] ?? '')),
                $this->labels->directionLabel((string) ($row['direction'] ?? '')),
                $this->labels->paymentMethodLabel((string) ($row['payment_method'] ?? '')),
                (int) ($row['event_amount_rupiah'] ?? 0),
                $row['cash_amount_paid_rupiah'] ?? null,
                $row['cash_amount_received_rupiah'] ?? null,
                $row['cash_change_rupiah'] ?? null,
                (string) ($row['customer_payment_id'] ?? ''),
                (string) ($row['refund_id'] ?? ''),
                $this->labels->sourceLabel((string) ($row['source_table'] ?? '')),
                (string) ($row['source_id'] ?? ''),
                (string) ($row['source_disposition_id'] ?? ''),
            ];
        }

        $this->tables->writeTable($sheet, 1, [
            'No',
            'Tanggal Event',
            'ID Nota',
            'Nota',
            'Jenis Kejadian',
            'Arah',
            'Metode Pembayaran',
            'Nominal',
            'Tunai Dibayar',
            'Uang Pelanggan',
            'Kembalian Tunai',
            'ID Pembayaran',
            'ID Pengembalian Dana',
            'Asal Catatan',
            'ID Asal Catatan',
            'ID Disposisi Asal',
        ], $values);

        $this->tables->autosize($sheet, 16);
    }
}
