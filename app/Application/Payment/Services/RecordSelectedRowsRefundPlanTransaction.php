<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Application\Note\Services\CancelSelectedRowsAndSyncActiveNoteTotal;
use App\Application\Note\Services\FinalizeRefundedNoteFromActiveRows;
use App\Application\Note\Services\NoteHistoryProjectionService;
use App\Application\Payment\DTO\RecordedSelectedRowsRefundPlanResult;
use App\Application\Payment\DTO\SelectedRowsRefundPlan;
use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class RecordSelectedRowsRefundPlanTransaction
{
    public function __construct(
        private readonly RecordSelectedRowsRefundPlanBucketProcessor $buckets,
        private readonly CancelSelectedRowsAndSyncActiveNoteTotal $cancelRows,
        private readonly FinalizeRefundedNoteFromActiveRows $finalizeRefunded,
        private readonly TransactionManagerPort $transactions,
        private readonly RecordSelectedRowsRefundPlanAuditRecorder $audit,
        private readonly NoteHistoryProjectionService $projection,
        private readonly NoteReaderPort $notes,
    ) {}

    public function run(
        SelectedRowsRefundPlan $plan,
        string $refundedAt,
        string $reason,
        string $actorId,
        string $actorRole,
        ?array $idempotencyPayload = null,
    ): Result {
        $started = false;

        try {
            $this->transactions->begin();
            $started = true;

            if ($idempotencyPayload !== null) {
                app(RecordSelectedRowsRefundIdempotencyService::class)->start($idempotencyPayload);
            }

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

            $finalized = Result::success([
                'note_id' => $plan->noteId(),
                'note_state' => null,
                'finalized' => false,
            ]);

            if ((int) $processed['allocation_count'] > 0) {
                $finalized = $this->finalizeRefunded->execute($plan->noteId(), $actorId, $actorRole, $reason);
                if ($finalized->isFailure()) {
                    throw new DomainException($finalized->message() ?? 'Gagal finalisasi note refund.');
                }
            }

            $this->projection->syncNote($plan->noteId());
            $this->audit->record($plan, $actorId, $actorRole, $reason, $processed, $finalized->data());

            $result = Result::success(
                (new RecordedSelectedRowsRefundPlanResult(
                    $plan->noteId(),
                    $processed['refund_ids'],
                    $plan->selectedRowIds(),
                    $plan->unpaidRowIds(),
                    $plan->cancellableRowIds(),
                    (int) $processed['allocation_count'],
                    $plan->totalRefundRupiah(),
                    $activeTotalRupiah,
                ))->toArray(),
                'Refund selected rows berhasil dicatat.',
            );

            if ($idempotencyPayload !== null) {
                app(RecordSelectedRowsRefundIdempotencyService::class)
                    ->succeed($idempotencyPayload, $plan->noteId(), $result);
            }

            $this->transactions->commit();

            return $result;
        } catch (DomainException $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            return Result::failure($e->getMessage(), ['refund' => ['SELECTED_ROWS_REFUND_FAILED']]);
        } catch (Throwable $e) {
            if ($started) {
                $this->transactions->rollBack();
                }
                throw $e;
        }
    }
}
