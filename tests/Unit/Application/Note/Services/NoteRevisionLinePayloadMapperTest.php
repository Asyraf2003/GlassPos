<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Note\Services;

use App\Application\Note\Services\NoteRevisionLinePayloadMapper;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\StoreStockLine;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\ProductCatalog\Product\Product;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\ProductCatalog\ProductReaderPort;
use PHPUnit\Framework\TestCase;

final class NoteRevisionLinePayloadMapperTest extends TestCase
{
    public function test_it_snapshots_store_stock_product_name_in_revision_payload(): void
    {
        $mapper = new NoteRevisionLinePayloadMapper($this->products([
            Product::create(
                'product-1',
                'KB-001',
                'Filter Oli Lama',
                'Federal',
                90,
                Money::fromInt(50000),
                null,
                null,
            ),
        ]));

        $item = WorkItem::createStoreStockSaleOnly(
            'wi-1',
            'note-1',
            1,
            [
                StoreStockLine::create(
                    'sto-1',
                    'product-1',
                    2,
                    Money::fromInt(100000),
                ),
            ],
        );

        $payload = $mapper->map($item);

        self::assertSame('product-1', $payload['store_stock_lines'][0]['product_id']);
        self::assertSame('Filter Oli Lama', $payload['store_stock_lines'][0]['product_name_snapshot']);
    }

    public function test_it_omits_product_name_snapshot_when_product_cannot_be_resolved(): void
    {
        $mapper = new NoteRevisionLinePayloadMapper($this->products([]));

        $item = WorkItem::createStoreStockSaleOnly(
            'wi-1',
            'note-1',
            1,
            [
                StoreStockLine::create(
                    'sto-1',
                    'missing-product',
                    1,
                    Money::fromInt(50000),
                ),
            ],
        );

        $payload = $mapper->map($item);

        self::assertSame('missing-product', $payload['store_stock_lines'][0]['product_id']);
        self::assertArrayNotHasKey('product_name_snapshot', $payload['store_stock_lines'][0]);
    }

    public function test_it_snapshots_package_financial_fingerprint_for_service_with_store_stock_part(): void
    {
        $mapper = new NoteRevisionLinePayloadMapper($this->products([
            Product::create(
                'product-1',
                'KB-001',
                'Filter Oli Lama',
                'Federal',
                90,
                Money::fromInt(50000),
                null,
                null,
            ),
        ]));

        $item = WorkItem::createServiceWithStoreStockPart(
            'wi-1',
            'note-1',
            1,
            ServiceDetail::create(
                'Paket Servis Lama',
                Money::fromInt(130000),
                ServiceDetail::PART_SOURCE_NONE,
                Money::fromInt(40000),
                Money::fromInt(100000),
                Money::fromInt(30000),
            ),
            [
                StoreStockLine::create(
                    'sto-1',
                    'product-1',
                    1,
                    Money::fromInt(50000),
                ),
            ],
        );

        $payload = $mapper->map($item);

        self::assertSame('package_auto_split', $payload['pricing_mode'] ?? null);
        self::assertSame(220000, $payload['package_total_rupiah'] ?? null);
        self::assertSame(50000, $payload['parts_total_rupiah'] ?? null);
        self::assertSame(130000, $payload['service_price_rupiah'] ?? null);
        self::assertSame(100000, $payload['package_base_service_price_rupiah'] ?? null);
        self::assertSame(30000, $payload['package_service_extra_rupiah'] ?? null);
        self::assertSame(40000, $payload['package_profit_rupiah'] ?? null);
        self::assertSame(170000, $payload['total_service_component_rupiah'] ?? null);
        self::assertSame('Filter Oli Lama', $payload['store_stock_lines'][0]['product_name_snapshot'] ?? null);
        self::assertSame([], $payload['external_purchase_lines']);
    }

    /**
     * @param list<Product> $products
     */
    private function products(array $products): ProductReaderPort
    {
        return new class ($products) implements ProductReaderPort {
            /**
             * @param list<Product> $products
             */
            public function __construct(private readonly array $products)
            {
            }

            public function getById(string $productId): ?Product
            {
                foreach ($this->products as $product) {
                    if ($product->id() === $productId) {
                        return $product;
                    }
                }

                return null;
            }

            public function findAll(): array
            {
                return $this->products;
            }

            public function search(string $query): array
            {
                return $this->products;
            }
        };
    }
}
