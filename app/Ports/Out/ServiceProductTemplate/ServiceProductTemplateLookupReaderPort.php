<?php

declare(strict_types=1);

namespace App\Ports\Out\ServiceProductTemplate;

use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplateLookupRow;
use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplatePackageLookupRow;

interface ServiceProductTemplateLookupReaderPort
{
    public const DEFAULT_PACKAGE_LIMIT = 10;
    public const MAX_PACKAGE_LIMIT = 25;

    public function findActiveByProductId(string $productId): ?ServiceProductTemplateLookupRow;

    /**
     * @return list<ServiceProductTemplatePackageLookupRow>
     */
    public function searchActivePackages(string $query, int $limit = self::DEFAULT_PACKAGE_LIMIT): array;
}
