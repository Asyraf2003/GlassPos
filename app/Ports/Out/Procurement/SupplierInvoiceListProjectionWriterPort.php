<?php

declare(strict_types=1);

namespace App\Ports\Out\Procurement;

interface SupplierInvoiceListProjectionWriterPort
{
    /**
     * @param array{
     *   supplier_invoice_id: string,
     *   supplier_id: string,
     *   nomor_faktur: ?string,
     *   nomor_faktur_normalized: ?string,
     *   supplier_nama_pt_pengirim_snapshot: ?string,
     *   shipment_date: string,
     *   due_date: string,
     *   grand_total_rupiah: int,
     *   total_paid_rupiah: int,
     *   outstanding_rupiah: int,
     *   payment_count: int,
     *   receipt_count: int,
     *   total_received_qty: int,
     *   proof_attachment_count: int,
     *   lifecycle_status: string,
     *   payment_status: string,
     *   voided_at: ?string,
     *   last_revision_no: int,
     *   projected_at: string
     * } $row
     */
    public function upsert(array $row): void;

    /** Refresh only the revision marker on an existing projection; never rebuild aggregates. */
    public function syncRevisionFromInvoice(string $supplierInvoiceId): void;
}
