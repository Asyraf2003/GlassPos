<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Procurement\Services;

use App\Application\Procurement\Services\SupplierInvoiceTaxInputCalculation;
use App\Application\Procurement\Services\SupplierInvoiceTaxInputCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SupplierInvoiceTaxInputCalculatorTest extends TestCase
{
    public function test_fixed_rupiah_accepts_plain_integer_input(): void
    {
        $calculation = (new SupplierInvoiceTaxInputCalculator())->calculate(2000, 20000);

        $this->assertSame('2000', $calculation->taxInput());
        $this->assertSame(SupplierInvoiceTaxInputCalculation::MODE_FIXED, $calculation->taxMode());
        $this->assertNull($calculation->taxRateBasisPoints());
        $this->assertSame(2000, $calculation->taxAmountRupiah());
    }

    public function test_fixed_rupiah_accepts_rupiah_thousand_separator_input(): void
    {
        $calculation = (new SupplierInvoiceTaxInputCalculator())->calculate('Rp 2.000', 20000);

        $this->assertSame('Rp 2.000', $calculation->taxInput());
        $this->assertSame(SupplierInvoiceTaxInputCalculation::MODE_FIXED, $calculation->taxMode());
        $this->assertSame(2000, $calculation->taxAmountRupiah());
    }

    public function test_fixed_rupiah_rejects_negative_input(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Format nominal pajak supplier invoice tidak valid.');

        (new SupplierInvoiceTaxInputCalculator())->calculate('-1000', 20000);
    }

    public function test_fixed_rupiah_rejects_alphanumeric_input(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Format nominal pajak supplier invoice tidak valid.');

        (new SupplierInvoiceTaxInputCalculator())->calculate('abc123', 20000);
    }

    public function test_fixed_rupiah_rejects_decimal_like_input(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Format nominal pajak supplier invoice tidak valid.');

        (new SupplierInvoiceTaxInputCalculator())->calculate('1.234,56', 20000);
    }

    public function test_percent_accepts_comma_decimal_input(): void
    {
        $calculation = (new SupplierInvoiceTaxInputCalculator())->calculate('11,5%', 20000);

        $this->assertSame(SupplierInvoiceTaxInputCalculation::MODE_PERCENT, $calculation->taxMode());
        $this->assertSame(1150, $calculation->taxRateBasisPoints());
        $this->assertSame(2300, $calculation->taxAmountRupiah());
    }
}
