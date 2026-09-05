<?php

declare(strict_types=1);

namespace App\Application\ServiceCatalog\UseCases;

use App\Application\ServiceCatalog\DTO\ServiceCatalogTableQuery;
use App\Application\Shared\DTO\Result;
use App\Ports\Out\ServiceCatalog\ServiceCatalogTableReaderPort;

final class GetServiceCatalogTableHandler
{
    public function __construct(private readonly ServiceCatalogTableReaderPort $services)
    {
    }

    public function handle(ServiceCatalogTableQuery $query): Result
    {
        return Result::success($this->services->search($query));
    }
}
