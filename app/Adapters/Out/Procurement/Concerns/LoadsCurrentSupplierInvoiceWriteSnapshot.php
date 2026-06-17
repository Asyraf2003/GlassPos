<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement\Concerns;

use Illuminate\Support\Facades\DB;
use LogicException;

trait LoadsCurrentSupplierInvoiceWriteSnapshot
{
    use MapsCurrentSupplierInvoiceWriteSnapshotLines;

    /**
     * @return array{
     *   last_revision_no:int,
     *   snapshot:array<string, mixed>
     * }
     */
    private function loadCurrentInvoiceWriteSnapshot(string $supplierInvoiceId): array
    {
        $invoice = DB::table('supplier_invoices')
            ->where('id', $supplierInvoiceId)
            ->first([
                'id',
                'supplier_id',
                'supplier_nama_pt_pengirim_snapshot',
                'nomor_faktur',
                'nomor_faktur_normalized',
                'document_kind',
                'lifecycle_status',
                'origin_supplier_invoice_id',
                'superseded_by_supplier_invoice_id',
                'tanggal_pengiriman',
                'jatuh_tempo',
                'grand_total_rupiah',
                'last_revision_no',
            ]);

        if ($invoice === null) {
            throw new LogicException('Supplier invoice tidak ditemukan untuk proses update.');
        }

        $lines = DB::table('supplier_invoice_lines')
            ->where('supplier_invoice_id', $supplierInvoiceId)
            ->orderBy('line_no')
            ->get([
                'id',
                'line_no',
                'product_id',
                'product_kode_barang_snapshot',
                'product_nama_barang_snapshot',
                'product_merek_snapshot',
                'product_ukuran_snapshot',
                'qty_pcs',
                'line_total_rupiah',
                'unit_cost_rupiah',
                'line_subtotal_before_tax_rupiah',
                'tax_input',
                'tax_mode',
                'tax_rate_basis_points',
                'tax_amount_rupiah',
            ])
            ->map(fn (object $line): array => $this->currentInvoiceLineSnapshot($line))
            ->all();

        return [
            'last_revision_no' => (int) $invoice->last_revision_no,
            'snapshot' => [
                'id' => (string) $invoice->id,
                'nomor_faktur' => (string) $invoice->nomor_faktur,
                'nomor_faktur_normalized' => (string) $invoice->nomor_faktur_normalized,
                'supplier' => [
                    'id' => (string) $invoice->supplier_id,
                    'nama_pt_pengirim_snapshot' => (string) $invoice->supplier_nama_pt_pengirim_snapshot,
                ],
                'document_kind' => (string) $invoice->document_kind,
                'lifecycle_status' => (string) $invoice->lifecycle_status,
                'origin_supplier_invoice_id' => $invoice->origin_supplier_invoice_id !== null ? (string) $invoice->origin_supplier_invoice_id : null,
                'superseded_by_supplier_invoice_id' => $invoice->superseded_by_supplier_invoice_id !== null ? (string) $invoice->superseded_by_supplier_invoice_id : null,
                'tanggal_pengiriman' => (string) $invoice->tanggal_pengiriman,
                'jatuh_tempo' => (string) $invoice->jatuh_tempo,
                'grand_total_rupiah' => (int) $invoice->grand_total_rupiah,
                'lines' => $lines,
            ],
        ];
    }
}
