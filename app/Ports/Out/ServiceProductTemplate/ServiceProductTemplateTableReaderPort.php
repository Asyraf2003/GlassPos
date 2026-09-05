<?php

declare(strict_types=1);

namespace App\Ports\Out\ServiceProductTemplate;

use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplateTableQuery;

interface ServiceProductTemplateTableReaderPort
{
    /** @return array{rows:list<array<string, mixed>>,meta:array<string, mixed>} */
    public function search(ServiceProductTemplateTableQuery $query): array;
}
