<?php

declare(strict_types=1);

namespace App\Application\Audit\Services;

use App\Application\Audit\DTO\AuditLogTableQuery;
use App\Ports\Out\AuditLogReaderPort;
use App\Ports\Out\Shared\PaginatedResult;

final class AuditLogIndexPageData
{
    public function __construct(
        private readonly AuditLogReaderPort $reader,
    ) {}

    public function listForAdmin(string $search = '', int $perPage = 20): PaginatedResult
    {
        return $this->reader->listForAdmin($search, $perPage);
    }

    public function tableForAdmin(AuditLogTableQuery $query): PaginatedResult
    {
        return $this->reader->tableForAdmin($query);
    }
}
