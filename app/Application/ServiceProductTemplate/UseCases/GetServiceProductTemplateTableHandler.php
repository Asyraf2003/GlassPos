<?php

declare(strict_types=1);

namespace App\Application\ServiceProductTemplate\UseCases;

use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplateTableQuery;
use App\Application\Shared\DTO\Result;
use App\Ports\Out\ServiceProductTemplate\ServiceProductTemplateTableReaderPort;

final class GetServiceProductTemplateTableHandler
{
    public function __construct(private readonly ServiceProductTemplateTableReaderPort $templates) {}

    public function handle(ServiceProductTemplateTableQuery $query): Result
    {
        return Result::success($this->templates->search($query));
    }
}
