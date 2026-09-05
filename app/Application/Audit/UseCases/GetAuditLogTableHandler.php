<?php

declare(strict_types=1);

namespace App\Application\Audit\UseCases;

use App\Application\Audit\DTO\AuditLogTableQuery;
use App\Application\Shared\DTO\Result;
use App\Ports\Out\AuditLogReaderPort;

final class GetAuditLogTableHandler
{
    public function __construct(private readonly AuditLogReaderPort $reader) {}

    public function handle(AuditLogTableQuery $query): Result
    {
        $page = $this->reader->tableForAdmin($query);

        return Result::success([
            'rows' => array_map(static function (array $row): array {
                unset($row['context'], $row['context_json']);
                $row['show_url'] = route('admin.audit-logs.show', [
                    'source' => $row['source'],
                    'auditId' => $row['id'],
                ]);

                return $row;
            }, $page->items),
            'meta' => [
                'page' => $page->currentPage,
                'per_page' => 20,
                'total' => $page->total,
                'last_page' => max(1, (int) ceil($page->total / 20)),
                'sort_by' => $query->sortBy(),
                'sort_dir' => $query->sortDir(),
                'filters' => ['q' => $query->q(), 'source' => $query->source()],
            ],
        ]);
    }
}
