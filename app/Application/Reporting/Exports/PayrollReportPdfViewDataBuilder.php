<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Application\Reporting\Exports\Concerns\FormatsPdfReportValues;
use App\Application\Reporting\Services\ReportTemporalContext;
use App\Ports\Out\ClockPort;

final class PayrollReportPdfViewDataBuilder
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
        $modeRows = is_array($dataset['mode_rows'] ?? null) ? $dataset['mode_rows'] : [];

        return [
            'temporalContext' => ReportTemporalContext::description('PayrollReport', $filters),
            'title' => 'Laporan Gaji',
            'periodLabel' => $this->formatRange(
                $this->stringValue($filters['date_from'] ?? ''),
                $this->stringValue($filters['date_to'] ?? ''),
            ),
            'generatedAt' => $this->clock->now()->format('d/m/Y H:i'),
            'detailTables' => [['title' => 'Rincian', 'columns' => ['date' => 'Tanggal', 'employee_name' => 'Karyawan', 'mode_label' => 'Mode', 'notes' => 'Catatan', 'amount' => 'Nominal periode'],
                'rows' => array_map(fn (array $row): array => $this->rowData($row), $rows)]],
            'summaryItems' => $this->summaryItems($summary),
            'periodRows' => array_map(fn (array $row): array => $this->periodRowData($row), $periodRows),
            'modeRows' => array_map(fn (array $row): array => $this->modeRowData($row), $modeRows),
            'rows' => array_map(fn (array $row): array => $this->rowData($row), $rows),
        ];
    }

    private function summaryItems(array $summary): array
    {
        return [
            ['label' => 'Jumlah Pencairan', 'value' => $this->integerValue($summary['total_rows'] ?? 0)],
            ['label' => 'Total Nominal', 'value' => $this->rupiah($summary['total_amount_rupiah'] ?? 0)],
            ['label' => 'Tanggal Terakhir', 'value' => $this->formatDate($this->stringValue($summary['latest_disbursement_date'] ?? ''))],
            ['label' => 'Mode Terbesar', 'value' => $this->stringValue($summary['top_mode_label'] ?? '-')],
            ['label' => 'Rata-rata Harian', 'value' => $this->rupiah($summary['average_daily_rupiah'] ?? 0)],
        ];
    }

    private function periodRowData(array $row): array
    {
        return [
            'period_label' => $this->stringValue($row['period_label'] ?? ''),
            'total_rows' => $this->integerValue($row['total_rows'] ?? 0),
            'total_amount' => $this->rupiah($row['total_amount_rupiah'] ?? 0),
        ];
    }

    private function modeRowData(array $row): array
    {
        return [
            'mode_label' => $this->stringValue($row['mode_label'] ?? ''),
            'total_rows' => $this->integerValue($row['total_rows'] ?? 0),
            'total_amount' => $this->rupiah($row['total_amount_rupiah'] ?? 0),
        ];
    }

    private function rowData(array $row): array
    {
        return [
            'date' => $this->formatDate($this->stringValue($row['disbursement_date'] ?? '')),
            'employee_name' => $this->stringValue($row['employee_name'] ?? ''),
            'mode_label' => $this->stringValue($row['mode_label'] ?? ''),
            'notes' => $this->nullableString($row['notes'] ?? null),
            'amount' => $this->rupiah($row['amount_rupiah'] ?? 0),
        ];
    }
}
