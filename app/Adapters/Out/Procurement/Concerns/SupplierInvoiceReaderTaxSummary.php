<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement\Concerns;

use App\Core\Procurement\SupplierInvoice\SupplierInvoiceLine;
use App\Core\Procurement\SupplierInvoice\SupplierInvoiceTaxSummary;

trait SupplierInvoiceReaderTaxSummary
{
    /**
     * @param list<SupplierInvoiceLine> $lines
     */
    private function taxSummary(object $invoiceRow, array $lines): SupplierInvoiceTaxSummary
    {
        $taxInput = $invoiceRow->tax_input !== null ? trim((string) $invoiceRow->tax_input) : null;
        $taxInput = $taxInput === '' ? null : $taxInput;
        $taxMode = trim((string) ($invoiceRow->tax_mode ?? SupplierInvoiceTaxSummary::MODE_NONE));
        $taxRateBasisPoints = $invoiceRow->tax_rate_basis_points !== null
            ? (int) $invoiceRow->tax_rate_basis_points
            : null;
        $taxAmountRupiah = (int) ($invoiceRow->tax_amount_rupiah ?? 0);

        if ($this->isNoTaxSummary($taxInput, $taxMode, $taxRateBasisPoints, $taxAmountRupiah)) {
            return SupplierInvoiceTaxSummary::none($this->subtotalBeforeTaxRupiah($invoiceRow, $lines));
        }

        return SupplierInvoiceTaxSummary::rehydrate(
            (int) ($invoiceRow->subtotal_before_tax_rupiah ?? 0),
            $taxInput,
            $taxMode,
            $taxRateBasisPoints,
            $taxAmountRupiah,
        );
    }

    private function isNoTaxSummary(?string $taxInput, string $taxMode, ?int $rate, int $amount): bool
    {
        return $taxInput === null
            && ($taxMode === '' || $taxMode === SupplierInvoiceTaxSummary::MODE_NONE)
            && $rate === null
            && $amount === 0;
    }

    /**
     * @param list<SupplierInvoiceLine> $lines
     */
    private function subtotalBeforeTaxRupiah(object $invoiceRow, array $lines): int
    {
        $storedSubtotalBeforeTax = (int) ($invoiceRow->subtotal_before_tax_rupiah ?? 0);

        if ($storedSubtotalBeforeTax > 0) {
            return $storedSubtotalBeforeTax;
        }

        if ($this->sumLineTaxRupiah($lines) > 0) {
            return $this->sumLineSubtotalBeforeTaxRupiah($lines);
        }

        return $this->sumLineTotalRupiah($lines);
    }

    /**
     * @param list<SupplierInvoiceLine> $lines
     */
    private function sumLineSubtotalBeforeTaxRupiah(array $lines): int
    {
        $total = 0;

        foreach ($lines as $line) {
            $total += $line->lineSubtotalBeforeTaxRupiah()->amount();
        }

        return $total;
    }

    /**
     * @param list<SupplierInvoiceLine> $lines
     */
    private function sumLineTaxRupiah(array $lines): int
    {
        $total = 0;

        foreach ($lines as $line) {
            $total += $line->taxAmountRupiah()->amount();
        }

        return $total;
    }

    /**
     * @param list<SupplierInvoiceLine> $lines
     */
    private function sumLineTotalRupiah(array $lines): int
    {
        $total = 0;

        foreach ($lines as $line) {
            $total += $line->lineTotalRupiah()->amount();
        }

        return $total;
    }

        return $total;
    }
}
