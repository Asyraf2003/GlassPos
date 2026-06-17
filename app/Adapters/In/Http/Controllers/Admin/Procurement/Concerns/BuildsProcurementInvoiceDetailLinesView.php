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

            $lineSubtotalBeforeTax = $this->lineSubtotalBeforeTaxRupiah($line);
            $lineTaxInput = isset($line['tax_input']) ? trim((string) $line['tax_input']) : '';
            $lineTaxAmount = (int) ($line['tax_amount_rupiah'] ?? 0);

            $lineSubtotalBeforeTax = $this->lineSubtotalBeforeTaxRupiah($line);
            $lineTaxInput = isset($line['tax_input']) ? trim((string) $line['tax_input']) : '';
            $lineTaxAmount = (int) ($line['tax_amount_rupiah'] ?? 0);

            $lineSubtotalBeforeTax = $this->lineSubtotalBeforeTaxRupiah($line);
            $lineTaxInput = isset($line['tax_input']) ? trim((string) $line['tax_input']) : '';
            $lineTaxAmount = (int) ($line['tax_amount_rupiah'] ?? 0);

            $lineSubtotalBeforeTax = $this->lineSubtotalBeforeTaxRupiah($line);
            $lineTaxInput = isset($line['tax_input']) ? trim((string) $line['tax_input']) : '';
            $lineTaxAmount = (int) ($line['tax_amount_rupiah'] ?? 0);

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
                'line_subtotal_before_tax_rupiah' => $lineSubtotalBeforeTax,
                'line_subtotal_before_tax_label' => $this->formatRupiah($lineSubtotalBeforeTax),
                'tax_input' => $lineTaxInput !== '' ? $lineTaxInput : null,
                'tax_amount_rupiah' => $lineTaxAmount,
                'tax_amount_label' => $this->formatRupiah($lineTaxAmount),
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

