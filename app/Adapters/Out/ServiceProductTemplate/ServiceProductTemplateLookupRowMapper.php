<?php

declare(strict_types=1);

namespace App\Adapters\Out\ServiceProductTemplate;

use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplateLookupRow;

final class ServiceProductTemplateLookupRowMapper
{
    public function map(object $row): ServiceProductTemplateLookupRow
    {
        return new ServiceProductTemplateLookupRow(
            id: (string) $row->id,
            productId: (string) $row->product_id,
            serviceCatalogItemId: (string) $row->service_catalog_item_id,
            serviceName: (string) $row->service_name,
            defaultServicePriceRupiah: (int) $row->default_service_price_rupiah,
            defaultPackageTotalRupiah: $row->default_package_total_rupiah !== null
                ? (int) $row->default_package_total_rupiah
                : null,
            isActive: (bool) $row->is_active,
        );
    }
}
