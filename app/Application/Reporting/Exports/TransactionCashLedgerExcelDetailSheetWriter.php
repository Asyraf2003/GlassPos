<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Support\ViewDateFormatter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class TransactionCashLedgerExcelDetailSheetWriter
{
    public function __construct(
        private readonly TransactionReportExcelTableWriter $tables,
    ) {}

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
                $this->eventTypeLabel((string) ($row['event_type'] ?? '')),
                $this->directionLabel((string) ($row['direction'] ?? '')),
                $this->paymentMethodLabel((string) ($row['payment_method'] ?? '')),
                (int) ($row['event_amount_rupiah'] ?? 0),
                (string) ($row['customer_payment_id'] ?? ''),
                (string) ($row['refund_id'] ?? ''),
                $this->sourceLabel((string) ($row['source_table'] ?? '')),
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
            'ID Pembayaran',
            'ID Pengembalian Dana',
            'Asal Catatan',
            'ID Asal Catatan',
            'ID Disposisi Asal',
        ], $values);

        $this->tables->autosize($sheet, 13);
    }

    private function eventTypeLabel(string $type): string
    {
        return match ($type) {
            'payment_allocation' => 'Pembayaran Tercatat',
            'payment' => 'Pembayaran',
            'refund' => 'Pengembalian Dana',
            default => $type,
        };
    }

    private function sourceLabel(string $source): string
    {
        return match ($source) {
            'payment_allocations' => 'Pembayaran Nota',
            'payment_component_allocations' => 'Pembayaran Rincian Nota',
            'customer_payments' => 'Pembayaran Pelanggan',
            'customer_refunds' => 'Pengembalian Dana',
            'refund_component_allocations' => 'Pengembalian Rincian Nota',
            'note_revision_surplus_refund_payments' => 'Pengembalian Surplus Dibayar',
            'note_revision_surplus_dispositions' => 'Pengembalian Surplus Ditandai',
            '' => '-',
            default => $source,
        };
    }

    private function directionLabel(string $direction): string
    {
        return match ($direction) {
            'in' => 'Masuk',
            'out' => 'Keluar',
            default => $direction,
        };
    }

    private function paymentMethodLabel(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'cash' => 'Tunai',
            'transfer' => 'Transfer',
            '' => '-',
            default => $paymentMethod,
        };
    }
}
