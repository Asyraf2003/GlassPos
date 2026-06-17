<?php

declare(strict_types=1);

namespace App\Core\Procurement\SupplierInvoice;

use App\Core\Support\Money;

trait SupplierInvoiceLineState
{
    private function __construct(
        private string $id,
        private int $lineNo,
        private string $productId,
        private ?string $productKodeBarangSnapshot,
        private string $productNamaBarangSnapshot,
        private string $productMerekSnapshot,
        private ?int $productUkuranSnapshot,
        private int $qtyPcs,
        private Money $lineTotalRupiah,
        private Money $unitCostRupiah,
        private Money $lineSubtotalBeforeTaxRupiah,
        private ?string $taxInput,
        private string $taxMode,
        private ?int $taxRateBasisPoints,
        private Money $taxAmountRupiah,
    ) {}

    public function id(): string { return $this->id; }
    public function lineNo(): int { return $this->lineNo; }
    public function productId(): string { return $this->productId; }
    public function productKodeBarangSnapshot(): ?string { return $this->productKodeBarangSnapshot; }
    public function productNamaBarangSnapshot(): string { return $this->productNamaBarangSnapshot; }
    public function productMerekSnapshot(): string { return $this->productMerekSnapshot; }
    public function productUkuranSnapshot(): ?int { return $this->productUkuranSnapshot; }
    public function qtyPcs(): int { return $this->qtyPcs; }
    public function lineTotalRupiah(): Money { return $this->lineTotalRupiah; }
    public function unitCostRupiah(): Money { return $this->unitCostRupiah; }
    public function lineSubtotalBeforeTaxRupiah(): Money { return $this->lineSubtotalBeforeTaxRupiah; }
    public function taxInput(): ?string { return $this->taxInput; }
    public function taxMode(): string { return $this->taxMode; }
    public function taxRateBasisPoints(): ?int { return $this->taxRateBasisPoints; }
    public function taxAmountRupiah(): Money { return $this->taxAmountRupiah; }

    private static function normalizeNullableString(?string $value): ?string
    {
        if ($value === null) return null;

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
