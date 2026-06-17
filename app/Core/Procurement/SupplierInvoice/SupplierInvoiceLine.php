<?php

declare(strict_types=1);

namespace App\Core\Procurement\SupplierInvoice;

use App\Core\Support\Money;

final class SupplierInvoiceLine
{
    use SupplierInvoiceLineState;
    use SupplierInvoiceLineValidation;

    public static function create(
        string $id, int $lineNo, string $productId, ?string $productKodeBarangSnapshot,
        string $productNamaBarangSnapshot, string $productMerekSnapshot, ?int $productUkuranSnapshot,
        int $qtyPcs, Money $lineTotalRupiah, ?Money $lineSubtotalBeforeTaxRupiah = null,
        ?string $taxInput = null, string $taxMode = SupplierInvoiceTaxSummary::MODE_NONE,
        ?int $taxRateBasisPoints = null, ?Money $taxAmountRupiah = null
    ): self {
        $lineSubtotalBeforeTaxRupiah ??= $lineTotalRupiah;
        $taxInput = self::normalizeNullableString($taxInput);
        $taxMode = trim($taxMode);
        $taxAmountRupiah ??= Money::fromInt(0);

        self::assertValid(
            $id, $lineNo, $productId, $productNamaBarangSnapshot, $productMerekSnapshot,
            $qtyPcs, $lineTotalRupiah, $lineSubtotalBeforeTaxRupiah,
            $taxInput, $taxMode, $taxRateBasisPoints, $taxAmountRupiah
        );

        return new self(
            $id, $lineNo, $productId, self::normalizeNullableString($productKodeBarangSnapshot),
            $productNamaBarangSnapshot, $productMerekSnapshot, $productUkuranSnapshot,
            $qtyPcs, $lineTotalRupiah, Money::fromInt(intdiv($lineTotalRupiah->amount(), $qtyPcs)),
            $lineSubtotalBeforeTaxRupiah, $taxInput, $taxMode, $taxRateBasisPoints, $taxAmountRupiah
        );
    }

    public static function rehydrate(
        string $id, int $lineNo, string $productId, ?string $productKodeBarangSnapshot,
        string $productNamaBarangSnapshot, string $productMerekSnapshot, ?int $productUkuranSnapshot,
        int $qtyPcs, Money $lineTotalRupiah, Money $unitCostRupiah,
        ?Money $lineSubtotalBeforeTaxRupiah = null, ?string $taxInput = null,
        string $taxMode = SupplierInvoiceTaxSummary::MODE_NONE,
        ?int $taxRateBasisPoints = null, ?Money $taxAmountRupiah = null
    ): self {
        $lineSubtotalBeforeTaxRupiah ??= $lineTotalRupiah;
        $taxInput = self::normalizeNullableString($taxInput);
        $taxMode = trim($taxMode);
        $taxAmountRupiah ??= Money::fromInt(0);

        self::assertValid(
            $id, $lineNo, $productId, $productNamaBarangSnapshot, $productMerekSnapshot,
            $qtyPcs, $lineTotalRupiah, $lineSubtotalBeforeTaxRupiah,
            $taxInput, $taxMode, $taxRateBasisPoints, $taxAmountRupiah
        );

        return new self(
            $id, $lineNo, $productId, self::normalizeNullableString($productKodeBarangSnapshot),
            $productNamaBarangSnapshot, $productMerekSnapshot, $productUkuranSnapshot,
            $qtyPcs, $lineTotalRupiah, $unitCostRupiah, $lineSubtotalBeforeTaxRupiah,
            $taxInput, $taxMode, $taxRateBasisPoints, $taxAmountRupiah
        );
    }
}
