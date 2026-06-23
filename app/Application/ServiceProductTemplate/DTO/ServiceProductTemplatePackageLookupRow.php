<?php

declare(strict_types=1);

namespace App\Application\ServiceProductTemplate\DTO;

final readonly class ServiceProductTemplatePackageLookupRow
{
    /**
     * @param list<ServiceProductTemplatePackageProductLineRow> $productLines
     */
    public function __construct(
        public string $id,
        public string $legacyProductId,
        public string $serviceCatalogItemId,
        public string $serviceName,
        public int $defaultServicePriceRupiah,
        public ?int $defaultPackageTotalRupiah,
        public bool $isActive,
        public array $productLines,
    ) {
    }

    public function productSummaryLabel(): string
    {
        return implode(
            ' + ',
            array_map(
                static fn (ServiceProductTemplatePackageProductLineRow $line): string => $line->label(),
                $this->productLines,
            ),
        );
    }

    public function hasSufficientStock(): bool
    {
        if ($this->productLines === []) {
            return false;
        }

        foreach ($this->productLines as $line) {
            if ($line->availableStock < $line->qty) {
                return false;
            }
        }

        return true;
    }
}
