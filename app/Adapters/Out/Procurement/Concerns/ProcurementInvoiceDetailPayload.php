<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement\Concerns;

trait ProcurementInvoiceDetailPayload
{
    /**
     * @param list<array{
     *   id:string,
     *   supplier_invoice_id:string,
     *   product_id:string,
     *   kode_barang:?string,
     *   nama_barang:string,
     *   merek:string,
     *   ukuran:?int,
     *   qty_pcs:int,
     *   line_total_rupiah:int,
     *   unit_cost_rupiah:int
     * }> $lines
     * @return array{
     *   summary: array<string, mixed>,
     *   lines: list<array<string, mixed>>
     * }
     */
    private function toDetailPayload(object $summary, array $lines, array $versionTimeline): array
    {
        $totalPaidRupiah = (int) $summary->total_paid_rupiah;
        $receiptCount = (int) $summary->receipt_count;

        $policyState = trim((string) ($summary->policy_state ?? 'editable'));

        $allowedActionsCsv = trim((string) ($summary->allowed_actions_csv ?? ''));
        $allowedActions = $allowedActionsCsv === ''
            ? []
            : array_values(array_filter(array_map(
                static fn (string $value): string => trim($value),
                explode(',', $allowedActionsCsv),
            ), static fn (string $value): bool => $value !== ''));

        $lockReasonsCsv = trim((string) ($summary->lock_reasons_csv ?? ''));
        $lockReasons = $lockReasonsCsv === ''
            ? []
            : array_values(array_filter(array_map(
                static fn (string $value): string => trim($value),
                explode(',', $lockReasonsCsv),
            ), static fn (string $value): bool => $value !== ''));

        return [
            'summary' => [
                'supplier_invoice_id' => (string) $summary->supplier_invoice_id,
                'nomor_faktur' => $summary->nomor_faktur !== null
                    ? (string) $summary->nomor_faktur
                    : '',
                'supplier_id' => (string) $summary->supplier_id,
                'supplier_nama_pt_pengirim_current' => $summary->supplier_nama_pt_pengirim_current !== null
                    ? (string) $summary->supplier_nama_pt_pengirim_current
                    : '',
                'supplier_nama_pt_pengirim_snapshot' => (string) $summary->supplier_nama_pt_pengirim_snapshot,
                'shipment_date' => (string) $summary->shipment_date,
                'due_date' => (string) $summary->due_date,
                'subtotal_before_tax_rupiah' => (int) ($summary->subtotal_before_tax_rupiah ?? 0),
                'tax_input' => $summary->tax_input !== null ? (string) $summary->tax_input : null,
                'tax_mode' => (string) ($summary->tax_mode ?? 'none'),
                'tax_rate_basis_points' => $summary->tax_rate_basis_points !== null ? (int) $summary->tax_rate_basis_points : null,
                'tax_amount_rupiah' => (int) ($summary->tax_amount_rupiah ?? 0),
                'grand_total_rupiah' => (int) $summary->grand_total_rupiah,
                'last_revision_no' => (int) $summary->last_revision_no,
                'latest_revision_event_name' => $summary->latest_revision_event_name !== null
                    ? (string) $summary->latest_revision_event_name
                    : null,
                'latest_revision_change_reason' => $summary->latest_revision_change_reason !== null
                    ? (string) $summary->latest_revision_change_reason
                    : null,
                'latest_revision_changed_at' => $summary->latest_revision_changed_at !== null
                    ? (string) $summary->latest_revision_changed_at
                    : null,
                'total_paid_rupiah' => $totalPaidRupiah,
                'outstanding_rupiah' => (int) $summary->outstanding_rupiah,
                'receipt_count' => $receiptCount,
                'latest_receipt_date' => $summary->latest_receipt_date !== null
                    ? (string) $summary->latest_receipt_date
                    : null,
                'total_received_qty' => (int) $summary->total_received_qty,
                'voided_at' => $summary->voided_at !== null ? (string) $summary->voided_at : null,
                'void_reason' => $summary->void_reason !== null ? (string) $summary->void_reason : null,
                'policy_state' => $policyState,
                'lock_reasons' => $lockReasons,
                'allowed_actions' => $allowedActions,
            ],
            'lines' => $lines,
            'version_timeline' => $versionTimeline,
        ];
    }
}
