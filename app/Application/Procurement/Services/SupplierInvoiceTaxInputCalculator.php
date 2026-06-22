<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services;

use InvalidArgumentException;

final class SupplierInvoiceTaxInputCalculator
{
    public function calculate(null|string|int $input, int $baseRupiah): SupplierInvoiceTaxInputCalculation
    {
        if ($baseRupiah < 0) {
            throw new InvalidArgumentException('Base pajak supplier invoice tidak boleh negatif.');
        }

        $normalizedInput = $this->normalizeInput($input);

        if ($normalizedInput === null) {
            return new SupplierInvoiceTaxInputCalculation(
                taxInput: null,
                taxMode: SupplierInvoiceTaxInputCalculation::MODE_NONE,
                taxRateBasisPoints: null,
                taxAmountRupiah: 0,
            );
        }

        if (str_ends_with($normalizedInput, '%')) {
            $basisPoints = $this->parsePercentBasisPoints(substr($normalizedInput, 0, -1));
            $amount = $this->roundHalfUpDivisor($baseRupiah * $basisPoints, 10_000);

            return new SupplierInvoiceTaxInputCalculation(
                taxInput: $normalizedInput,
                taxMode: SupplierInvoiceTaxInputCalculation::MODE_PERCENT,
                taxRateBasisPoints: $basisPoints,
                taxAmountRupiah: $amount,
            );
        }

        return new SupplierInvoiceTaxInputCalculation(
            taxInput: $normalizedInput,
            taxMode: SupplierInvoiceTaxInputCalculation::MODE_FIXED,
            taxRateBasisPoints: null,
            taxAmountRupiah: $this->parseFixedRupiah($normalizedInput),
        );
    }

    private function normalizeInput(null|string|int $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $value = trim(str_replace(["\u{00A0}", "\t", "\n", "\r"], ' ', (string) $input));
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return $value === '' ? null : $value;
    }

    private function parsePercentBasisPoints(string $value): int
    {
        $normalized = trim(str_replace([' ', ','], ['', '.'], $value));

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new InvalidArgumentException('Format persen pajak supplier invoice tidak valid.');
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');
        $fraction = str_pad($fraction, 2, '0');

        return ((int) $whole * 100) + (int) $fraction;
    }

    private function parseFixedRupiah(string $value): int
    {
        $normalized = trim(str_replace(["\u{00A0}", "\t", "\n", "\r"], ' ', $value));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';
        $normalized = preg_replace('/^rp\.?\s*/i', '', $normalized) ?? '';
        $normalized = trim($normalized);

        if ($normalized === '' || str_contains($normalized, '-')) {
            throw new InvalidArgumentException('Format nominal pajak supplier invoice tidak valid.');
        }

        if (preg_match('/^\d+$/', $normalized)) {
            return (int) $normalized;
        }

        if (preg_match('/^\d{1,3}(?:[., ]\d{3})+$/', $normalized)) {
            return (int) preg_replace('/[., ]+/', '', $normalized);
        }

        throw new InvalidArgumentException('Format nominal pajak supplier invoice tidak valid.');
    }

    private function roundHalfUpDivisor(int $amount, int $divisor): int
    {
        return intdiv($amount + intdiv($divisor, 2), $divisor);
    }
}
