<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Application\Note\Services\CancelSelectedRowsAndSyncActiveNoteTotal;
use App\Application\Note\Services\FinalizeRefundedNoteFromActiveRows;
use App\Application\Note\Services\NoteHistoryProjectionService;
use App\Application\Note\Services\SelectedNoteRowsRefundPlanResolver;
use App\Application\Payment\DTO\SelectedRowsRefundPlan;
use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\Note\NoteReaderPort;

final class RecordSelectedRowsRefundPlanExecutor
{
    public function __construct(
        private readonly RecordSelectedRowsRefundPlanBucketProcessor $buckets,
        private readonly CancelSelectedRowsAndSyncActiveNoteTotal $cancelRows,
        private readonly FinalizeRefundedNoteFromActiveRows $finalizeRefunded,
        private readonly RecordSelectedRowsRefundPlanAuditRecorder $audit,
        private readonly NoteHistoryProjectionService $projection,
        private readonly NoteReaderPort $notes,
        private readonly RecordSelectedRowsRefundPlanResultFactory $results,
        private readonly SelectedNoteRowsRefundPlanResolver $plans,
    ) {}

    public function execute(SelectedRowsRefundPlan $plan, string $refundedAt, string $reason, string $actorId, string $actorRole): Result
    {
        $note = $this->notes->getByIdForUpdate($plan->noteId());
        if ($note === null) {
            throw new DomainException('Nota tidak ditemukan.');
        }
        $note->assertNotCancelled();
        // Rebuild under the canonical root lock; a controller preview is not authority.
        $resolved = $this->plans->resolve($plan->noteId(), $plan->selectedRowIds(), $plan->stockReturns());
        if ($resolved->isFailure()) {
            throw new DomainException($resolved->message() ?? 'Refund plan sudah tidak valid.');
        }
        $plan = $resolved->data()['plan'];
        $processed = $this->buckets->process($plan, $refundedAt, $reason);
        $activeTotalRupiah = $this->notes->getById($plan->noteId())?->totalRupiah()->amount() ?? 0;
        $cancellableRowIds = $plan->cancellableRowIds();

        if ($cancellableRowIds !== []) {
            $canceled = $this->cancelRows->execute($plan->noteId(), $cancellableRowIds, $actorId, $actorRole, $reason);

            if ($canceled->isFailure()) {
                throw new DomainException($canceled->message() ?? 'Gagal membatalkan line refund.');
            }

            $activeTotalRupiah = (int) ($canceled->data()['active_total_rupiah'] ?? $activeTotalRupiah);
        }

        $finalized = Result::success(['note_id' => $plan->noteId(), 'note_state' => null, 'finalized' => false]);

        if ((int) $processed['allocation_count'] > 0) {
            $finalized = $this->finalizeRefunded->execute($plan->noteId(), $actorId, $actorRole, $reason);

            if ($finalized->isFailure()) {
                throw new DomainException($finalized->message() ?? 'Gagal finalisasi note refund.');
            }
        }

        $this->projection->syncNote($plan->noteId());
        $this->audit->record($plan, $actorId, $actorRole, $reason, $processed, $finalized->data());

        return $this->results->success($plan, $processed, $activeTotalRupiah);
    }
}
