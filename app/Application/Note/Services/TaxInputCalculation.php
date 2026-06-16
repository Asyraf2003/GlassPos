<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

final class TaxInputCalculation
{
    public const MODE_NONE = 'none';
    public const MODE_PERCENT = 'percent';
    public const MODE_FIXED = 'fixed';

    public function __construct(
        private readonly ?string $taxInput,
        private readonly string $taxMode,
        private readonly ?int $taxRateBasisPoints,
        private readonly int $taxAmountRupiah,
    ) {
    }

    public function taxInput(): ?string
    {
        return $this->taxInput;
    }

    public function taxMode(): string
    {
        return $this->taxMode;
    }

    public function taxRateBasisPoints(): ?int
    {
        return $this->taxRateBasisPoints;
    }

    public function taxAmountRupiah(): int
    {
        return $this->taxAmountRupiah;
    }

    /** @return array{tax_input:?string,tax_mode:string,tax_rate_basis_points:?int,tax_amount_rupiah:int} */
    public function toArray(): array
    {
        return [
            'tax_input' => $this->taxInput,
            'tax_mode' => $this->taxMode,
            'tax_rate_basis_points' => $this->taxRateBasisPoints,
            'tax_amount_rupiah' => $this->taxAmountRupiah,
        ];
    }
}
