<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\Policies\NotePaidStatusPolicy;
use App\Application\Note\Services\NoteCorrectionSnapshotBuilder;
use App\Application\Note\Services\NoteHistoryProjectionService;
use App\Application\Note\Services\WorkItemStatusTransitionService;
use App\Application\Shared\DTO\Result;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Note\WorkItemWriterPort;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class CorrectPaidWorkItemStatusHandler
{
    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly WorkItemWriterPort $workItems,
        private readonly TransactionManagerPort $transactions,
        private readonly WorkItemStatusTransitionService $transition,
        private readonly NotePaidStatusPolicy $paidStatus,
        private readonly NoteCorrectionSnapshotBuilder $snapshots,
        private readonly AuditLogPort $audit,
        private readonly NoteHistoryProjectionService $projection,
    ) {}

    public function handle(string $nId, int $line, string $status, string $reason, string $actorId): Result
    {
        $started = false;
        try {
            if ($line <= 0) {
                throw new DomainException('Line number harus > 0.');
            }
            $reason = trim($reason);
            $actorId = trim($actorId);
            if ($reason === '') {
                return Result::failure('Alasan wajib diisi.', ['correction' => ['AUDIT_REASON_REQUIRED']]);
            }
            if ($actorId === '') {
                throw new DomainException('Actor wajib ada.');
            }

            $this->transactions->begin();
            $started = true;
            $note = $this->notes->getById(trim($nId)) ?? throw new DomainException('Note tidak ditemukan.');
            $this->paidStatus->assertPaidForCorrection($note);

            if (trim($status) === WorkItem::STATUS_CANCELED) {
                throw new DomainException('Work item yang sudah lunas harus dibalik melalui refund, bukan correction status batal.');
            }

            $before = $this->snapshots->build($note);
            $item = $this->transition->findAndApply($note, $line, trim($status));
            $this->workItems->updateStatus($item);

            $afterNote = $this->notes->getById($note->id()) ?? throw new DomainException('Note tidak ditemukan (after).');
            $after = $this->snapshots->build($afterNote);

            $this->audit->record('paid_work_item_status_corrected', [
                'performed_by_actor_id' => $actorId,
                'note_id' => $note->id(),
                'line_no' => $line,
                'target_status' => $item->status(),
                'reason' => $reason,
                'before' => $before,
                'after' => $after,
            ]);

            $this->projection->syncNote($afterNote->id());

            $this->transactions->commit();
            return Result::success([
                'note' => ['id' => $afterNote->id()],
                'work_item' => ['id' => $item->id(), 'status' => $item->status()],
            ], 'Correction status work item berhasil disimpan.');
        } catch (DomainException $e) {
            if ($started) {
                $this->transactions->rollBack();
            }
            return Result::failure($e->getMessage(), ['work_item' => ['INVALID_WORK_ITEM_STATE']]);
        } catch (Throwable $e) {
            if ($started) {
                $this->transactions->rollBack();
            }
            throw $e;
        }
    }
}
