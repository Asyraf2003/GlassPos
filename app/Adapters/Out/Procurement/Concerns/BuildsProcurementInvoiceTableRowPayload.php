<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement\Concerns;

trait BuildsProcurementInvoiceTableRowPayload
{
    /**
     * @return array<string, bool|int|string>
     */
    private function toTableRowPayload(object $row): array
    {
        $supplierInvoiceId = (string) $row->supplier_invoice_id;
        $outstandingRupiah = (int) $row->outstanding_rupiah;
        $paymentCount = (int) $row->payment_count;
        $receiptCount = (int) $row->receipt_count;
        $proofAttachmentCount = (int) $row->proof_attachment_count;
        $isVoided = $row->voided_at !== null;
        $isLocked = ! $isVoided && ($paymentCount > 0 || $receiptCount > 0);

        $paymentActionKind = $outstandingRupiah > 0 ? 'payment_proof' : 'proof';
        $paymentActionLabel = $outstandingRupiah > 0 ? 'Bayar' : 'Bukti Bayar';
        $paymentActionMode = $outstandingRupiah > 0 ? 'modal' : 'link';

        return [
            'supplier_invoice_id' => $supplierInvoiceId,
            'nomor_faktur' => $row->nomor_faktur !== null ? (string) $row->nomor_faktur : '',
            'supplier_nama_pt_pengirim_current' => $row->supplier_nama_pt_pengirim_current !== null
                ? (string) $row->supplier_nama_pt_pengirim_current
                : '',
            'supplier_nama_pt_pengirim_snapshot' => (string) $row->supplier_nama_pt_pengirim_snapshot,
            'shipment_date' => (string) $row->shipment_date,
            'due_date' => (string) $row->due_date,
            'grand_total_rupiah' => (int) $row->grand_total_rupiah,
            'total_paid_rupiah' => (int) $row->total_paid_rupiah,
            'outstanding_rupiah' => $outstandingRupiah,
            'payment_count' => $paymentCount,
            'receipt_count' => $receiptCount,
            'total_received_qty' => (int) $row->total_received_qty,
            'proof_attachment_count' => $proofAttachmentCount,
            'can_record_payment' => ! $isVoided && $outstandingRupiah > 0,
            'has_uploaded_proof' => $proofAttachmentCount > 0,
            'policy_state' => $isVoided ? 'voided' : ($isLocked ? 'locked' : 'editable'),
            'payment_action_enabled' => ! $isVoided,
            'payment_action_kind' => $paymentActionKind,
            'payment_action_label' => $paymentActionLabel,
            'payment_action_mode' => $paymentActionMode,
            'payment_action_url' => route('admin.procurement.supplier-invoices.payment-proofs.show', [
                'supplierInvoiceId' => $supplierInvoiceId,
            ]),
            'edit_action_kind' => $isLocked ? 'revise' : 'edit',
            'edit_action_label' => $isLocked ? 'Koreksi' : 'Edit Nota',
            'edit_action_url' => $isVoided
                ? ''
                : route($isLocked
                    ? 'admin.procurement.supplier-invoices.revise'
                    : 'admin.procurement.supplier-invoices.edit', [
                    'supplierInvoiceId' => $supplierInvoiceId,
                ]),
            'void_action_enabled' => ! $isVoided && ! $isLocked,
            'void_action_label' => 'Hapus Nota',
            'void_action_url' => route('admin.procurement.supplier-invoices.void', [
                'supplierInvoiceId' => $supplierInvoiceId,
            ]),
        ];
    }
}
