<?php

declare(strict_types=1);

namespace App\Ports\Out\ProductCatalog;

use App\Application\ProductCatalog\DTO\ProductLookupRow;

interface ProductLookupReaderPort
{
    public const DEFAULT_LIMIT = 20;
    public const MAX_LIMIT = 50;

    /**
     * @return list<ProductLookupRow>
     */
    public function search(string $query, int $limit = self::DEFAULT_LIMIT, bool $onlyInStock = false): array;
}
