<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note;

use App\Ports\Out\Note\NoteSurplusDispositionAuditTimelineReaderPort;

final class DatabaseNoteSurplusDispositionAuditTimelineReaderAdapter implements NoteSurplusDispositionAuditTimelineReaderPort
{
    public function __construct(
        private readonly DatabaseNoteSurplusRefundDueAuditTimelineRowsQuery $refundDueRows,
        private readonly DatabaseNoteSurplusRefundPaidAuditTimelineRowsQuery $refundPaidRows,
    ) {
    }

    public function findSurplusAuditEventsByNoteRootId(string $noteRootId, int $limit = 10): array
    {
        $rows = array_merge(
            $this->findRefundDueCreatedEventsByNoteRootId($noteRootId, $limit),
            $this->refundPaidRows->find($noteRootId, $limit),
        );

        usort(
            $rows,
            static fn (array $left, array $right): int => [
                $right['occurred_at'],
                $right['event_id'],
            ] <=> [
                $left['occurred_at'],
                $left['event_id'],
            ],
        );

        return array_slice($rows, 0, $limit);
    }

    public function findRefundDueCreatedEventsByNoteRootId(string $noteRootId, int $limit = 10): array
    {
        return $this->refundDueRows->find($noteRootId, $limit);
    }
}
