<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Application\Reporting\Exports\Concerns\FormatsPdfReportValues;
use App\Application\Reporting\Services\ReportTemporalContext;
use App\Ports\Out\ClockPort;

final class EmployeeDebtReportPdfViewDataBuilder
{
    use FormatsPdfReportValues;

    public function __construct(
        private readonly ClockPort $clock,
    ) {}

    public function build(array $dataset, array $filters): array
    {
        $summary = is_array($dataset['summary'] ?? null) ? $dataset['summary'] : [];
        $rows = is_array($dataset['rows'] ?? null) ? $dataset['rows'] : [];
        $periodRows = is_array($dataset['period_rows'] ?? null) ? $dataset['period_rows'] : [];
        $statusRows = is_array($dataset['status_rows'] ?? null) ? $dataset['status_rows'] : [];

        return [
            'temporalContext' => ReportTemporalContext::description('EmployeeDebtReport', $filters),
            'title' => 'Laporan Hutang Karyawan',
            'periodLabel' => $this->formatRange(
                $this->stringValue($filters['date_from'] ?? ''),
                $this->stringValue($filters['date_to'] ?? ''),
            ),
            'generatedAt' => $this->clock->now()->format('d/m/Y H:i'),
            'detailTables' => [['title' => 'Rincian', 'columns' => ['recorded_at' => 'Tanggal Kasbon', 'employee_id' => 'Karyawan', 'debt_id' => 'ID Kasbon', 'total_debt' => 'Pokok per '.$this->formatDate($filters['date_to']), 'total_paid_amount' => 'Dibayar sampai '.$this->formatDate($filters['date_to']), 'remaining_balance' => 'Sisa per '.$this->formatDate($filters['date_to']), 'status' => 'Status', 'notes' => 'Catatan'],
                'rows' => array_map(fn (array $row): array => $this->rowData($row), $rows)]],
            'summaryItems' => array_map(fn (array $row): array => [
                'label' => $row['label'], 'value' => $this->rupiah($row['value']),
            ], $dataset['temporal_summary_rows'] ?? []),
            'periodRows' => array_map(fn (array $row): array => $this->periodRowData($row), $periodRows),
            'statusRows' => array_map(fn (array $row): array => $this->statusRowData($row), $statusRows),
            'rows' => array_map(fn (array $row): array => $this->rowData($row), $rows),
        ];
    }

    private function periodRowData(array $row): array
    {
        return [
            'period_label' => $this->formatDate($this->stringValue($row['period_label'] ?? '')),
            'total_rows' => $this->integerValue($row['total_rows'] ?? 0),
            'total_debt' => $this->rupiah($row['total_debt'] ?? 0),
            'total_paid_amount' => $this->rupiah($row['total_paid_amount'] ?? 0),
            'total_remaining_balance' => $this->rupiah($row['total_remaining_balance'] ?? 0),
        ];
    }

    private function statusRowData(array $row): array
    {
        return [
            'status' => (($row['status'] ?? '') === 'paid' ? 'Lunas' : 'Belum Lunas'),
            'total_rows' => $this->integerValue($row['total_rows'] ?? 0),
            'total_debt' => $this->rupiah($row['total_debt'] ?? 0),
            'total_paid_amount' => $this->rupiah($row['total_paid_amount'] ?? 0),
            'total_remaining_balance' => $this->rupiah($row['total_remaining_balance'] ?? 0),
        ];
    }

    private function rowData(array $row): array
    {
        return [
            'recorded_at' => $this->formatDate($this->stringValue($row['recorded_at'] ?? '')),
            'debt_id' => $this->stringValue($row['debt_id'] ?? ''),
            'employee_id' => $this->stringValue($row['employee_id'] ?? ''),
            'status' => (($row['status'] ?? '') === 'paid' ? 'Lunas' : 'Belum Lunas'),
            'total_debt' => $this->rupiah($row['total_debt'] ?? 0),
            'total_paid_amount' => $this->rupiah($row['total_paid_amount'] ?? 0),
            'remaining_balance' => $this->rupiah($row['remaining_balance'] ?? 0),
            'notes' => $this->nullableString($row['notes'] ?? null),
        ];
    }
}
