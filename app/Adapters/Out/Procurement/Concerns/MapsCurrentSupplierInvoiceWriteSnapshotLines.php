<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement\Concerns;

trait MapsCurrentSupplierInvoiceWriteSnapshotLines
{
    /** @return array<string, int|string|null> */
    private function currentInvoiceLineSnapshot(object $line): array
    {
        return [
            'id' => (string) $line->id,
            'line_no' => (int) $line->line_no,
            'product_id' => (string) $line->product_id,
            'qty_pcs' => (int) $line->qty_pcs,
            'line_total_rupiah' => (int) $line->line_total_rupiah,
            'unit_cost_rupiah' => (int) $line->unit_cost_rupiah,
            'line_subtotal_before_tax_rupiah' => (int) ($line->line_subtotal_before_tax_rupiah ?? 0),
            'tax_input' => $line->tax_input !== null ? (string) $line->tax_input : null,
            'tax_mode' => (string) ($line->tax_mode ?? 'none'),
            'tax_rate_basis_points' => $this->nullableInt($line->tax_rate_basis_points ?? null),
            'tax_amount_rupiah' => (int) ($line->tax_amount_rupiah ?? 0),
        ];
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value !== null ? (int) $value : null;
    }
}
