<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement\Concerns;

use App\Core\Procurement\SupplierInvoice\SupplierInvoice;
use Illuminate\Support\Facades\DB;

trait PersistsVersionedSupplierInvoiceWrites
{
    private function persistCreatedInvoice(SupplierInvoice $supplierInvoice): void
    {
        $revisionNo = 1;
        $occurredAt = $this->clock->now();
        $context = $this->changeContext->snapshot();
        $snapshot = $this->toVersionSnapshot($supplierInvoice);
        $eventName = 'supplier_invoice_created';

        DB::table('supplier_invoices')->insert($this->toInvoiceRecord($supplierInvoice, $revisionNo));
        DB::table('supplier_invoice_lines')->insert($this->toLineRecords($supplierInvoice, $revisionNo));
        DB::table('supplier_invoice_versions')->insert($this->toVersionRecord($supplierInvoice, $revisionNo, $eventName, $occurredAt, $context, $snapshot));

        $auditEventId = $this->uuid->generate();

        DB::table('audit_events')->insert($this->toAuditEventRecord($auditEventId, $supplierInvoice, $revisionNo, $eventName, $occurredAt, $context, $snapshot));
        DB::table('audit_event_snapshots')->insert($this->toAuditSnapshotRecord($auditEventId, 'after', $snapshot, $occurredAt));
    }

    private function persistUpdatedInvoice(SupplierInvoice $supplierInvoice): void
    {
        $current = $this->loadCurrentInvoiceWriteSnapshot($supplierInvoice->id());
        $revisionNo = $current['last_revision_no'] + 1;
        $occurredAt = $this->clock->now();
        $context = $this->changeContext->snapshot();
        $beforeSnapshot = $current['snapshot'];
        $afterSnapshot = $this->toVersionSnapshot($supplierInvoice);
        $eventName = 'supplier_invoice_updated';

        $metadataFields = ['nomor_faktur' => true, 'nomor_faktur_normalized' => true];
        $metadataOnly = array_diff_key($beforeSnapshot, $metadataFields)
            === array_diff_key($afterSnapshot, $metadataFields);
        DB::table('supplier_invoices')
            ->where('id', $supplierInvoice->id())
            ->update($metadataOnly ? [
                'nomor_faktur' => $supplierInvoice->nomorFaktur(),
                'nomor_faktur_normalized' => $supplierInvoice->nomorFakturNormalized(),
                'last_revision_no' => $revisionNo,
            ] : $this->toInvoiceRecord($supplierInvoice, $revisionNo));

        if (! $metadataOnly) {
            DB::table('supplier_invoice_lines')
                ->where('supplier_invoice_id', $supplierInvoice->id())
                ->where('is_current', true)
                ->update([
                    'is_current' => false,
                    'superseded_at' => $occurredAt,
                ]);

            DB::table('supplier_invoice_lines')->insert($this->toLineRecords($supplierInvoice, $revisionNo));
        }
        DB::table('supplier_invoice_versions')->insert($this->toVersionRecord($supplierInvoice, $revisionNo, $eventName, $occurredAt, $context, $afterSnapshot));

        $auditEventId = $this->uuid->generate();

        DB::table('audit_events')->insert($this->toAuditEventRecord($auditEventId, $supplierInvoice, $revisionNo, $eventName, $occurredAt, $context, $afterSnapshot));
        DB::table('audit_event_snapshots')->insert([
            $this->toAuditSnapshotRecord($auditEventId, 'before', $beforeSnapshot, $occurredAt),
            $this->toAuditSnapshotRecord($auditEventId, 'after', $afterSnapshot, $occurredAt),
        ]);
    }
}
