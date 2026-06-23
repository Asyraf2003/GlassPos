<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\WorkItem\WorkItem;
use App\Ports\Out\ServiceCatalog\ServiceCatalogWriterPort;

final class ServiceCatalogFromWorkItemSync
{
    public function __construct(private readonly ServiceCatalogWriterPort $serviceCatalog)
    {
    }

    public function sync(WorkItem $workItem): void
    {
        $service = $workItem->serviceDetail();

        if ($service === null || $service->totalPriceRupiah()->amount() <= 0) {
            return;
        }

        $this->serviceCatalog->createIfMissing(
            $service->serviceName(),
            $service->totalPriceRupiah()->amount()
        );
    }
}
