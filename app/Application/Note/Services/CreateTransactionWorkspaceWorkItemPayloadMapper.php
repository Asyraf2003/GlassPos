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
        $item = (new CreateTransactionWorkspaceServiceExternalPurchasePackagePricingComposer())->compose($item);

        $service = [
            'service_name' => $this->requiredString($item['service']['name'] ?? null, 'Nama servis wajib diisi.'),
            'service_price_rupiah' => $this->requiredServicePrice($item),
            'package_profit_rupiah' => $this->optionalNonNegativeInt($item['service']['package_profit_rupiah'] ?? 0),
            'package_base_service_price_rupiah' => $this->optionalNullableNonNegativeInt($item['service']['package_base_service_price_rupiah'] ?? null),
            'package_service_extra_rupiah' => $this->optionalNonNegativeInt($item['service']['package_service_extra_rupiah'] ?? 0),
            'part_source' => 'none',
        ];

        if ($this->variants->hasExternalPurchaseLines($item)) {
            return [WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE, $service, [$this->external->map($item)], []];
        }

        if ($this->variants->hasStoreStockLines($item)) {
            return [WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART, $service, [], $this->storeStock->mapMany($item)];
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
    private function optionalNonNegativeInt(mixed $value): int
    {
        return is_int($value) && $value > 0 ? $value : 0;
    }

    private function optionalNullableNonNegativeInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return is_int($value) && $value >= 0 ? $value : null;
    }

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
