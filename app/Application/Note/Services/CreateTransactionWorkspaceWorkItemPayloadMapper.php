<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;

final class CreateTransactionWorkspaceWorkItemPayloadMapper
{
    public function __construct(
        private readonly CreateTransactionWorkspaceStoreStockLineMapper $storeStock,
        private readonly CreateTransactionWorkspaceExternalPurchaseLineMapper $external,
        private readonly CreateTransactionWorkspaceServiceWorkItemVariantResolver $variants,
        private readonly CreateTransactionWorkspaceServiceStoreStockPackagePricingComposer $packagePricing,
    ) {
    }

    /**
     * @param array<string, mixed> $item
     * @return array{0:string,1:array<string, mixed>,2:list<array<string, mixed>>,3:list<array<string, mixed>>}
     */
    public function map(array $item): array
    {
        $entryMode = (string) ($item['entry_mode'] ?? '');

        if ($entryMode === 'product') {
            return [
                WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
                [],
                [],
                [$this->storeStock->map($item)],
            ];
        }

        if ($entryMode !== 'service') {
            throw new DomainException('Tipe item workspace tidak didukung.');
        }

        $item = $this->packagePricing->compose($item);

        $service = [
            'service_name' => $this->requiredString($item['service']['name'] ?? null, 'Nama servis wajib diisi.'),
            'service_price_rupiah' => $this->requiredServicePrice($item),
            'part_source' => 'none',
        ];

        if ($this->variants->hasExternalPurchaseLines($item)) {
            return [WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE, $service, [$this->external->map($item)], []];
        }

        if ($this->variants->hasStoreStockLines($item)) {
            return [WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART, $service, [], [$this->storeStock->map($item)]];
        }

        return [WorkItem::TYPE_SERVICE_ONLY, $service, [], []];
    }

    private function requiredString(mixed $value, string $message): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw new DomainException($message);
        }

        return trim($value);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function requiredServicePrice(array $item): int
    {
        $value = $item['service']['price_rupiah'] ?? null;

        if (! is_int($value)) {
            throw new DomainException('Harga servis wajib diisi.');
        }

        if ($value > 0) {
            return $value;
        }

        if ($value === 0 && ($item['pricing_mode'] ?? null) === 'package_auto_split') {
            return 0;
        }

        throw new DomainException('Harga servis wajib diisi.');
    }

}
