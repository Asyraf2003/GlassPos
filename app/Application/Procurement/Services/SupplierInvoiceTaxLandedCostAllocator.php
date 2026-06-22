<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services;

use InvalidArgumentException;

final class SupplierInvoiceTaxLandedCostAllocator
{
    public function __construct(
        private readonly SupplierInvoiceTaxInputCalculator $calculator,
        private readonly ?SupplierInvoiceTaxLineAllocator $lineAllocator = null,
        private readonly ?SupplierInvoiceLineTaxAllocator $lineTaxAllocator = null,
    ) {}

    /**
     * @param array<int, array<string, mixed>> $lines
     */
    public function allocate(array $lines, null|string|int $taxInput, bool $roundingResidueConfirmed = false): SupplierInvoiceTaxLandedCostAllocation
    {
        $baseSubtotal = $this->subtotal($lines);
        $lineTaxedLines = $this->lineTaxAllocator()->allocate($lines, $roundingResidueConfirmed);
        $subtotalAfterLineTax = $this->subtotal($lineTaxedLines);
        $tax = $this->calculator->calculate($taxInput, $subtotalAfterLineTax);

        if ($tax->taxAmountRupiah() <= 0) {
            $resolvedLines = $this->shouldKeepOriginalLines($lineTaxedLines) ? $lines : $lineTaxedLines;

            return new SupplierInvoiceTaxLandedCostAllocation($baseSubtotal, $tax, $resolvedLines);
        }

        if ($subtotalAfterLineTax <= 0) {
            throw new InvalidArgumentException('Subtotal supplier invoice wajib lebih dari 0 untuk pajak.');
        }

        return new SupplierInvoiceTaxLandedCostAllocation(
            $baseSubtotal,
            $tax,
            $this->taxLineAllocator()->allocate($lineTaxedLines, $subtotalAfterLineTax, $tax->taxAmountRupiah(), $roundingResidueConfirmed)
        );
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     */
    private function subtotal(array $lines): int
    {
        return array_sum(array_map(
            static fn (array $line): int => (int) ($line['line_total_rupiah'] ?? 0),
            $lines
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     */
    private function shouldKeepOriginalLines(array $lines): bool
    {
        foreach ($lines as $line) {
            if (($line['tax_input'] ?? null) !== null || (int) ($line['tax_amount_rupiah'] ?? 0) !== 0) {
                return false;
            }
        }

        return true;
    }

    private function taxLineAllocator(): SupplierInvoiceTaxLineAllocator
    {
        return $this->lineAllocator ?? new SupplierInvoiceTaxLineAllocator();
    }

    private function lineTaxAllocator(): SupplierInvoiceLineTaxAllocator
    {
        return $this->lineTaxAllocator ?? new SupplierInvoiceLineTaxAllocator($this->calculator);
    }
}
