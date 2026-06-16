<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Note\WorkItem;

use App\Core\Note\WorkItem\StoreStockLine;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class StoreStockLineTest extends TestCase
{
    public function test_it_keeps_product_qty_and_line_total_exactly(): void
    {
        $line = StoreStockLine::create(
            'store-line-1',
            'product-1',
            2,
            Money::fromInt(40000),
        );

        $this->assertSame('store-line-1', $line->id());
        $this->assertSame('product-1', $line->productId());
        $this->assertSame(2, $line->qty());
        $this->assertSame(40000, $line->lineTotalRupiah()->amount());
    }

    public function test_it_rejects_non_positive_line_total(): void
    {
        $this->expectException(DomainException::class);

        StoreStockLine::create(
            'store-line-1',
            'product-1',
            2,
            Money::zero(),
        );
    }
}
