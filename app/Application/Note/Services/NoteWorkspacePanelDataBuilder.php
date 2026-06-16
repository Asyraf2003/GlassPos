<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Note\Services\CurrentRevision\CurrentRevisionDetailRowMapper;
use App\Application\Note\Services\CurrentRevision\CurrentRevisionRowSettlementProjector;

final class NoteWorkspacePanelDataBuilder
{
    public function __construct(
        private readonly NoteCurrentRevisionResolver $currentRevision,
        private readonly CurrentRevisionRowSettlementProjector $settlements,
        private readonly CurrentRevisionDetailRowMapper $rows,
        private readonly NoteWorkspacePanelPayloadFactory $payloads,
    ) {
    }

    public function build(string $noteId): ?array
    {
        $normalized = trim($noteId);

        if ($normalized === '') {
            return null;
        }

        $revision = $this->currentRevision->resolveOrFail($normalized);
        $lines = $revision->lines();

        $rows = $this->rows->map(
            $lines,
            $this->settlements->build($revision->noteRootId(), $lines),
        );

        return $this->payloads->build(
            $revision->noteRootId(),
            $revision->customerName(),
            $revision->customerPhone(),
            $revision->transactionDate()->format('Y-m-d'),
            $revision->grandTotalRupiah(),
            $rows,
        );
    }
}
