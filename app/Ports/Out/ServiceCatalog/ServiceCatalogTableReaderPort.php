<?php

declare(strict_types=1);

namespace App\Ports\Out\ServiceCatalog;

use App\Application\ServiceCatalog\DTO\ServiceCatalogTableQuery;

interface ServiceCatalogTableReaderPort
{
    /** @return array{rows:list<array<string, mixed>>,meta:array<string, mixed>} */
    public function search(ServiceCatalogTableQuery $query): array;
}
