<?php

declare(strict_types=1);

namespace App\Ports\Out\ServiceProductTemplate;

use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplateLookupRow;

interface ServiceProductTemplateLookupReaderPort
{
    public function findActiveByProductId(string $productId): ?ServiceProductTemplateLookupRow;
}
