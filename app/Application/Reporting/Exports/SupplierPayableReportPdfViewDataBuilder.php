<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Application\Reporting\Exports\Concerns\FormatsPdfReportValues;
use App\Application\Reporting\Services\ReportTemporalContext;
use App\Ports\Out\ClockPort;

final class SupplierPayableReportPdfViewDataBuilder
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
        $supplierRows = is_array($dataset['supplier_rows'] ?? null) ? $dataset['supplier_rows'] : [];

        return [
            'temporalContext' => ReportTemporalContext::description('SupplierPayableReport', $filters),
            'title' => 'Hutang Pemasok '.(($dataset['as_of_date'] ?? null) === null ? 'Saat Ini' : 'per '.$dataset['as_of_date']),
            'periodLabel' => ($filters['period_mode'] ?? '') === 'all' ? 'Seluruh Periode' : $this->formatRange(
                $this->stringValue($filters['date_from'] ?? ''),
                $this->stringValue($filters['date_to'] ?? ''),
            ),
            'referenceDateLabel' => $this->formatDate($this->stringValue($filters['reference_date'] ?? '')),
            'generatedAt' => $this->clock->now()->format('d/m/Y H:i'),
            'detailTables' => [['title' => 'Rincian', 'columns' => ['invoice_no' => 'Faktur', 'supplier' => 'Supplier', 'shipment_date' => 'Pengiriman', 'due_date' => 'Jatuh Tempo', 'grand_total' => 'Tagihan pada posisi terpilih', 'total_paid' => 'Pembayaran sampai posisi terpilih', 'outstanding' => 'Sisa pada posisi terpilih', 'status' => 'Status'],
                'rows' => array_map(fn (array $row): array => $this->rowData($row), $rows)]],
            'summaryItems' => array_merge(array_map(fn (array $row): array => ['label' => $row['label'], 'value' => $this->rupiah($row['value'])], $dataset['temporal_summary_rows'] ?? []), $this->summaryItems($summary)),
            'periodRows' => array_map(fn (array $row): array => $this->periodRowData($row), $periodRows),
            'supplierRows' => array_map(fn (array $row): array => $this->supplierRowData($row), $supplierRows),
            'rows' => array_map(fn (array $row): array => $this->rowData($row), $rows),
        ];
    }

    private function summaryItems(array $summary): array
    {
        return [
            ['label' => 'Total Faktur', 'value' => $this->integerValue($summary['total_rows'] ?? 0)],
            ['label' => 'Total Tagihan', 'value' => $this->rupiah($summary['grand_total_rupiah'] ?? 0)],
            ['label' => 'Total Dibayar', 'value' => $this->rupiah($summary['total_paid_rupiah'] ?? 0)],
            ['label' => 'Outstanding', 'value' => $this->rupiah($summary['outstanding_rupiah'] ?? 0)],
            ['label' => 'Belum Jatuh Tempo', 'value' => $this->integerValue($summary['not_due_rows'] ?? 0)],
            ['label' => 'Jatuh Tempo Hari Ini', 'value' => $this->integerValue($summary['due_today_rows'] ?? 0)],
            ['label' => 'Lewat Jatuh Tempo', 'value' => $this->integerValue($summary['overdue_rows'] ?? 0)],
            ['label' => 'Sisa Hutang Lewat Tempo', 'value' => $this->rupiah($summary['overdue_outstanding_rupiah'] ?? 0)],
            ['label' => 'Belum Lunas', 'value' => $this->integerValue($summary['open_rows'] ?? 0)],
            ['label' => 'Lunas', 'value' => $this->integerValue($summary['settled_rows'] ?? 0)],
            ['label' => 'Jumlah Receipt', 'value' => $this->integerValue($summary['receipt_count'] ?? 0)],
            ['label' => 'Total Qty Diterima', 'value' => $this->integerValue($summary['total_received_qty'] ?? 0)],
        ];
    }

    private function periodRowData(array $row): array
    {
        return [
            'period_label' => $this->formatDate($this->stringValue($row['period_label'] ?? '')),
            'total_rows' => $this->integerValue($row['total_rows'] ?? 0),
            'grand_total' => $this->rupiah($row['grand_total_rupiah'] ?? 0),
            'total_paid' => $this->rupiah($row['total_paid_rupiah'] ?? 0),
            'outstanding' => $this->rupiah($row['outstanding_rupiah'] ?? 0),
        ];
    }

    private function supplierRowData(array $row): array
    {
        return [
            'supplier' => $this->stringValue($row['supplier_name'] ?? $row['supplier_id'] ?? ''),
            'total_rows' => $this->integerValue($row['total_rows'] ?? 0),
            'grand_total' => $this->rupiah($row['grand_total_rupiah'] ?? 0),
            'total_paid' => $this->rupiah($row['total_paid_rupiah'] ?? 0),
            'outstanding' => $this->rupiah($row['outstanding_rupiah'] ?? 0),
        ];
    }

    private function rowData(array $row): array
    {
        return [
            'invoice_no' => $this->stringValue($row['nomor_faktur'] ?? $row['supplier_invoice_id'] ?? ''),
            'supplier' => $this->stringValue($row['supplier_name'] ?? $row['supplier_id'] ?? ''),
            'shipment_date' => $this->formatDate($this->stringValue($row['shipment_date'] ?? '')),
            'due_date' => $this->formatDate($this->stringValue($row['due_date'] ?? '')),
            'status' => $this->stringValue($row['due_status_label'] ?? $row['due_status'] ?? ''),
            'grand_total' => $this->rupiah($row['grand_total_rupiah'] ?? 0),
            'total_paid' => $this->rupiah($row['total_paid_rupiah'] ?? 0),
            'outstanding' => $this->rupiah($row['outstanding_rupiah'] ?? 0),
            'receipt_count' => $this->integerValue($row['receipt_count'] ?? 0),
            'total_received_qty' => $this->integerValue($row['total_received_qty'] ?? 0),
        ];
    }
}
