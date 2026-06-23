<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Note\UseCases;

use App\Application\Note\Services\CreateTransactionWorkspaceExternalPurchaseLineMapper;
use App\Application\Note\Services\CreateTransactionWorkspaceServiceWorkItemVariantResolver;
use App\Application\Note\Services\CreateTransactionWorkspaceServiceStoreStockPackagePricingComposer;
use App\Application\Note\Services\CreateTransactionWorkspaceStoreStockLineMapper;
use App\Application\Note\Services\CreateTransactionWorkspaceWorkItemPayloadMapper;
use App\Application\Note\Services\RevisionWorkspace\RevisionSnapshotStoreStockLineKeyer;
use App\Application\Note\Services\RevisionWorkspace\RevisionSnapshotStoreStockLineTrustInventory;
use App\Application\Note\Services\RevisionWorkspace\RevisionSnapshotStoreStockLineTrustMarker;
use App\Application\Note\Services\ServiceCatalogFromWorkItemSync;
use App\Application\Note\Services\WorkItemFactory;
use App\Application\Note\UseCases\CreateNoteRevisionPayloadNoteBuilder;
use App\Application\Note\UseCases\CreateNoteRevisionPayloadWorkItemBuilder;
use App\Core\Note\WorkItem\StoreStockLine;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\ProductCatalog\Policies\MinSellingPricePolicy;
use App\Core\ProductCatalog\Product\Product;
use App\Core\ServiceCatalog\ServiceCatalogItem;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\ProductCatalog\ProductReaderPort;
use App\Ports\Out\ServiceCatalog\ServiceCatalogWriterPort;
use App\Ports\Out\ServiceProductTemplate\ServiceProductTemplateLookupReaderPort;
use App\Ports\Out\UuidPort;
use PHPUnit\Framework\TestCase;

final class CreateNoteRevisionPayloadNoteBuilderTest extends TestCase
{
    public function test_it_accepts_server_trusted_historical_revision_snapshot_below_current_catalog_price(): void
    {
        $builder = $this->builderWithCurrentProductPrice(110000);

        $note = $builder->build(
            'note-1',
            $this->payloadForRevisionSnapshot(3, 100000),
            null,
            $this->currentHistoricalWorkItems()
        );

        $this->assertSame(300000, $note->totalRupiah()->amount());
        $this->assertCount(1, $note->workItems());
    }

    public function test_it_rejects_forged_revision_snapshot_below_current_catalog_price(): void
    {
        $builder = $this->builderWithCurrentProductPrice(110000);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Harga jual pada store stock line tidak boleh di bawah harga jual minimum.');

        $builder->build(
            'note-1',
            $this->payloadForRevisionSnapshot(1, 1),
            null,
            $this->currentHistoricalWorkItems()
        );
    }

    private function payloadForRevisionSnapshot(int $qty, int $unitPriceRupiah): array
    {
        return [
            'note' => [
                'customer_name' => 'Budi Revised Product',
                'customer_phone' => '08123456789',
                'transaction_date' => '2026-05-08',
            ],
            'items' => [
                [
                    'entry_mode' => 'product',
                    'description' => null,
                    'part_source' => 'store_stock',
                    'service' => [
                        'name' => null,
                        'price_rupiah' => null,
                        'notes' => null,
                    ],
                    'product_lines' => [
                        [
                            'product_id' => 'product-1',
                            'qty' => $qty,
                            'unit_price_rupiah' => $unitPriceRupiah,
                            'price_basis' => 'revision_snapshot',
                        ],
                    ],
                    'external_purchase_lines' => [],
                ],
            ],
        ];
    }

    /**
     * @return list<WorkItem>
     */
    private function currentHistoricalWorkItems(): array
    {
        return [
            WorkItem::createStoreStockSaleOnly(
                'wi-old-1',
                'note-1',
                1,
                [
                    StoreStockLine::create(
                        'ssl-old-1',
                        'product-1',
                        3,
                        Money::fromInt(300000)
                    ),
                ]
            ),
        ];
    }

    private function builderWithCurrentProductPrice(int $hargaJual): CreateNoteRevisionPayloadNoteBuilder
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

        $templates = new class implements ServiceProductTemplateLookupReaderPort {
            public function findActiveByProductId(string $productId): ?\App\Application\ServiceProductTemplate\DTO\ServiceProductTemplateLookupRow
            {
                return null;
            }

            public function searchActivePackages(
                string $query,
                int $limit = ServiceProductTemplateLookupReaderPort::DEFAULT_PACKAGE_LIMIT,
            ): array {
                return [];
            }
        };

        $mapper = new CreateTransactionWorkspaceWorkItemPayloadMapper(
            new CreateTransactionWorkspaceStoreStockLineMapper(),
            new CreateTransactionWorkspaceExternalPurchaseLineMapper(),
            new CreateTransactionWorkspaceServiceWorkItemVariantResolver(),
            new CreateTransactionWorkspaceServiceStoreStockPackagePricingComposer($products, $templates),
        );

        $factory = new WorkItemFactory(
            $uuid,
            $products,
            new MinSellingPricePolicy()
        );

        return new CreateNoteRevisionPayloadNoteBuilder(
            new CreateNoteRevisionPayloadWorkItemBuilder(
                $mapper,
                $factory,
                new ServiceCatalogFromWorkItemSync($this->serviceCatalogWriter()),
            ),
            $this->trustMarker(),
        );
    }

    private function serviceCatalogWriter(): ServiceCatalogWriterPort
    {
        return new class implements ServiceCatalogWriterPort {
            public function createIfMissing(string $name, int $defaultPriceRupiah): ServiceCatalogItem
            {
                return ServiceCatalogItem::rehydrate(
                    'service-test',
                    $name,
                    mb_strtolower(trim($name), 'UTF-8'),
                    $defaultPriceRupiah,
                    true,
                );
            }
        };
    }


    private function trustMarker(): RevisionSnapshotStoreStockLineTrustMarker
    {
        $keyer = new RevisionSnapshotStoreStockLineKeyer();

        return new RevisionSnapshotStoreStockLineTrustMarker(
            new RevisionSnapshotStoreStockLineTrustInventory($keyer),
            $keyer,
        );
    }
}
