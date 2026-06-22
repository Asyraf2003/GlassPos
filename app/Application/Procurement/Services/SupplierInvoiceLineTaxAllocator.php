<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services;

use InvalidArgumentException;

final class SupplierInvoiceLineTaxAllocator
{
    private const ROUNDING_CONFIRMATION_MESSAGE =
        'Total setelah pajak tidak habis dibagi qty, sehingga modal per pcs akan dibulatkan dan selisih pembulatan akan dicatat. Lanjutkan?';

    public function __construct(
        private readonly SupplierInvoiceTaxInputCalculator $calculator = new SupplierInvoiceTaxInputCalculator(),
    ) {}

    /**
     * @param array<int, array<string, mixed>> $lines
     * @return array<int, array<string, mixed>>
     */
    public function allocate(array $lines, bool $roundingResidueConfirmed = false): array
    {
        return array_map(
            fn (array $line): array => $this->taxedLine($line, $roundingResidueConfirmed),
            $lines
        );
    }

    /**
     * @param array<string, mixed> $line
     * @return array<string, mixed>
     */
    private function taxedLine(array $line, bool $roundingResidueConfirmed): array
    {
        $lineTotal = (int) ($line['line_total_rupiah'] ?? 0);
        $tax = $this->calculator->calculate($line['tax_input'] ?? null, $lineTotal);

        return $this->withUnitCostRoundingMetadata(array_merge($line, [
            'line_subtotal_before_tax_rupiah' => $lineTotal,
        ], $tax->toArray(), [
            'line_total_rupiah' => $lineTotal + $tax->taxAmountRupiah(),
        ]), $roundingResidueConfirmed);
    }

    /**
     * @param array<string, mixed> $line
     * @return array<string, mixed>
     */
    private function withUnitCostRoundingMetadata(array $line, bool $roundingResidueConfirmed): array
    {
        $qtyPcs = (int) ($line['qty_pcs'] ?? 0);
        $lineTotal = (int) ($line['line_total_rupiah'] ?? 0);

        if ($qtyPcs < 1) {
            throw new InvalidArgumentException('Qty supplier invoice harus lebih dari 0 untuk alokasi pajak.');
        }

        $roundingResidue = $lineTotal % $qtyPcs;

        if ($roundingResidue !== 0 && ! $roundingResidueConfirmed) {
            throw new InvalidArgumentException(self::ROUNDING_CONFIRMATION_MESSAGE);
        }

        return array_merge($line, [
            'unit_cost_rupiah' => intdiv($lineTotal, $qtyPcs),
            'rounding_residue_rupiah' => $roundingResidue,
        ]);
    }
}
