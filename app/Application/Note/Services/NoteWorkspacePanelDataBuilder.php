<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Note\Services\CurrentRevision\CurrentRevisionDetailRowMapper;
use App\Application\Note\Services\CurrentRevision\CurrentRevisionRowSettlementProjector;
use App\Ports\Out\Note\NoteReaderPort;

final class NoteWorkspacePanelDataBuilder
{
    public function __construct(
        private readonly NoteCurrentRevisionResolver $currentRevision,
        private readonly NoteReaderPort $notes,
        private readonly CurrentRevisionRowSettlementProjector $settlements,
        private readonly CurrentRevisionDetailRowMapper $rows,
        private readonly NoteWorkspacePanelPayloadFactory $payloads,
    ) {}

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

        // Snapshot values remain history; cancelled roots have no current row rights.
        if ($this->notes->getById($normalized)?->isCancelled()) {
            $rows = array_map(static fn (array $row): array => array_replace($row, [
                'status' => 'canceled', 'line_status' => 'canceled', 'settlement_label' => 'Dibatalkan',
                'outstanding_rupiah' => 0, 'can_edit' => false, 'can_pay' => false,
                'can_refund' => false, 'can_correct_service_only' => false,
            ]), $rows);
        }

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
