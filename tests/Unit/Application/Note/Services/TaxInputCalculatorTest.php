<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Note\Services;

use App\Application\Note\Services\TaxInputCalculation;
use App\Application\Note\Services\TaxInputCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class TaxInputCalculatorTest extends TestCase
{
    #[DataProvider('emptyInputs')]
    public function test_empty_input_means_no_tax(null|string|int $input): void
    {
        $result = $this->calculator()->calculate($input, 100_000);

        self::assertSame(null, $result->taxInput());
        self::assertSame(TaxInputCalculation::MODE_NONE, $result->taxMode());
        self::assertSame(null, $result->taxRateBasisPoints());
        self::assertSame(0, $result->taxAmountRupiah());
    }

    /** @return array<string, array{0:null|string|int}> */
    public static function emptyInputs(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'spaces' => ['   '],
        ];
    }

    #[DataProvider('percentInputs')]
    public function test_percent_input_uses_basis_points_and_round_half_up(
        string $input,
        int $baseRupiah,
        int $expectedBasisPoints,
        int $expectedAmountRupiah,
    ): void {
        $result = $this->calculator()->calculate($input, $baseRupiah);

        self::assertSame($input, $result->taxInput());
        self::assertSame(TaxInputCalculation::MODE_PERCENT, $result->taxMode());
        self::assertSame($expectedBasisPoints, $result->taxRateBasisPoints());
        self::assertSame($expectedAmountRupiah, $result->taxAmountRupiah());
    }

    /** @return array<string, array{0:string,1:int,2:int,3:int}> */
    public static function percentInputs(): array
    {
        return [
            '11 percent' => ['11%', 100_000, 1100, 11_000],
            '10.5 percent' => ['10.5%', 100_000, 1050, 10_500],
            '0.5 percent' => ['0.5%', 100_000, 50, 500],
            'comma decimal percent' => ['10,5%', 100_000, 1050, 10_500],
            'round half up' => ['0.5%', 101, 50, 1],
        ];
    }

    #[DataProvider('fixedInputs')]
    public function test_fixed_input_normalizes_rupiah_nominal(
        string|int $input,
        int $expectedAmountRupiah,
    ): void {
        $result = $this->calculator()->calculate($input, 100_000);

        self::assertSame((string) $input, $result->taxInput());
        self::assertSame(TaxInputCalculation::MODE_FIXED, $result->taxMode());
        self::assertSame(null, $result->taxRateBasisPoints());
        self::assertSame($expectedAmountRupiah, $result->taxAmountRupiah());
    }

    /** @return array<string, array{0:string|int,1:int}> */
    public static function fixedInputs(): array
    {
        return [
            'plain integer string' => ['15000', 15_000],
            'plain integer' => [15000, 15_000],
            'rupiah prefix dot thousands' => ['Rp 10.000', 10_000],
            'dot thousands' => ['10.000', 10_000],
            'comma thousands' => ['10,000', 10_000],
        ];
    }

    public function test_invalid_percent_format_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator()->calculate('10.555%', 100_000);
    }

    public function test_negative_base_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator()->calculate('11%', -1);
    }

    private function calculator(): TaxInputCalculator
    {
        return new TaxInputCalculator();
    }
}
