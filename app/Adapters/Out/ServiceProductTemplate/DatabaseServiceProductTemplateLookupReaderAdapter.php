<?php

declare(strict_types=1);

namespace App\Adapters\Out\ServiceProductTemplate;

use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplateLookupRow;
use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplatePackageLookupRow;
use App\Ports\Out\ServiceProductTemplate\ServiceProductTemplateLookupReaderPort;

final class DatabaseServiceProductTemplateLookupReaderAdapter implements ServiceProductTemplateLookupReaderPort
{
    public function __construct(
        private readonly ActiveServiceProductTemplateLookupQuery $activeLookup,
        private readonly ServiceProductTemplateLookupRowMapper $lookupRows,
        private readonly DatabaseServiceProductTemplatePackageSearchQuery $packageSearch,
        private readonly DatabaseServiceProductTemplatePackageMapper $packageRows,
    ) {
    }

    public function findActiveByProductId(string $productId): ?ServiceProductTemplateLookupRow
    {
        $row = $this->activeLookup->firstByProductId($productId);

        return $row === null ? null : $this->lookupRows->map($row);
    }

    /**
     * @return list<ServiceProductTemplatePackageLookupRow>
     */
    public function searchActivePackages(
        string $query,
        int $limit = ServiceProductTemplateLookupReaderPort::DEFAULT_PACKAGE_LIMIT,
    ): array {
        $packages = [];

        foreach ($this->packageSearch->search($query, $limit) as $row) {
            $package = $this->packageRows->map($row);

            if ($package !== null) {
                $packages[] = $package;
            }
        }

        return $packages;
    }
}
