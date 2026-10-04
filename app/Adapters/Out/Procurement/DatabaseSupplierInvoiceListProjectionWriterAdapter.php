<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Ports\Out\Procurement\SupplierInvoiceListProjectionWriterPort;
use Illuminate\Support\Facades\DB;

final class DatabaseSupplierInvoiceListProjectionWriterAdapter implements SupplierInvoiceListProjectionWriterPort
{
    public function syncRevisionFromInvoice(string $supplierInvoiceId): void
    {
        // Identity revisions do not reconcile receipt/payment/supplier aggregates.
        // Missing projections remain a separate rebuild concern.
        DB::table('supplier_invoice_list_projection')
            ->where('supplier_invoice_id', $supplierInvoiceId)
            ->update(['last_revision_no' => DB::table('supplier_invoices')
                ->select('last_revision_no')->where('id', $supplierInvoiceId)]);
    }

    public function upsert(array $row): void
    {
        DB::table('supplier_invoice_list_projection')->updateOrInsert(
            ['supplier_invoice_id' => $row['supplier_invoice_id']],
            [
                'supplier_id' => $row['supplier_id'],
                'nomor_faktur' => $row['nomor_faktur'],
                'nomor_faktur_normalized' => $row['nomor_faktur_normalized'],
                'supplier_nama_pt_pengirim_snapshot' => $row['supplier_nama_pt_pengirim_snapshot'],
                'shipment_date' => $row['shipment_date'],
                'due_date' => $row['due_date'],
                'grand_total_rupiah' => $row['grand_total_rupiah'],
                'total_paid_rupiah' => $row['total_paid_rupiah'],
                'outstanding_rupiah' => $row['outstanding_rupiah'],
                'payment_count' => $row['payment_count'],
                'receipt_count' => $row['receipt_count'],
                'total_received_qty' => $row['total_received_qty'],
                'proof_attachment_count' => $row['proof_attachment_count'],
                'lifecycle_status' => $row['lifecycle_status'],
                'payment_status' => $row['payment_status'],
                'voided_at' => $row['voided_at'],
                'last_revision_no' => $row['last_revision_no'],
                'projected_at' => $row['projected_at'],
            ],
        );
    }
}
