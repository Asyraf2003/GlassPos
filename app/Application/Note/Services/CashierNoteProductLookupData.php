<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\ProductCatalog\DTO\ProductLookupRow;
use App\Ports\Out\ProductCatalog\ProductLookupReaderPort;

final class CashierNoteProductLookupData
{
    public function __construct(
        private readonly ProductLookupReaderPort $products,
    ) {
    }

    /**
     * @return list<ProductLookupRow>
     */
    public function searchAvailableProducts(string $query, int $limit = ProductLookupReaderPort::DEFAULT_LIMIT): array
    {
        return $this->products->search(trim($query), $limit, onlyInStock: true);
    }

    /**
     * @return list<ProductLookupRow>
     */
    public function searchProducts(string $query, int $limit = ProductLookupReaderPort::DEFAULT_LIMIT): array
    {
        return $this->products->search(trim($query), $limit);
    }
}
