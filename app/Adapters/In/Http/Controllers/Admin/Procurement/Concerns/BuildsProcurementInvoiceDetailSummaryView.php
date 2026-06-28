<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Procurement\Concerns;

trait BuildsProcurementInvoiceDetailSummaryView
{
    /**
     * @param array<string, mixed> $summary
     * @return array<string, bool|int|string|null>
     */
    private function buildSummaryView(array $summary): array
    {
        $outstandingAmount = (int) ($summary['outstanding_rupiah'] ?? 0);
        $grandTotalRupiah = (int) ($summary['grand_total_rupiah'] ?? 0);
        $subtotalBeforeTaxRupiah = (int) ($summary['subtotal_before_tax_rupiah'] ?? $grandTotalRupiah);
        $taxAmountRupiah = (int) ($summary['tax_amount_rupiah'] ?? 0);

        $supplierNamaCurrent = trim((string) ($summary['supplier_nama_pt_pengirim_current'] ?? ''));
        $supplierNamaSnapshot = trim((string) ($summary['supplier_nama_pt_pengirim_snapshot'] ?? ''));
        $nomorFaktur = trim((string) ($summary['nomor_faktur'] ?? ''));

        return [
            'supplier_invoice_id' => (string) ($summary['supplier_invoice_id'] ?? ''),
            'nomor_faktur' => $nomorFaktur,
            'supplier_nama_pt_pengirim_current' => $supplierNamaCurrent,
            'supplier_nama_pt_pengirim_snapshot' => $supplierNamaSnapshot,
            'shipment_date' => (string) ($summary['shipment_date'] ?? ''),
            'due_date' => (string) ($summary['due_date'] ?? ''),
            'subtotal_before_tax_rupiah' => $subtotalBeforeTaxRupiah,
            'subtotal_before_tax_label' => $this->formatRupiah($subtotalBeforeTaxRupiah),
            'tax_input' => ($summary['tax_input'] ?? null) !== null ? (string) $summary['tax_input'] : null,
            'tax_mode' => (string) ($summary['tax_mode'] ?? 'none'),
            'tax_rate_basis_points' => ($summary['tax_rate_basis_points'] ?? null) !== null
                ? (int) $summary['tax_rate_basis_points']
                : null,
            'tax_amount_rupiah' => $taxAmountRupiah,
            'tax_amount_label' => $this->formatRupiah($taxAmountRupiah),
            'grand_total_label' => $this->formatRupiah($grandTotalRupiah),
            'total_paid_label' => $this->formatRupiah((int) ($summary['total_paid_rupiah'] ?? 0)),
            'outstanding_label' => $this->formatRupiah($outstandingAmount),
            'outstanding_amount' => $outstandingAmount,
            'can_record_payment' => $outstandingAmount > 0,
            'receipt_count' => (int) ($summary['receipt_count'] ?? 0),
            'total_received_qty' => (int) ($summary['total_received_qty'] ?? 0),
            'last_revision_no' => (int) ($summary['last_revision_no'] ?? 0),
            'latest_revision_event_name' => ($summary['latest_revision_event_name'] ?? null) !== null
                ? (string) $summary['latest_revision_event_name']
                : null,
            'latest_revision_reason' => ($summary['latest_revision_change_reason'] ?? null) !== null
                ? (string) $summary['latest_revision_change_reason']
                : null,
            'latest_revision_changed_at' => ($summary['latest_revision_changed_at'] ?? null) !== null
                ? (string) $summary['latest_revision_changed_at']
                : null,
        ];
    }
}
