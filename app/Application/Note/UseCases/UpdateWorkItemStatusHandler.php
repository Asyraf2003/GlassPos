<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\Policies\NotePaidStatusPolicy;
use App\Application\Note\Services\WorkItemStatusTransitionService;
use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Note\WorkItemWriterPort;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class UpdateWorkItemStatusHandler
{
    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly WorkItemWriterPort $workItems,
        private readonly TransactionManagerPort $transactions,
        private readonly WorkItemStatusTransitionService $transition,
        private readonly NotePaidStatusPolicy $paidStatus,
        private readonly AuditLogPort $audit
    ) {}

    public function handle(string $noteId, int $lineNo, string $targetStatus): Result
    {
        $started = false;

        try {
            if ($lineNo <= 0) {
                throw new DomainException('Line number harus > 0.');
            }

            $this->transactions->begin();
            $started = true;

            $note = $this->notes->getByIdForUpdate(trim($noteId))
                ?? throw new DomainException('Note tidak ditemukan.');
            $note->assertNotCancelled();

            $this->paidStatus->assertNotPaidForStandardMutation($note);

            $workItem = $this->transition->findAndApply($note, $lineNo, trim($targetStatus));

            $this->workItems->updateStatus($workItem);

            $this->audit->record('work_item_status_updated', [
                'note_id' => $note->id(),
                'line_no' => $lineNo,
                'new_status' => $workItem->status(),
            ]);

            $this->transactions->commit();

            return Result::success(
                $this->mapResponse($note, $workItem),
                'Status work item diperbarui.',
            );
        } catch (DomainException $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            return Result::failure(
                $e->getMessage(),
                ['work_item' => ['INVALID_WORK_ITEM_STATE']],
            );
        } catch (Throwable $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            throw $e;
        }
    }

    private function mapResponse($note, $workItem): array
    {
        return [
            'note' => [
                'id' => $note->id(),
            ],
            'work_item' => [
                'id' => $workItem->id(),
                'status' => $workItem->status(),
            ],
        ];
    }
}
