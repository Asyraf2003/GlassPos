<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\ServiceCatalog\UseCases\CreateServiceCatalogItemHandler;
use App\Core\Note\WorkItem\WorkItem;

final class ServiceCatalogFromWorkItemSync
{
    public function __construct(private readonly CreateServiceCatalogItemHandler $serviceCatalog) {}

    public function sync(WorkItem $workItem): void
    {
        $service = $workItem->serviceDetail();

        if ($service === null || $service->totalPriceRupiah()->amount() <= 0) {
            return;
        }

        $this->serviceCatalog->handle(
            $service->serviceName(),
            $service->totalPriceRupiah()->amount(),
            null,
            'transaction_workspace',
        );
    }
}
