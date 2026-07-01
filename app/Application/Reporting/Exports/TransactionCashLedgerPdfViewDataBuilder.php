<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Application\Reporting\Exports\Concerns\FormatsPdfReportValues;
use App\Ports\Out\ClockPort;

final class TransactionCashLedgerPdfViewDataBuilder
{
    use FormatsPdfReportValues;

    private readonly TransactionCashLedgerExportLabelFormatter $labels;

    public function __construct(
        private readonly ClockPort $clock,
        ?TransactionCashLedgerExportLabelFormatter $labels = null,
    ) {
        $this->labels = $labels ?? new TransactionCashLedgerExportLabelFormatter();
    }

    public function build(array $dataset, array $filters): array
    {
        $summary = is_array($dataset['summary'] ?? null) ? $dataset['summary'] : [];
        $rows = is_array($dataset['rows'] ?? null) ? $dataset['rows'] : [];

        return [
            'title' => 'Laporan Buku Kas Transaksi',
            'periodLabel' => $this->formatRange(
                $this->stringValue($filters['date_from'] ?? ''),
                $this->stringValue($filters['date_to'] ?? ''),
            ),
            'generatedAt' => $this->clock->now()->format('d/m/Y H:i'),
            'summaryItems' => $this->summaryItems($summary),
            'rows' => array_map(fn (array $row): array => $this->rowData($row), $rows),
        ];
    }

    private function summaryItems(array $summary): array
    {
        return [
            ['label' => 'Total Kejadian', 'value' => $this->integerValue($summary['total_events'] ?? 0)],
            ['label' => 'Kas Masuk', 'value' => $this->rupiah($summary['total_cash_in_rupiah'] ?? 0)],
            ['label' => 'Tunai Masuk', 'value' => $this->rupiah($summary['cash_in_rupiah'] ?? 0)],
            ['label' => 'Transfer Masuk', 'value' => $this->rupiah($summary['transfer_in_rupiah'] ?? 0)],
            ['label' => 'Kas Keluar', 'value' => $this->rupiah($summary['total_cash_out_rupiah'] ?? 0)],
            ['label' => 'Nilai Bersih', 'value' => $this->rupiah($summary['net_amount_rupiah'] ?? 0)],
        ];
    }

    private function rowData(array $row): array
    {
        $paymentId = $this->stringValue($row['customer_payment_id'] ?? '');
        $refundId = $this->stringValue($row['refund_id'] ?? '');

        return [
            'date' => $this->formatDate($this->stringValue($row['event_date'] ?? '')),
            'note_label' => $this->stringValue($row['note_label'] ?? $row['note_id'] ?? ''),
            'event_type' => $this->labels->eventTypeLabel($this->stringValue($row['event_type'] ?? '')),
            'direction' => $this->labels->directionLabel($this->stringValue($row['direction'] ?? '')),
            'payment_method' => $this->labels->paymentMethodLabel($this->stringValue($row['payment_method'] ?? '')),
            'amount' => $this->rupiah($row['event_amount_rupiah'] ?? 0),
            'cash_amount_paid' => $this->nullableRupiah($row['cash_amount_paid_rupiah'] ?? null),
            'cash_amount_received' => $this->nullableRupiah($row['cash_amount_received_rupiah'] ?? null),
            'cash_change' => $this->nullableRupiah($row['cash_change_rupiah'] ?? null),
            'payment_marker' => $paymentId !== '' ? 'Ada' : '-',
            'refund_marker' => $refundId !== '' ? 'Ada' : '-',
            'source_table' => $this->labels->sourceLabel($this->stringValue($row['source_table'] ?? '-')),
            'source_id' => $this->stringValue($row['source_id'] ?? '-') ?: '-',
            'source_disposition_id' => $this->stringValue($row['source_disposition_id'] ?? '-') ?: '-',
        ];
    }

    private function nullableRupiah(mixed $value): string
    {
        return $value === null ? '-' : $this->rupiah($value);
    }
}
