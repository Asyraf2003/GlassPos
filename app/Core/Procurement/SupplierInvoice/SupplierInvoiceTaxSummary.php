<?php

declare(strict_types=1);

namespace App\Core\Procurement\SupplierInvoice;

use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;

final class SupplierInvoiceTaxSummary
{
    public const MODE_NONE = 'none';
    public const MODE_PERCENT = 'percent';
    public const MODE_FIXED = 'fixed';

    private function __construct(
        private readonly Money $subtotalBeforeTaxRupiah,
        private readonly ?string $taxInput,
        private readonly string $taxMode,
        private readonly ?int $taxRateBasisPoints,
        private readonly Money $taxAmountRupiah,
    ) {
    }

    public static function none(int $subtotalBeforeTaxRupiah): self
    {
        if ($subtotalBeforeTaxRupiah < 0) {
            throw new DomainException('Subtotal sebelum pajak supplier invoice tidak boleh negatif.');
        }

        return new self(
            Money::fromInt($subtotalBeforeTaxRupiah),
            null,
            self::MODE_NONE,
            null,
            Money::zero(),
        );
    }

    public static function rehydrate(
        int $subtotalBeforeTaxRupiah,
        ?string $taxInput,
        string $taxMode,
        ?int $taxRateBasisPoints,
        int $taxAmountRupiah,
    ): self {
        $summary = new self(
            Money::fromInt($subtotalBeforeTaxRupiah),
            self::normalizeNullableString($taxInput),
            trim($taxMode),
            $taxRateBasisPoints,
            Money::fromInt($taxAmountRupiah),
        );

        $summary->assertValid();

        return $summary;
    }

    public function subtotalBeforeTaxRupiah(): Money
    {
        return $this->subtotalBeforeTaxRupiah;
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

    public function taxAmountRupiah(): Money
    {
        return $this->taxAmountRupiah;
    }

    public function grandTotalAfterTaxRupiah(): Money
    {
        return $this->subtotalBeforeTaxRupiah->add($this->taxAmountRupiah);
    }

    private function assertValid(): void
    {
        if ($this->subtotalBeforeTaxRupiah->amount() < 0) {
            throw new DomainException('Subtotal sebelum pajak supplier invoice tidak boleh negatif.');
        }

        if ($this->taxAmountRupiah->amount() < 0) {
            throw new DomainException('Nominal pajak supplier invoice tidak boleh negatif.');
        }

        if (! in_array($this->taxMode, [self::MODE_NONE, self::MODE_PERCENT, self::MODE_FIXED], true)) {
            throw new DomainException('Mode pajak supplier invoice tidak valid.');
        }

        if ($this->taxMode === self::MODE_NONE) {
            if ($this->taxInput !== null || $this->taxRateBasisPoints !== null || $this->taxAmountRupiah->amount() !== 0) {
                throw new DomainException('Pajak supplier invoice mode none harus kosong.');
            }

            return;
        }

        if ($this->taxInput === null) {
            throw new DomainException('Input pajak supplier invoice wajib ada.');
        }

        if ($this->taxMode === self::MODE_PERCENT && ($this->taxRateBasisPoints === null || $this->taxRateBasisPoints < 0)) {
            throw new DomainException('Basis points pajak supplier invoice tidak valid.');
        }

        if ($this->taxMode === self::MODE_FIXED && $this->taxRateBasisPoints !== null) {
            throw new DomainException('Pajak fixed supplier invoice tidak boleh punya basis points.');
        }
    }

    private static function normalizeNullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
