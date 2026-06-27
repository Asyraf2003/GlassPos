<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Application\Reporting\Exports\Concerns\FormatsPdfReportValues;
use App\Ports\Out\ClockPort;

final class TransactionCashLedgerPdfViewDataBuilder
{
    use FormatsPdfReportValues;

    public function __construct(
        private readonly ClockPort $clock,
    ) {
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
            'event_type' => $this->eventTypeLabel($this->stringValue($row['event_type'] ?? '')),
            'direction' => $this->directionLabel($this->stringValue($row['direction'] ?? '')),
            'payment_method' => $this->paymentMethodLabel($this->stringValue($row['payment_method'] ?? '')),
            'amount' => $this->rupiah($row['event_amount_rupiah'] ?? 0),
            'payment_marker' => $paymentId !== '' ? 'Ada' : '-',
            'refund_marker' => $refundId !== '' ? 'Ada' : '-',
            'source_table' => $this->sourceLabel($this->stringValue($row['source_table'] ?? '-')),
            'source_id' => $this->stringValue($row['source_id'] ?? '-') ?: '-',
            'source_disposition_id' => $this->stringValue($row['source_disposition_id'] ?? '-') ?: '-',
        ];
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
            '', '-' => '-',
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
}
