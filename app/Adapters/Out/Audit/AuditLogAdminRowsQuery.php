<?php

declare(strict_types=1);

namespace App\Adapters\Out\Audit;

use App\Application\Audit\DTO\AuditLogTableQuery;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class AuditLogAdminRowsQuery
{
    /**
     * @return iterable<int, object>
     */
    public function legacyRows(QueryBuilder $query, int $take, AuditLogTableQuery $criteria): iterable
    {
        $query = clone $query;
        $this->orderLegacy($query, $criteria);

        return $query->limit($take)
            ->get(['id', 'event', 'context', 'created_at']);
    }

    /**
     * @return iterable<int, object>
     */
    public function eventRows(QueryBuilder $query, int $take, AuditLogTableQuery $criteria): iterable
    {
        $query = clone $query;
        $this->orderEvent($query, $criteria);

        return $query
            ->limit($take)
            ->get($this->eventColumns());
    }

    private function orderLegacy(QueryBuilder $query, AuditLogTableQuery $criteria): void
    {
        $direction = $criteria->sortDir() === 'asc' ? 'asc' : 'desc';

        if ($criteria->sortBy() === 'relevance' && $criteria->q() !== null) {
            $keyword = mb_strtolower($criteria->q());
            $query->orderByRaw(
                "CASE WHEN LOWER(event) = ? THEN 0 WHEN LOWER(event) LIKE ? THEN 1 WHEN ? = 'audit_logs' THEN 3 WHEN LOWER(context) LIKE ? THEN 7 ELSE 8 END",
                [$keyword, $keyword.'%', $keyword, '%'.$keyword.'%'],
            );
        } elseif ($criteria->sortBy() === 'event') {
            $query->orderBy('event', $direction);
        } elseif (in_array($criteria->sortBy(), ['actor', 'entity'], true)) {
            $query->orderBy('context', $direction);
        } elseif ($criteria->sortBy() === 'source') {
            // A source is constant inside this query; chronology remains its deterministic tie-breaker.
        } else {
            $query->orderBy('created_at', $direction);
        }

        $query->orderByDesc('created_at')->orderByDesc('id');
    }

    private function orderEvent(QueryBuilder $query, AuditLogTableQuery $criteria): void
    {
        $direction = $criteria->sortDir() === 'asc' ? 'asc' : 'desc';

        if ($criteria->sortBy() === 'relevance' && $criteria->q() !== null) {
            $keyword = mb_strtolower($criteria->q());
            $query->orderByRaw(
                'CASE
                    WHEN LOWER(event_name) = ? THEN 0 WHEN LOWER(event_name) LIKE ? THEN 1
                    WHEN LOWER(aggregate_id) = ? THEN 2 WHEN LOWER(actor_id) = ? THEN 3
                    WHEN LOWER(source_channel) = ? OR LOWER(bounded_context) = ? OR LOWER(aggregate_type) = ? THEN 4
                    WHEN LOWER(reason) LIKE ? OR LOWER(actor_role) LIKE ? THEN 6
                    WHEN LOWER(metadata_json) LIKE ? THEN 7 ELSE 8 END',
                [$keyword, $keyword.'%', $keyword, $keyword, $keyword, $keyword, $keyword, '%'.$keyword.'%', '%'.$keyword.'%', '%'.$keyword.'%'],
            );
        } elseif ($criteria->sortBy() === 'event') {
            $query->orderBy('event_name', $direction);
        } elseif ($criteria->sortBy() === 'actor') {
            $query->orderBy('actor_id', $direction);
        } elseif ($criteria->sortBy() === 'entity') {
            $query->orderBy('aggregate_id', $direction);
        } elseif ($criteria->sortBy() === 'source') {
            // A source is constant inside this query; chronology remains its deterministic tie-breaker.
        } else {
            $query->orderBy('occurred_at', $direction);
        }

        $query->orderByDesc('occurred_at')->orderByDesc('id');
    }

    /**
     * @return list<string>
     */
    private function eventColumns(): array
    {
        return [
            'id', 'bounded_context', 'aggregate_type', 'aggregate_id', 'event_name', 'actor_id',
            'actor_role', 'reason', 'source_channel', 'metadata_json', 'occurred_at',
        ];
    }
}
