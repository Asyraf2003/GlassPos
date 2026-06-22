<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services;

use InvalidArgumentException;

final class SupplierInvoiceTaxLineAllocator
{
    private const ROUNDING_CONFIRMATION_MESSAGE =
        'Total setelah pajak tidak habis dibagi qty, sehingga modal per pcs akan dibulatkan dan selisih pembulatan akan dicatat. Lanjutkan?';

    /**
     * @param list<array<string, mixed>> $lines
     * @return list<array<string, mixed>>
     */
    public function allocate(
        array $lines,
        int $subtotal,
        int $taxAmount,
        bool $roundingResidueConfirmed = false
    ): array {
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
            $remainders[] = ['index' => $index, 'remainder' => $numerator % $subtotal];
        }

        $this->allocateRemainingTax($allocatedLines, $remainders, $taxAmount - $allocatedTaxTotal);
        ksort($allocatedLines);

        return array_map(
            fn (array $line): array => $this->withUnitCostRoundingMetadata($line, $roundingResidueConfirmed),
            array_values($allocatedLines)
        );
    }

    /**
     * @param array<int, array<string, mixed>> $allocatedLines
     * @param list<array{index:int|string,remainder:int}> $remainders
     */
    private function allocateRemainingTax(array &$allocatedLines, array $remainders, int $remainingTax): void
    {
        usort($remainders, static function (array $left, array $right): int {
            $byRemainder = $right['remainder'] <=> $left['remainder'];

            return $byRemainder !== 0 ? $byRemainder : ($left['index'] <=> $right['index']);
        });

        for ($i = 0; $i < $remainingTax; $i++) {
            $targetIndex = (int) $remainders[$i]['index'];
            $allocatedLines[$targetIndex]['line_total_rupiah'] =
                (int) $allocatedLines[$targetIndex]['line_total_rupiah'] + 1;
        }
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
