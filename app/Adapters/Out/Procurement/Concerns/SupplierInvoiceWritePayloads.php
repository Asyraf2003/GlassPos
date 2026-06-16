<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement\Concerns;

use App\Core\Procurement\SupplierInvoice\SupplierInvoice;
use App\Core\Procurement\SupplierInvoice\SupplierInvoiceLine;

trait SupplierInvoiceWritePayloads
{
    private function toInvoiceRecord(SupplierInvoice $supplierInvoice, int $revisionNo): array
    {
        return [
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
            'voided_at' => null,
            'void_reason' => null,
            'last_revision_no' => $revisionNo,
        ];
    }

    private function toLineRecords(SupplierInvoice $supplierInvoice, int $revisionNo): array
    {
        return array_map(
            static fn (SupplierInvoiceLine $line): array => [
                'id' => $line->id(),
                'supplier_invoice_id' => $supplierInvoice->id(),
                'revision_no' => $revisionNo,
                'is_current' => true,
                'source_line_id' => null,
                'superseded_by_line_id' => null,
                'superseded_at' => null,
                'line_no' => $line->lineNo(),
                'product_id' => $line->productId(),
                'product_kode_barang_snapshot' => $line->productKodeBarangSnapshot(),
                'product_nama_barang_snapshot' => $line->productNamaBarangSnapshot(),
                'product_merek_snapshot' => $line->productMerekSnapshot(),
                'product_ukuran_snapshot' => $line->productUkuranSnapshot(),
                'qty_pcs' => $line->qtyPcs(),
                'line_total_rupiah' => $line->lineTotalRupiah()->amount(),
                'unit_cost_rupiah' => $line->unitCostRupiah()->amount(),
            ],
            $supplierInvoice->lines(),
        );
    }

    private function toVersionSnapshot(SupplierInvoice $supplierInvoice): array
    {
        return [
            'id' => $supplierInvoice->id(),
            'nomor_faktur' => $supplierInvoice->nomorFaktur(),
            'nomor_faktur_normalized' => $supplierInvoice->nomorFakturNormalized(),
            'supplier' => [
                'id' => $supplierInvoice->supplierId(),
                'nama_pt_pengirim_snapshot' => $supplierInvoice->supplierNamaPtPengirimSnapshot(),
            ],
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
            'lines' => array_map(
                static fn (SupplierInvoiceLine $line): array => [
                    'id' => $line->id(),
                    'line_no' => $line->lineNo(),
                    'product_id' => $line->productId(),
                    'product_kode_barang_snapshot' => $line->productKodeBarangSnapshot(),
                    'product_nama_barang_snapshot' => $line->productNamaBarangSnapshot(),
                    'product_merek_snapshot' => $line->productMerekSnapshot(),
                    'product_ukuran_snapshot' => $line->productUkuranSnapshot(),
                    'qty_pcs' => $line->qtyPcs(),
                    'line_total_rupiah' => $line->lineTotalRupiah()->amount(),
                    'unit_cost_rupiah' => $line->unitCostRupiah()->amount(),
                ],
                $supplierInvoice->lines(),
            ),
        ];
    }
}
