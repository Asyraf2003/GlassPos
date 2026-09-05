<?php

declare(strict_types=1);

namespace App\Adapters\Out\Audit;

use App\Application\Audit\DTO\AuditLogTableQuery;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class AuditLogAdminListQuery
{
    public function __construct(
        private readonly AuditLogAdminQueryFilters $filters = new AuditLogAdminQueryFilters,
        private readonly AuditLogAdminEntrySorter $sorter = new AuditLogAdminEntrySorter,
        private readonly AuditLogAdminRowsQuery $rows = new AuditLogAdminRowsQuery,
    ) {}

    public function paginate(AuditLogTableQuery $criteria, AuditLogAdminRowMapper $mapper): LengthAwarePaginator
    {
        $safePerPage = 20;
        $page = max(1, $criteria->page());
        $take = $page * $safePerPage;
        $legacyQuery = $criteria->source() === 'audit_events' ? null : $this->legacyQuery($criteria->q() ?? '');
        $eventQuery = $criteria->source() === 'audit_logs' ? null : $this->eventQuery($criteria->q() ?? '');
        $total = ($legacyQuery === null ? 0 : (clone $legacyQuery)->count())
            + ($eventQuery === null ? 0 : (clone $eventQuery)->count());
        $entries = $this->entries($legacyQuery, $eventQuery, $take, $mapper, $criteria);

        return (new LengthAwarePaginator(
            array_slice($entries, ($page - 1) * $safePerPage, $safePerPage),
            $total,
            $safePerPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()],
        ))->withQueryString();
    }

    private function legacyQuery(string $search): QueryBuilder
    {
        return $this->filters->applyLegacy(DB::table('audit_logs'), $search);
    }

    private function eventQuery(string $search): QueryBuilder
    {
        return $this->filters->applyEvent(DB::table('audit_events'), $search);
    }

    /**
     * @return list<array{id:string,source:string,event:string,reason:string,actor_id:?string,actor_role:?string,entity_type:?string,entity_id:?string,bounded_context:?string,context:array<string,mixed>,context_json:string,created_at:string}>
     */
    private function entries(
        ?QueryBuilder $legacyQuery,
        ?QueryBuilder $eventQuery,
        int $take,
        AuditLogAdminRowMapper $mapper,
        AuditLogTableQuery $criteria,
    ): array {
        $entries = [];

        if ($legacyQuery !== null) {
            foreach ($this->rows->legacyRows($legacyQuery, $take, $criteria) as $row) {
                $entries[] = $mapper->mapLegacy($row);
            }
        }

        if ($eventQuery !== null) {
            foreach ($this->rows->eventRows($eventQuery, $take, $criteria) as $row) {
                $entries[] = $mapper->mapEvent($row);
            }
        }

        $this->sorter->sort($entries, $criteria);

        return $entries;
    }
}
