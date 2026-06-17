<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement\Concerns;

use App\Core\Procurement\SupplierInvoice\SupplierInvoiceLine;
use App\Core\Shared\ValueObjects\Money;
use Illuminate\Support\Facades\DB;

trait SupplierInvoiceReaderLines
{
    private function supplierInvoiceLines(string $supplierInvoiceId): array
    {
        $rows = DB::table('supplier_invoice_lines')
            ->where('supplier_invoice_id', $supplierInvoiceId)
            ->where('is_current', true)
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
            ]);

        $lines = [];

        foreach ($rows as $row) {
            $lines[] = SupplierInvoiceLine::rehydrate(
                (string) $row->id,
                (int) $row->line_no,
                (string) $row->product_id,
                $row->product_kode_barang_snapshot !== null ? (string) $row->product_kode_barang_snapshot : null,
                (string) $row->product_nama_barang_snapshot,
                (string) $row->product_merek_snapshot,
                $row->product_ukuran_snapshot !== null ? (int) $row->product_ukuran_snapshot : null,
                (int) $row->qty_pcs,
                Money::fromInt((int) $row->line_total_rupiah),
                Money::fromInt((int) $row->unit_cost_rupiah),
                Money::fromInt($this->lineSubtotalBeforeTaxRupiah($row)),
                $row->tax_input !== null ? (string) $row->tax_input : null,
                (string) ($row->tax_mode ?? 'none'),
                $row->tax_rate_basis_points !== null ? (int) $row->tax_rate_basis_points : null,
                Money::fromInt((int) ($row->tax_amount_rupiah ?? 0))
            );
        }

        return $lines;
    }

    private function lineSubtotalBeforeTaxRupiah(object $row): int
    {
        $subtotal = (int) ($row->line_subtotal_before_tax_rupiah ?? 0);

        return $subtotal > 0 ? $subtotal : (int) $row->line_total_rupiah;
    }

}
