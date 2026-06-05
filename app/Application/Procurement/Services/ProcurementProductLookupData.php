<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services;

use App\Application\ProductCatalog\DTO\ProductLookupRow;
use App\Ports\Out\ProductCatalog\ProductLookupReaderPort;

final class ProcurementProductLookupData
{
    public function __construct(
        private readonly ProductLookupReaderPort $products,
    ) {
    }

    /**
     * @return list<ProductLookupRow>
     */
    public function search(string $search, int $limit = ProductLookupReaderPort::DEFAULT_LIMIT): array
    {
        return $this->products->search(trim($search), $limit);
    }
}
