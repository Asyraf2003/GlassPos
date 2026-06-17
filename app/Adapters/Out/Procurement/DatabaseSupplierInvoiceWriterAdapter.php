<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Core\Procurement\SupplierInvoice\SupplierInvoice;
use App\Core\Procurement\SupplierInvoice\SupplierInvoiceLine;
use App\Ports\Out\Procurement\SupplierInvoiceWriterPort;
use Illuminate\Support\Facades\DB;

final class DatabaseSupplierInvoiceWriterAdapter implements SupplierInvoiceWriterPort
{
    public function create(SupplierInvoice $supplierInvoice): void
    {
        DB::table('supplier_invoices')->insert($this->toInvoiceRecord($supplierInvoice, true));

        DB::table('supplier_invoice_lines')->insert($this->toLineRecords($supplierInvoice));
    }

    public function update(SupplierInvoice $supplierInvoice): void
    {
        DB::table('supplier_invoices')
            ->where('id', $supplierInvoice->id())
            ->update($this->toInvoiceRecord($supplierInvoice, false));

        DB::table('supplier_invoice_lines')
            ->where('supplier_invoice_id', $supplierInvoice->id())
            ->delete();

        DB::table('supplier_invoice_lines')->insert($this->toLineRecords($supplierInvoice));
    }

    /**
     * @return array<string, string|int|null>
     */
    private function toInvoiceRecord(SupplierInvoice $supplierInvoice, bool $includeCreatedAt): array
    {
        $timestamp = now()->toDateTimeString();

        $record = [
            'id' => $supplierInvoice->id(),
            'supplier_id' => $supplierInvoice->supplierId(),
            'supplier_nama_pt_pengirim_snapshot' => $supplierInvoice->supplierNamaPtPengirimSnapshot(),
            'nomor_faktur' => $supplierInvoice->nomorFaktur(),
            'nomor_faktur_normalized' => $supplierInvoice->nomorFakturNormalized(),
            'document_kind' => $supplierInvoice->documentKind(),
            'lifecycle_status' => $supplierInvoice->lifecycleStatus(),
            'origin_supplier_invoice_id' => $supplierInvoice->originSupplierInvoiceId(),
            'superseded_by_supplier_invoice_id' => $supplierInvoice->supersededBySupplierInvoiceId(),
            'tanggal_pengiriman' => $supplierInvoice->tanggalPengiriman()->format('Y-m-d'),
            'jatuh_tempo' => $supplierInvoice->jatuhTempo()->format('Y-m-d'),
            'subtotal_before_tax_rupiah' => $supplierInvoice->subtotalBeforeTaxRupiah()->amount(),
            'tax_input' => $supplierInvoice->taxInput(),
            'tax_mode' => $supplierInvoice->taxMode(),
            'tax_rate_basis_points' => $supplierInvoice->taxRateBasisPoints(),
            'tax_amount_rupiah' => $supplierInvoice->taxAmountRupiah()->amount(),
            'grand_total_rupiah' => $supplierInvoice->grandTotalRupiah()->amount(),
            'updated_at' => $timestamp,
        ];

        if ($includeCreatedAt) {
            $record['created_at'] = $timestamp;
        }

        return $record;
    }

    /**
     * @return list<array<string, string|int|null>>
     */
    private function toLineRecords(SupplierInvoice $supplierInvoice): array
    {
        return array_map(
            static fn (SupplierInvoiceLine $line): array => [
                'id' => $line->id(),
                'supplier_invoice_id' => $supplierInvoice->id(),
                'line_no' => $line->lineNo(),
                'product_id' => $line->productId(),
                'product_kode_barang_snapshot' => $line->productKodeBarangSnapshot(),
                'product_nama_barang_snapshot' => $line->productNamaBarangSnapshot(),
                'product_merek_snapshot' => $line->productMerekSnapshot(),
                'product_ukuran_snapshot' => $line->productUkuranSnapshot(),
                'qty_pcs' => $line->qtyPcs(),
                'line_total_rupiah' => $line->lineTotalRupiah()->amount(),
                'unit_cost_rupiah' => $line->unitCostRupiah()->amount(),
                'line_subtotal_before_tax_rupiah' => $line->lineSubtotalBeforeTaxRupiah()->amount(),
                'tax_input' => $line->taxInput(),
                'tax_mode' => $line->taxMode(),
                'tax_rate_basis_points' => $line->taxRateBasisPoints(),
                'tax_amount_rupiah' => $line->taxAmountRupiah()->amount(),
            ],
            $supplierInvoice->lines(),
        );
    }
}
