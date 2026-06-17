<?php

declare(strict_types=1);

namespace App\Core\Procurement\SupplierInvoice;

use App\Core\Support\Money;
use DomainException;

trait SupplierInvoiceLineValidation
{
    private static function assertValid(
        string $id,
        int $lineNo,
        string $productId,
        string $productNamaBarangSnapshot,
        string $productMerekSnapshot,
        int $qtyPcs,
        Money $lineTotalRupiah,
        Money $lineSubtotalBeforeTaxRupiah,
        ?string $taxInput,
        string $taxMode,
        ?int $taxRateBasisPoints,
        Money $taxAmountRupiah
    ): void {
        if (trim($id) === '') throw new DomainException('ID line faktur supplier wajib diisi.');
        if ($lineNo < 1) throw new DomainException('Nomor line wajib lebih dari 0.');
        if (trim($productId) === '') throw new DomainException('Produk pada line wajib diisi.');
        if (trim($productNamaBarangSnapshot) === '') throw new DomainException('Nama produk snapshot wajib diisi.');
        if (trim($productMerekSnapshot) === '') throw new DomainException('Merek produk snapshot wajib diisi.');
        if ($qtyPcs < 1) throw new DomainException('Qty wajib lebih dari 0.');
        if ($lineTotalRupiah->amount() < 1) throw new DomainException('Total line wajib lebih dari 0.');
        if ($lineSubtotalBeforeTaxRupiah->amount() < 1) throw new DomainException('Subtotal line sebelum pajak wajib lebih dari 0.');
        if ($lineTotalRupiah->amount() % $qtyPcs !== 0) {
            throw new DomainException('Total line harus habis dibagi qty agar unit cost presisi.');
        }

        self::assertValidTaxMetadata($taxInput, $taxMode, $taxRateBasisPoints, $taxAmountRupiah);
    }

    private static function assertValidTaxMetadata(
        ?string $taxInput,
        string $taxMode,
        ?int $taxRateBasisPoints,
        Money $taxAmountRupiah
    ): void {
        if (! in_array($taxMode, [
            SupplierInvoiceTaxSummary::MODE_NONE,
            SupplierInvoiceTaxSummary::MODE_PERCENT,
            SupplierInvoiceTaxSummary::MODE_FIXED,
        ], true)) throw new DomainException('Mode pajak line faktur supplier tidak valid.');

        if ($taxAmountRupiah->amount() < 0) {
            throw new DomainException('Nominal pajak line faktur supplier tidak boleh negatif.');
        }

        if ($taxMode === SupplierInvoiceTaxSummary::MODE_NONE) {
            if ($taxInput !== null || $taxRateBasisPoints !== null || $taxAmountRupiah->amount() !== 0) {
                throw new DomainException('Pajak line none tidak boleh memiliki nilai pajak.');
            }

            return;
        }

        if ($taxInput === null) throw new DomainException('Input pajak line faktur supplier wajib diisi.');

        if ($taxMode === SupplierInvoiceTaxSummary::MODE_PERCENT && ($taxRateBasisPoints === null || $taxRateBasisPoints < 0)) {
            throw new DomainException('Rate pajak line persen wajib valid.');
        }

        if ($taxMode === SupplierInvoiceTaxSummary::MODE_FIXED && $taxRateBasisPoints !== null) {
            throw new DomainException('Pajak line nominal tidak boleh memiliki rate persen.');
        }
    }
}
