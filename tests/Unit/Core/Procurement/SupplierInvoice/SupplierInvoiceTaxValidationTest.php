<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Procurement\SupplierInvoice;

use App\Core\Procurement\SupplierInvoice\SupplierInvoice;
use App\Core\Procurement\SupplierInvoice\SupplierInvoiceLine;
use App\Core\Procurement\SupplierInvoice\SupplierInvoiceTaxSummary;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use DateTimeImmutable;
use Tests\TestCase;

final class SupplierInvoiceTaxValidationTest extends TestCase
{
    public function test_invoice_accepts_line_tax_when_grand_total_matches_subtotal_plus_tax(): void
    {
        $line = SupplierInvoiceLine::create(
            'line-tax-1',
            1,
            'product-tax-1',
            'KB-TAX-001',
            'Ban Tax',
            'Federal',
            100,
            1,
            Money::fromInt(111000),
            Money::fromInt(100000),
            '11%',
            SupplierInvoiceTaxSummary::MODE_PERCENT,
            1100,
            Money::fromInt(11000),
        );

        $taxSummary = SupplierInvoiceTaxSummary::rehydrate(
            100000,
            '11%',
            SupplierInvoiceTaxSummary::MODE_PERCENT,
            1100,
            11000,
        );

        $invoice = SupplierInvoice::create(
            'invoice-tax-1',
            'supplier-tax-1',
            'PT Supplier Tax',
            'INV-TAX-001',
            new DateTimeImmutable('2026-06-19'),
            [$line],
            $taxSummary,
        );

        self::assertSame(111000, $invoice->grandTotalRupiah()->amount());
        self::assertSame(100000, $invoice->subtotalBeforeTaxRupiah()->amount());
        self::assertSame(11000, $invoice->taxAmountRupiah()->amount());
    }

    public function test_invoice_rejects_tax_summary_when_line_tax_total_does_not_match(): void
    {
        $line = SupplierInvoiceLine::create(
            'line-tax-mismatch-1',
            1,
            'product-tax-mismatch-1',
            'KB-TAX-002',
            'Ban Tax Mismatch',
            'Federal',
            100,
            1,
            Money::fromInt(111000),
            Money::fromInt(100000),
            '11%',
            SupplierInvoiceTaxSummary::MODE_PERCENT,
            1100,
            Money::fromInt(11000),
        );

        $taxSummary = SupplierInvoiceTaxSummary::rehydrate(
            100000,
            '10%',
            SupplierInvoiceTaxSummary::MODE_PERCENT,
            1000,
            10000,
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Grand total supplier invoice tidak cocok dengan subtotal dan pajak.');

        SupplierInvoice::create(
            'invoice-tax-mismatch-1',
            'supplier-tax-mismatch-1',
            'PT Supplier Tax Mismatch',
            'INV-TAX-002',
            new DateTimeImmutable('2026-06-19'),
            [$line],
            $taxSummary,
        );
    }
}
