<?php

declare(strict_types=1);

namespace App\Adapters\Out\Audit;

use App\Application\Audit\DTO\AuditLogTableQuery;

final class AuditLogAdminEntrySorter
{
    /**
     * @param  list<array{id:string,source:string,event:string,reason:string,actor_id:?string,actor_role:?string,entity_type:?string,entity_id:?string,bounded_context:?string,context:array<string,mixed>,context_json:string,created_at:string}>  $entries
     */
    public function sort(array &$entries, AuditLogTableQuery $criteria): void
    {
        usort($entries, function (array $left, array $right) use ($criteria): int {
            if ($criteria->sortBy() === 'relevance' && $criteria->q() !== null) {
                $rank = $this->rank($left, $criteria->q()) <=> $this->rank($right, $criteria->q());
                if ($rank !== 0) {
                    return $rank;
                }
            } else {
                $field = match ($criteria->sortBy()) {
                    'event' => 'event', 'source' => 'source', 'actor' => 'actor_id', 'entity' => 'entity_id', default => 'created_at',
                };
                $comparison = strcmp((string) ($left[$field] ?? ''), (string) ($right[$field] ?? ''));
                if ($comparison !== 0) {
                    return $criteria->sortDir() === 'asc' ? $comparison : -$comparison;
                }
            }

            $time = strcmp($right['created_at'], $left['created_at']);

            if ($time !== 0) {
                return $time;
            }

            return strcmp(
                $right['source'].':'.$right['id'],
                $left['source'].':'.$left['id'],
            );
        });
    }

    private function rank(array $entry, string $search): int
    {
        $needle = mb_strtolower($search);
        $event = mb_strtolower($entry['event']);
        if ($event === $needle) {
            return 0;
        }
        if (str_starts_with($event, $needle)) {
            return 1;
        }
        if (mb_strtolower((string) ($entry['entity_id'] ?? '')) === $needle) {
            return 2;
        }
        if (mb_strtolower((string) ($entry['actor_id'] ?? '')) === $needle) {
            return 3;
        }

        foreach (['source', 'bounded_context', 'entity_type'] as $field) {
            if (mb_strtolower((string) ($entry[$field] ?? '')) === $needle) {
                return 4;
            }
        }

        foreach (['reason', 'actor_role'] as $field) {
            if (str_contains(mb_strtolower((string) ($entry[$field] ?? '')), $needle)) {
                return 6;
            }
        }

        return 7;
    }
}
