<?php

declare(strict_types=1);

namespace App\Adapters\Out\Audit;

use Illuminate\Database\Query\Builder as QueryBuilder;

final class AuditLogAdminQueryFilters
{
    public function applyLegacy(QueryBuilder $query, string $search): QueryBuilder
    {
        if ($search === '') {
            return $query;
        }

        $like = '%'.$search.'%';

        return $query->where(function (QueryBuilder $query) use ($like): void {
            $query
                ->where('event', 'like', $like)
                ->orWhere('context', 'like', $like);
        });
    }

    public function applyEvent(QueryBuilder $query, string $search): QueryBuilder
    {
        if ($search === '') {
            return $query;
        }

        $like = '%'.$search.'%';

        return $query->where(function (QueryBuilder $query) use ($like): void {
            $query
                ->where('event_name', 'like', $like)
                ->orWhere('bounded_context', 'like', $like)
                ->orWhere('aggregate_type', 'like', $like)
                ->orWhere('aggregate_id', 'like', $like)
                ->orWhere('actor_id', 'like', $like)
                ->orWhere('actor_role', 'like', $like)
                ->orWhere('reason', 'like', $like)
                ->orWhere('source_channel', 'like', $like)
                ->orWhere('metadata_json', 'like', $like);
        });
    }
}
