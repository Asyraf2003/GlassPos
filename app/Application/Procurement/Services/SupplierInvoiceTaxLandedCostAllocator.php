<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services;

use InvalidArgumentException;

final class SupplierInvoiceTaxLandedCostAllocator
{
    public function __construct(
        private readonly SupplierInvoiceTaxInputCalculator $calculator,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $lines
     */
    public function allocate(array $lines, null|string|int $taxInput): SupplierInvoiceTaxLandedCostAllocation
    {
        $subtotal = $this->subtotalBeforeTax($lines);
        $tax = $this->calculator->calculate($taxInput, $subtotal);

        if ($tax->taxAmountRupiah() === 0) {
            return new SupplierInvoiceTaxLandedCostAllocation($subtotal, $tax, $lines);
        }

        if ($subtotal <= 0) {
            throw new InvalidArgumentException('Subtotal supplier invoice harus lebih dari 0 untuk alokasi pajak.');
        }

        return new SupplierInvoiceTaxLandedCostAllocation(
            $subtotal,
            $tax,
            $this->allocateTaxToLines($lines, $subtotal, $tax->taxAmountRupiah()),
        );
    }

    /**
     * @param list<array<string, mixed>> $lines
     */
    private function subtotalBeforeTax(array $lines): int
    {
        $subtotal = 0;

        foreach ($lines as $line) {
            if (! is_array($line)) {
                throw new InvalidArgumentException('Line supplier invoice tidak valid.');
            }

            $lineTotal = (int) ($line['line_total_rupiah'] ?? 0);

            if ($lineTotal < 0) {
                throw new InvalidArgumentException('Total line supplier invoice tidak boleh negatif.');
            }

            $subtotal += $lineTotal;
        }

        return $subtotal;
    }

    /**
     * @param list<array<string, mixed>> $lines
     * @return list<array<string, mixed>>
     */
    private function allocateTaxToLines(array $lines, int $subtotal, int $taxAmount): array
    {
        $allocatedLines = [];
        $remainders = [];
        $allocatedTaxTotal = 0;

        foreach ($lines as $index => $line) {
            $lineTotal = (int) ($line['line_total_rupiah'] ?? 0);
            $numerator = $lineTotal * $taxAmount;
            $allocatedTax = intdiv($numerator, $subtotal);

            $allocatedLines[$index] = [
                ...$line,
                'line_total_rupiah' => $lineTotal + $allocatedTax,
            ];

            $allocatedTaxTotal += $allocatedTax;

            $remainders[] = [
                'index' => $index,
                'remainder' => $numerator % $subtotal,
            ];
        }

        $remainingTax = $taxAmount - $allocatedTaxTotal;

        usort($remainders, static function (array $left, array $right): int {
            $byRemainder = $right['remainder'] <=> $left['remainder'];

            return $byRemainder !== 0 ? $byRemainder : ($left['index'] <=> $right['index']);
        });

        for ($i = 0; $i < $remainingTax; $i++) {
            $targetIndex = (int) $remainders[$i]['index'];
            $allocatedLines[$targetIndex]['line_total_rupiah'] = (int) $allocatedLines[$targetIndex]['line_total_rupiah'] + 1;
        }

        ksort($allocatedLines);

        $allocatedLines = array_values($allocatedLines);
        $this->assertAllocatedLinesPreserveUnitCostInvariant($allocatedLines);

        return $allocatedLines;
    }

    /**
     * @param list<array<string, mixed>> $lines
     */
    private function assertAllocatedLinesPreserveUnitCostInvariant(array $lines): void
    {
        foreach ($lines as $line) {
            $qtyPcs = (int) ($line['qty_pcs'] ?? 0);
            $lineTotal = (int) ($line['line_total_rupiah'] ?? 0);

            if ($qtyPcs < 1) {
                throw new InvalidArgumentException('Qty supplier invoice harus lebih dari 0 untuk alokasi pajak.');
            }

            if ($lineTotal % $qtyPcs !== 0) {
                throw new InvalidArgumentException('Alokasi pajak supplier invoice membuat total line tidak habis dibagi qty.');
            }
        }
    }
}
