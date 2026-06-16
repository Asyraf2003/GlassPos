<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use InvalidArgumentException;

final class TaxInputCalculator
{
    public function calculate(null|string|int $input, int $baseRupiah): TaxInputCalculation
    {
        if ($baseRupiah < 0) {
            throw new InvalidArgumentException('Base pajak tidak boleh negatif.');
        }

        $normalizedInput = $this->normalizeInput($input);

        if ($normalizedInput === null) {
            return new TaxInputCalculation(
                taxInput: null,
                taxMode: TaxInputCalculation::MODE_NONE,
                taxRateBasisPoints: null,
                taxAmountRupiah: 0,
            );
        }

        if (str_ends_with($normalizedInput, '%')) {
            $basisPoints = $this->parsePercentBasisPoints(substr($normalizedInput, 0, -1));
            $amount = $this->roundHalfUpDivisor($baseRupiah * $basisPoints, 10_000);

            return new TaxInputCalculation(
                taxInput: $normalizedInput,
                taxMode: TaxInputCalculation::MODE_PERCENT,
                taxRateBasisPoints: $basisPoints,
                taxAmountRupiah: $amount,
            );
        }

        $fixedAmount = $this->parseFixedRupiah($normalizedInput);

        return new TaxInputCalculation(
            taxInput: $normalizedInput,
            taxMode: TaxInputCalculation::MODE_FIXED,
            taxRateBasisPoints: null,
            taxAmountRupiah: $fixedAmount,
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
            throw new InvalidArgumentException('Format persen pajak tidak valid.');
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');
        $fraction = str_pad($fraction, 2, '0');

        return ((int) $whole * 100) + (int) $fraction;
    }

    private function parseFixedRupiah(string $value): int
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            throw new InvalidArgumentException('Format nominal pajak tidak valid.');
        }

        return (int) $digits;
    }

    private function roundHalfUpDivisor(int $amount, int $divisor): int
    {
        return intdiv($amount + intdiv($divisor, 2), $divisor);
    }
}
