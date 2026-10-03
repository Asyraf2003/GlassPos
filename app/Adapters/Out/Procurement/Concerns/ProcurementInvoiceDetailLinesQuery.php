<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement\Concerns;

use Illuminate\Support\Facades\DB;

trait ProcurementInvoiceDetailLinesQuery
{
    /**
     * @return list<array{
     * id:string,
     * supplier_invoice_id:string,
     * product_id:string,
     * kode_barang:?string,
     * nama_barang:string,
     * merek:string,
     * ukuran:?int,
     * qty_pcs:int,
     * line_total_rupiah:int,
     * unit_cost_rupiah:int
     * }>
     */
    private function getLineRows(string $supplierInvoiceId): array
    {
        return DB::table('supplier_invoice_lines')
            ->where('supplier_invoice_lines.supplier_invoice_id', $supplierInvoiceId)
            ->where('supplier_invoice_lines.is_current', true)
            ->orderBy('supplier_invoice_lines.line_no')
            ->get([
                'supplier_invoice_lines.id',
                'supplier_invoice_lines.line_no',
                'supplier_invoice_lines.supplier_invoice_id',
                'supplier_invoice_lines.product_id',
                'supplier_invoice_lines.product_kode_barang_snapshot as kode_barang',
                'supplier_invoice_lines.product_nama_barang_snapshot as nama_barang',
                'supplier_invoice_lines.product_merek_snapshot as merek',
                'supplier_invoice_lines.product_ukuran_snapshot as ukuran',
                'supplier_invoice_lines.qty_pcs',
                'supplier_invoice_lines.line_total_rupiah',
                'supplier_invoice_lines.unit_cost_rupiah',
                'supplier_invoice_lines.line_subtotal_before_tax_rupiah',
                'supplier_invoice_lines.tax_input',
                'supplier_invoice_lines.tax_mode',
                'supplier_invoice_lines.tax_rate_basis_points',
                'supplier_invoice_lines.tax_amount_rupiah',
                'supplier_invoice_lines.rounding_residue_rupiah',
            ])
            ->map(static fn (object $row): array => [
                'id' => (string) $row->id,
                'line_no' => (int) $row->line_no,
                'supplier_invoice_id' => (string) $row->supplier_invoice_id,
                'product_id' => (string) $row->product_id,
                'kode_barang' => $row->kode_barang !== null ? (string) $row->kode_barang : null,
                'nama_barang' => (string) $row->nama_barang,
                'merek' => (string) $row->merek,
                'ukuran' => $row->ukuran !== null ? (int) $row->ukuran : null,
                'qty_pcs' => (int) $row->qty_pcs,
                'line_total_rupiah' => (int) $row->line_total_rupiah,
                'unit_cost_rupiah' => (int) $row->unit_cost_rupiah,
                'line_subtotal_before_tax_rupiah' => (int) ($row->line_subtotal_before_tax_rupiah ?? 0),
                'tax_input' => $row->tax_input !== null ? (string) $row->tax_input : null,
                'tax_mode' => (string) ($row->tax_mode ?? 'none'),
                'tax_rate_basis_points' => $row->tax_rate_basis_points !== null
                    ? (int) $row->tax_rate_basis_points
                    : null,
                'tax_amount_rupiah' => (int) ($row->tax_amount_rupiah ?? 0),
                'rounding_residue_rupiah' => (int) ($row->rounding_residue_rupiah ?? 0),
            ])
            ->all();
    }
}
