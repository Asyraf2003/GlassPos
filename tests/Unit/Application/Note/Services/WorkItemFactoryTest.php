<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Note\Services;

use App\Application\Note\Services\WorkItemFactory;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\ProductCatalog\Policies\MinSellingPricePolicy;
use App\Core\ProductCatalog\Product\Product;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\ProductCatalog\ProductReaderPort;
use App\Ports\Out\UuidPort;
use PHPUnit\Framework\TestCase;

final class WorkItemFactoryTest extends TestCase
{
    public function test_store_stock_current_catalog_line_rejects_price_below_minimum(): void
    {
        $factory = $this->factoryWithProductHargaJual(100000);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Harga jual pada store stock line tidak boleh di bawah harga jual minimum.');

        $factory->build(
            'note-1',
            1,
            WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
            [],
            [],
            [[
                'product_id' => 'product-1',
                'qty' => 1,
                'line_total_rupiah' => 1,
                'price_basis' => 'current_catalog',
            ]]
        );
    }

    public function test_store_stock_revision_snapshot_line_rejects_price_below_minimum(): void
    {
        $factory = $this->factoryWithProductHargaJual(100000);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Harga jual pada store stock line tidak boleh di bawah harga jual minimum.');

        $factory->build(
            'note-1',
            1,
            WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
            [],
            [],
            [[
                'product_id' => 'product-1',
                'qty' => 1,
                'line_total_rupiah' => 1,
                'price_basis' => 'revision_snapshot',
            ]]
        );
    }

    public function test_store_stock_revision_snapshot_line_accepts_price_at_minimum(): void
    {
        $factory = $this->factoryWithProductHargaJual(100000);

        $item = $factory->build(
            'note-1',
            1,
            WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
            [],
            [],
            [[
                'product_id' => 'product-1',
                'qty' => 1,
                'line_total_rupiah' => 100000,
                'price_basis' => 'revision_snapshot',
            ]]
        );

        $this->assertSame(100000, $item->subtotalRupiah()->amount());
    }


    public function test_store_stock_server_trusted_revision_snapshot_accepts_historical_price_below_current_minimum(): void
    {
        $factory = $this->factoryWithProductHargaJual(110000);

        $item = $factory->build(
            'note-1',
            1,
            WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
            [],
            [],
            [[
                'product_id' => 'product-1',
                'qty' => 3,
                'line_total_rupiah' => 300000,
                'price_basis' => 'revision_snapshot',
                '_server_trusted_revision_snapshot' => true,
            ]]
        );

        $this->assertSame(300000, $item->subtotalRupiah()->amount());
    }

    private function factoryWithProductHargaJual(int $hargaJual): WorkItemFactory
    {
        $product = Product::create(
            'product-1',
            'PRD-1',
            'Produk A',
            'Merek A',
            100,
            Money::fromInt($hargaJual),
            null,
            null
        );

        $products = new class($product) implements ProductReaderPort {
            public function __construct(private Product $product)
            {
            }

            public function getById(string $productId): ?Product
            {
                return $productId === $this->product->id() ? $this->product : null;
            }

            public function findAll(): array
            {
                return [$this->product];
            }

            public function search(string $query): array
            {
                return [$this->product];
            }
        };

        $uuid = new class implements UuidPort {
            private int $counter = 0;

            public function generate(): string
            {
                $this->counter++;

                return 'uuid-' . $this->counter;
            }
        };

        return new WorkItemFactory(
            $uuid,
            $products,
            new MinSellingPricePolicy()
        );
    }
}
