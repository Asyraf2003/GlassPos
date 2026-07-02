<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Support\ViewDateFormatter;
use App\Ports\Out\ClockPort;
use Carbon\CarbonImmutable;
use Throwable;

final class TransactionReportPdfViewDataBuilder
{
    public function __construct(
        private readonly ClockPort $clock,
    ) {
    }

    public function build(array $dataset, array $filters): array
    {
        $summary = is_array($dataset['summary'] ?? null) ? $dataset['summary'] : [];
        $rows = is_array($dataset['rows'] ?? null) ? $dataset['rows'] : [];

        $periodContext = ViewDateFormatter::reportPeriodContext(
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null,
        );

        return [
            'title' => 'Laporan Transaksi',
            'periodLabelCaption' => $periodContext['label'],
            'periodLabel' => $periodContext['value'],
            'generatedAt' => $this->clock->now()->format('d/m/Y H:i'),
            'summaryItems' => $this->summaryItems($summary),
            'rows' => array_map(fn (array $row): array => $this->rowData($row), $rows),
        ];
    }

    private function summaryItems(array $summary): array
    {
        return [
            ['label' => 'Jumlah Nota', 'value' => $this->integerValue($summary['total_rows'] ?? 0)],
            ['label' => 'Total Nilai Nota', 'value' => $this->rupiah($summary['gross_transaction_rupiah'] ?? 0)],
            ['label' => 'Pembayaran Masuk ke Nota', 'value' => $this->rupiah($summary['allocated_payment_rupiah'] ?? 0)],
            ['label' => 'Uang Refund Dibayar', 'value' => $this->rupiah($summary['refunded_rupiah'] ?? 0)],
            ['label' => 'Refund yang Harus Dibayar', 'value' => $this->rupiah($summary['refund_due_rupiah'] ?? 0)],
            ['label' => 'Kelebihan Bayar Sudah Dikembalikan', 'value' => $this->rupiah($summary['surplus_refund_paid_rupiah'] ?? 0)],
            ['label' => 'Sisa Refund Belum Dibayar', 'value' => $this->rupiah($summary['remaining_refund_due_rupiah'] ?? 0)],
            ['label' => 'Uang Bersih Diterima', 'value' => $this->rupiah($summary['net_cash_collected_rupiah'] ?? 0)],
            ['label' => 'Sisa Tagihan Customer', 'value' => $this->rupiah($summary['outstanding_rupiah'] ?? 0)],
        ];
    }

    private function rowData(array $row): array
    {
        return [
            'date' => $this->formatDate($this->stringValue($row['transaction_date'] ?? '')),
            'note_id' => $this->stringValue($row['note_id'] ?? ''),
            'customer_name' => $this->stringValue($row['customer_name'] ?? ''),
            'total' => $this->rupiah($row['gross_transaction_rupiah'] ?? 0),
            'paid' => $this->rupiah($row['allocated_payment_rupiah'] ?? 0),
            'refund' => $this->rupiah($row['refunded_rupiah'] ?? 0),
            'refund_due' => $this->rupiah($row['refund_due_rupiah'] ?? 0),
            'surplus_refund_paid' => $this->rupiah($row['surplus_refund_paid_rupiah'] ?? 0),
            'remaining_refund_due' => $this->rupiah($row['remaining_refund_due_rupiah'] ?? 0),
            'net_paid' => $this->rupiah($row['net_cash_collected_rupiah'] ?? 0),
            'outstanding' => $this->rupiah($row['outstanding_rupiah'] ?? 0),
            'status' => $this->stringValue($row['payment_status_label'] ?? ''),
        ];
    }


    private function formatDate(string $value): string
    {
        if ($value === '') {
            return '-';
        }

        try {
            return CarbonImmutable::parse($value)->format('d/m/Y');
        } catch (Throwable) {
            return $value;
        }
    }

    private function rupiah(mixed $value): string
    {
        return 'Rp '.number_format($this->integerValue($value), 0, ',', '.');
    }

    private function integerValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
