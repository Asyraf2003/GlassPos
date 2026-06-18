<?php

declare(strict_types=1);

namespace App\Application\ServiceProductTemplate\DTO;

final readonly class ServiceProductTemplateLookupRow
{
    public function __construct(
        public string $id,
        public string $productId,
        public string $serviceCatalogItemId,
        public string $serviceName,
        public int $defaultServicePriceRupiah,
        public ?int $defaultPackageTotalRupiah,
        public bool $isActive,
    ) {
    }
}
