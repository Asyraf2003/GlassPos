<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Procurement\Concerns;

trait BuildsProcurementInvoiceDetailLinesView
{
    /**
     * @param array<int, mixed> $lines
     * @return array<int, array<string, string|int|null>>
     */
    private function buildLinesView(array $lines): array
    {
        $lineViews = [];

        foreach ($lines as $line) {
            if (! is_array($line)) {
                continue;
            }

            $lineViews[] = [
                'supplier_invoice_line_id' => isset($line['id']) ? (string) $line['id'] : null,
                'product_id' => isset($line['product_id']) ? (string) $line['product_id'] : null,
                'kode_barang' => isset($line['kode_barang']) ? (string) $line['kode_barang'] : null,
                'nama_barang' => (string) ($line['nama_barang'] ?? ''),
                'merek' => (string) ($line['merek'] ?? ''),
                'ukuran' => isset($line['ukuran']) ? (int) $line['ukuran'] : null,
                'qty_pcs' => (int) ($line['qty_pcs'] ?? 0),
                'unit_cost_rupiah' => (int) ($line['unit_cost_rupiah'] ?? 0),
                'line_total_rupiah' => (int) ($line['line_total_rupiah'] ?? 0),
                'unit_cost_label' => $this->formatRupiah((int) ($line['unit_cost_rupiah'] ?? 0)),
                'line_total_label' => $this->formatRupiah((int) ($line['line_total_rupiah'] ?? 0)),
            ];
        }

        return $lineViews;
    }

    private function lineSubtotalBeforeTaxRupiah(array $line): int
    {
        $subtotal = (int) ($line['line_subtotal_before_tax_rupiah'] ?? 0);

        return $subtotal > 0 ? $subtotal : (int) ($line['line_total_rupiah'] ?? 0);
    }
}

