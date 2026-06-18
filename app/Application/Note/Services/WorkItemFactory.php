<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\WorkItem\WorkItem;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\ExternalPurchaseLine;
use App\Core\Note\WorkItem\StoreStockLine;
use App\Core\Shared\ValueObjects\Money;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\ProductCatalog\ProductReaderPort;
use App\Ports\Out\UuidPort;
use App\Core\ProductCatalog\Policies\MinSellingPricePolicy;

final class WorkItemFactory
{
    public function __construct(
        private UuidPort $uuid,
        private ProductReaderPort $products,
        private MinSellingPricePolicy $pricePolicy
    ) {}

    public function build(string $noteId, int $lineNo, string $type, array $sd, array $ext, array $sto): WorkItem
    {
        return match ($type) {
            WorkItem::TYPE_SERVICE_ONLY => WorkItem::createServiceOnly($this->uuid->generate(), $noteId, $lineNo, $this->makeSd($sd)),
            WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE => WorkItem::createServiceWithExternalPurchase($this->uuid->generate(), $noteId, $lineNo, $this->makeSd($sd), $this->makeExt($ext)),
            WorkItem::TYPE_STORE_STOCK_SALE_ONLY => WorkItem::createStoreStockSaleOnly($this->uuid->generate(), $noteId, $lineNo, $this->makeSto($sto)),
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART => WorkItem::createServiceWithStoreStockPart($this->uuid->generate(), $noteId, $lineNo, $this->makeSd($sd), $this->makeSto($sto)),
            default => throw new DomainException("Tipe transaksi [$type] tidak didukung.")
        };
    }

    private function makeSd(array $p): ServiceDetail {
        $baseServicePrice = isset($p['package_base_service_price_rupiah'])
            ? Money::fromInt((int) $p['package_base_service_price_rupiah'])
            : null;

        return ServiceDetail::create(
            trim((string)($p['service_name'] ?? throw new DomainException('Service name wajib ada.'))),
            Money::fromInt((int)($p['service_price_rupiah'] ?? throw new DomainException('Service price wajib ada.'))),
            (string)($p['part_source'] ?? ServiceDetail::PART_SOURCE_NONE),
            Money::fromInt((int)($p['package_profit_rupiah'] ?? 0)),
            $baseServicePrice,
            Money::fromInt((int)($p['package_service_extra_rupiah'] ?? 0)),
        );
    }

    private function makeExt(array $payload): array {
        return array_map(fn($p) => ExternalPurchaseLine::create($this->uuid->generate(), trim((string)$p['cost_description']), Money::fromInt((int)$p['unit_cost_rupiah']), (int)$p['qty']), $payload);
    }

    private function makeSto(array $payload): array {
        return array_map(function($p) {
            $prod = $this->products->getById((string)$p['product_id']) ?? throw new DomainException('Product tidak ditemukan.');
            $total = Money::fromInt((int)$p['line_total_rupiah']);
            $isTrustedRevisionSnapshot = ($p['_server_trusted_revision_snapshot'] ?? false) === true;

            if (! $isTrustedRevisionSnapshot) {
                $this->pricePolicy->assertAllowed($prod, (int)$p['qty'], $total);
            }

            return StoreStockLine::create($this->uuid->generate(), $prod->id(), (int)$p['qty'], $total);
        }, $payload);
    }
}
