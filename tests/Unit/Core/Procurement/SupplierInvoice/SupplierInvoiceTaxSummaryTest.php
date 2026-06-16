<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Procurement\SupplierInvoice;

use App\Core\Procurement\SupplierInvoice\SupplierInvoiceTaxSummary;
use App\Core\Shared\Exceptions\DomainException;
use Tests\TestCase;

final class SupplierInvoiceTaxSummaryTest extends TestCase
{
    public function test_none_tax_summary_uses_subtotal_as_grand_total(): void
    {
        $summary = SupplierInvoiceTaxSummary::none(50000);

        self::assertSame(50000, $summary->subtotalBeforeTaxRupiah()->amount());
        self::assertSame(null, $summary->taxInput());
        self::assertSame(SupplierInvoiceTaxSummary::MODE_NONE, $summary->taxMode());
        self::assertSame(null, $summary->taxRateBasisPoints());
        self::assertSame(0, $summary->taxAmountRupiah()->amount());
        self::assertSame(50000, $summary->grandTotalAfterTaxRupiah()->amount());
    }

    public function test_percent_tax_summary_rehydrates_source_tax_metadata(): void
    {
        $summary = SupplierInvoiceTaxSummary::rehydrate(
            100000,
            '11%',
            SupplierInvoiceTaxSummary::MODE_PERCENT,
            1100,
            11000,
        );

        self::assertSame(100000, $summary->subtotalBeforeTaxRupiah()->amount());
        self::assertSame('11%', $summary->taxInput());
        self::assertSame(SupplierInvoiceTaxSummary::MODE_PERCENT, $summary->taxMode());
        self::assertSame(1100, $summary->taxRateBasisPoints());
        self::assertSame(11000, $summary->taxAmountRupiah()->amount());
        self::assertSame(111000, $summary->grandTotalAfterTaxRupiah()->amount());
    }

    public function test_none_mode_rejects_non_empty_tax_amount(): void
    {
        $this->expectException(DomainException::class);

        SupplierInvoiceTaxSummary::rehydrate(
            100000,
            null,
            SupplierInvoiceTaxSummary::MODE_NONE,
            null,
            1,
        );
    }
}
