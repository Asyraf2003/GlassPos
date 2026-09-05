<?php

declare(strict_types=1);

namespace App\Ports\Out\ServiceProductTemplate;

interface ServiceProductTemplateCreatePort
{
    public function activeTemplateExists(string $productId, string $serviceCatalogItemId): bool;

    public function serviceDefaultPriceRupiah(string $serviceCatalogItemId): ?int;

    /** @param list<array{product_id:string,qty:int,sort_order:int}> $lines */
    public function productLinesTotalRupiah(array $lines): ?int;

    /** @param list<array{product_id:string,qty:int,sort_order:int}> $lines */
    public function create(
        string $templateId,
        string $serviceCatalogItemId,
        int $servicePriceRupiah,
        int $packageTotalRupiah,
        array $lines,
    ): void;
}
