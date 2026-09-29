<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Application\Reporting\Exports\Concerns\FormatsPdfReportValues;
use App\Application\Reporting\Services\ReportTemporalContext;
use App\Ports\Out\ClockPort;

final class OperationalExpenseReportPdfViewDataBuilder
{
    use FormatsPdfReportValues;

    public function __construct(
        private readonly ClockPort $clock,
    ) {}

    public function build(array $dataset, array $filters): array
    {
        $summary = is_array($dataset['summary'] ?? null) ? $dataset['summary'] : [];
        $rows = is_array($dataset['rows'] ?? null) ? $dataset['rows'] : [];

        return [
            'temporalContext' => ReportTemporalContext::description('OperationalExpenseReport', $filters),
            'title' => 'Laporan Biaya Operasional',
            'periodLabel' => $this->formatRange(
                $this->stringValue($filters['date_from'] ?? ''),
                $this->stringValue($filters['date_to'] ?? ''),
            ),
            'generatedAt' => $this->clock->now()->format('d/m/Y H:i'),
            'detailTables' => [['title' => 'Rincian', 'columns' => ['date' => 'Tanggal', 'category_name' => 'Kategori', 'description' => 'Keterangan', 'payment_method' => 'Metode', 'reference_no' => 'Referensi', 'amount' => 'Biaya periode'],
                'rows' => array_map(fn (array $row): array => $this->rowData($row), $rows)]],
            'summaryItems' => $this->summaryItems($summary),
            'rows' => array_map(fn (array $row): array => $this->rowData($row), $rows),
        ];
    }

    private function summaryItems(array $summary): array
    {
        return [
            ['label' => 'Jumlah Catatan', 'value' => $this->integerValue($summary['total_rows'] ?? 0)],
            ['label' => 'Total Biaya', 'value' => $this->rupiah($summary['total_amount_rupiah'] ?? 0)],
            ['label' => 'Kategori Terbesar', 'value' => $this->stringValue($summary['top_category_name'] ?? '-')],
            ['label' => 'Nilai Kategori', 'value' => $this->rupiah($summary['top_category_amount_rupiah'] ?? 0)],
            ['label' => 'Rata-rata Harian', 'value' => $this->rupiah($summary['average_daily_rupiah'] ?? 0)],
        ];
    }

    private function rowData(array $row): array
    {
        return [
            'date' => $this->formatDate($this->stringValue($row['expense_date'] ?? '')),
            'expense_id' => $this->stringValue($row['expense_id'] ?? ''),
            'category_name' => $this->stringValue($row['category_name'] ?? ''),
            'description' => $this->stringValue($row['description'] ?? ''),
            'payment_method' => $this->paymentMethodLabel($this->stringValue($row['payment_method'] ?? '')),
            'reference_no' => $this->stringValue($row['reference_no'] ?? '-'),
            'amount' => $this->rupiah($row['amount_rupiah'] ?? 0),
        ];
    }

    private function paymentMethodLabel(string $method): string
    {
        return match ($method) {
            'cash' => 'Tunai',
            'tf', 'transfer', 'bank_transfer' => 'Transfer',
            'debit' => 'Debit',
            'credit' => 'Kredit',
            'qris' => 'QRIS',
            default => strtoupper($method),
        };
    }
}
