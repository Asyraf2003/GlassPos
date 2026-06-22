<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Procurement\Services;

use App\Application\Procurement\Services\SupplierInvoiceTaxInputCalculation;
use App\Application\Procurement\Services\SupplierInvoiceTaxInputCalculator;
use App\Application\Procurement\Services\SupplierInvoiceTaxLandedCostAllocator;
use InvalidArgumentException;
use Tests\TestCase;

final class SupplierInvoiceTaxLandedCostAllocatorTest extends TestCase
{
    public function test_empty_tax_keeps_supplier_invoice_lines_unchanged(): void
    {
        $lines = [
            ['line_no' => 1, 'product_id' => 'product-1', 'qty_pcs' => 2, 'line_total_rupiah' => 100_000],
            ['line_no' => 2, 'product_id' => 'product-2', 'qty_pcs' => 1, 'line_total_rupiah' => 50_000],
        ];

        $allocation = $this->allocator()->allocate($lines, null);

        self::assertSame(150_000, $allocation->subtotalBeforeTaxRupiah());
        self::assertSame(0, $allocation->taxAmountRupiah());
        self::assertSame(150_000, $allocation->grandTotalAfterTaxRupiah());
        self::assertSame(SupplierInvoiceTaxInputCalculation::MODE_NONE, $allocation->tax()->taxMode());
        self::assertSame($lines, $allocation->lines());
    }

    public function test_percent_tax_is_distributed_to_line_total_as_landed_cost(): void
    {
        $allocation = $this->allocator()->allocate([
            ['line_no' => 1, 'product_id' => 'product-1', 'qty_pcs' => 2, 'line_total_rupiah' => 100_000],
            ['line_no' => 2, 'product_id' => 'product-2', 'qty_pcs' => 1, 'line_total_rupiah' => 50_000],
        ], '10%');

        self::assertSame(150_000, $allocation->subtotalBeforeTaxRupiah());
        self::assertSame(15_000, $allocation->taxAmountRupiah());
        self::assertSame(165_000, $allocation->grandTotalAfterTaxRupiah());

        self::assertSame(110_000, $allocation->lines()[0]['line_total_rupiah']);
        self::assertSame(55_000, $allocation->lines()[1]['line_total_rupiah']);
        self::assertSame(165_000, array_sum(array_column($allocation->lines(), 'line_total_rupiah')));
    }

    public function test_fixed_tax_is_distributed_to_line_total_as_landed_cost(): void
    {
        $allocation = $this->allocator()->allocate([
            ['line_no' => 1, 'product_id' => 'product-1', 'qty_pcs' => 2, 'line_total_rupiah' => 80_000],
            ['line_no' => 2, 'product_id' => 'product-2', 'qty_pcs' => 1, 'line_total_rupiah' => 20_000],
        ], '15000');

        self::assertSame(100_000, $allocation->subtotalBeforeTaxRupiah());
        self::assertSame(15_000, $allocation->taxAmountRupiah());
        self::assertSame(115_000, $allocation->grandTotalAfterTaxRupiah());

        self::assertSame(92_000, $allocation->lines()[0]['line_total_rupiah']);
        self::assertSame(23_000, $allocation->lines()[1]['line_total_rupiah']);
    }

    public function test_remainder_is_allocated_by_largest_remainder_without_float_money(): void
    {
        $allocation = $this->allocator()->allocate([
            ['line_no' => 1, 'product_id' => 'product-1', 'qty_pcs' => 1, 'line_total_rupiah' => 100],
            ['line_no' => 2, 'product_id' => 'product-2', 'qty_pcs' => 1, 'line_total_rupiah' => 1],
        ], '1');

        self::assertSame(101, $allocation->subtotalBeforeTaxRupiah());
        self::assertSame(1, $allocation->taxAmountRupiah());
        self::assertSame(102, $allocation->grandTotalAfterTaxRupiah());

        self::assertSame(101, $allocation->lines()[0]['line_total_rupiah']);
        self::assertSame(1, $allocation->lines()[1]['line_total_rupiah']);
    }

    public function test_tax_allocation_rejects_result_that_breaks_integer_unit_cost_invariant(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Total setelah pajak tidak habis dibagi qty, sehingga modal per pcs akan dibulatkan dan selisih pembulatan akan dicatat. Lanjutkan?');

        $this->allocator()->allocate([
            ['line_no' => 1, 'product_id' => 'product-1', 'qty_pcs' => 3, 'line_total_rupiah' => 300],
        ], '1');
    }

    public function test_positive_tax_rejects_zero_subtotal(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->allocator()->allocate([
            ['line_no' => 1, 'product_id' => 'product-1', 'qty_pcs' => 1, 'line_total_rupiah' => 0],
        ], '15000');
    }

    private function allocator(): SupplierInvoiceTaxLandedCostAllocator
    {
        return new SupplierInvoiceTaxLandedCostAllocator(new SupplierInvoiceTaxInputCalculator());
    }
}
