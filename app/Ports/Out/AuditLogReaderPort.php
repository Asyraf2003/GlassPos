<?php

declare(strict_types=1);

namespace App\Ports\Out;

use App\Application\Audit\DTO\AuditLogTableQuery;
use App\Ports\Out\Shared\PaginatedResult;

interface AuditLogReaderPort
{
    /**
     * @return list<array{event:string,context:array<string,mixed>,created_at:string}>
     */
    public function findLatestNoteCorrections(string $noteId, int $limit = 10): array;

    /**
     * @return PaginatedResult<array{id:string,source:string,event:string,reason:string,actor_id:?string,actor_role:?string,entity_type:?string,entity_id:?string,bounded_context:?string,context:array<string,mixed>,context_json:string,created_at:string}>
     */
    public function listForAdmin(string $search = '', int $perPage = 20): PaginatedResult;

    public function tableForAdmin(AuditLogTableQuery $query): PaginatedResult;

    /** @return array<string,mixed>|null */
    public function findForAdmin(string $source, string $id): ?array;
}
