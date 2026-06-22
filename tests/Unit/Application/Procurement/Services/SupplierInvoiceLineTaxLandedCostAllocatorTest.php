<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Procurement\Services;

use App\Application\Procurement\Services\SupplierInvoiceTaxInputCalculation;
use App\Application\Procurement\Services\SupplierInvoiceTaxInputCalculator;
use App\Application\Procurement\Services\SupplierInvoiceTaxLandedCostAllocator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SupplierInvoiceLineTaxLandedCostAllocatorTest extends TestCase
{
    public function test_line_percent_tax_is_applied_before_header_tax(): void
    {
        $allocation = $this->allocator()->allocate([
            ['line_no' => 1, 'product_id' => 'product-1', 'qty_pcs' => 1, 'line_total_rupiah' => 100_000, 'tax_input' => '11%'],
        ], null);

        $line = $allocation->lines()[0];

        self::assertSame(100_000, $allocation->subtotalBeforeTaxRupiah());
        self::assertSame(111_000, $allocation->grandTotalAfterTaxRupiah());
        self::assertSame(111_000, $line['line_total_rupiah']);
        self::assertSame(100_000, $line['line_subtotal_before_tax_rupiah']);
        self::assertSame(SupplierInvoiceTaxInputCalculation::MODE_PERCENT, $line['tax_mode']);
        self::assertSame(1_100, $line['tax_rate_basis_points']);
        self::assertSame(11_000, $line['tax_amount_rupiah']);
    }

    public function test_line_fixed_tax_is_applied_before_header_tax(): void
    {
        $allocation = $this->allocator()->allocate([
            ['line_no' => 1, 'product_id' => 'product-1', 'qty_pcs' => 1, 'line_total_rupiah' => 50_000, 'tax_input' => '5000'],
        ], null);

        $line = $allocation->lines()[0];

        self::assertSame(50_000, $allocation->subtotalBeforeTaxRupiah());
        self::assertSame(55_000, $allocation->grandTotalAfterTaxRupiah());
        self::assertSame(55_000, $line['line_total_rupiah']);
        self::assertSame(SupplierInvoiceTaxInputCalculation::MODE_FIXED, $line['tax_mode']);
        self::assertSame(5_000, $line['tax_amount_rupiah']);
    }

    public function test_line_tax_and_header_tax_are_both_landed_to_lines(): void
    {
        $allocation = $this->allocator()->allocate([
            ['line_no' => 1, 'product_id' => 'product-1', 'qty_pcs' => 1, 'line_total_rupiah' => 100_000, 'tax_input' => '11%'],
            ['line_no' => 2, 'product_id' => 'product-2', 'qty_pcs' => 1, 'line_total_rupiah' => 50_000, 'tax_input' => '5000'],
        ], '10%');

        self::assertSame(150_000, $allocation->subtotalBeforeTaxRupiah());
        self::assertSame(16_600, $allocation->taxAmountRupiah());
        self::assertSame(182_600, $allocation->grandTotalAfterTaxRupiah());
        self::assertSame(122_100, $allocation->lines()[0]['line_total_rupiah']);
        self::assertSame(60_500, $allocation->lines()[1]['line_total_rupiah']);
        self::assertSame(11_000, $allocation->lines()[0]['tax_amount_rupiah']);
        self::assertSame(5_000, $allocation->lines()[1]['tax_amount_rupiah']);
    }

    public function test_invalid_line_tax_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->allocator()->allocate([
            ['line_no' => 1, 'product_id' => 'product-1', 'qty_pcs' => 1, 'line_total_rupiah' => 100_000, 'tax_input' => 'abc%'],
        ], null);
    }

    public function test_line_tax_that_breaks_integer_unit_cost_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Total setelah pajak tidak habis dibagi qty, sehingga modal per pcs akan dibulatkan dan selisih pembulatan akan dicatat. Lanjutkan?');

        $this->allocator()->allocate([
            ['line_no' => 1, 'product_id' => 'product-1', 'qty_pcs' => 2, 'line_total_rupiah' => 100, 'tax_input' => '1'],
        ], null);
    }

    private function allocator(): SupplierInvoiceTaxLandedCostAllocator
    {
        return new SupplierInvoiceTaxLandedCostAllocator(new SupplierInvoiceTaxInputCalculator());
    }
}
