<?php

declare(strict_types=1);

namespace App\Adapters\Out\ServiceProductTemplate;

use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplatePackageLookupRow;

final class DatabaseServiceProductTemplatePackageMapper
{
    public function __construct(private readonly DatabaseServiceProductTemplatePackageProductLinesReader $lines)
    {
    }

    public function map(object $row): ?ServiceProductTemplatePackageLookupRow
    {
        $productLines = $this->lines->forTemplate(
            templateId: (string) $row->id,
            legacyProductId: (string) $row->legacy_product_id,
        );

        if ($productLines === []) {
            return null;
        }

        return new ServiceProductTemplatePackageLookupRow(
            id: (string) $row->id,
            legacyProductId: (string) $row->legacy_product_id,
            serviceCatalogItemId: (string) $row->service_catalog_item_id,
            serviceName: (string) $row->service_name,
            defaultServicePriceRupiah: (int) $row->default_service_price_rupiah,
            defaultPackageTotalRupiah: $row->default_package_total_rupiah !== null
                ? (int) $row->default_package_total_rupiah
                : null,
            isActive: (bool) $row->is_active,
            productLines: $productLines,
        );
    }
}
