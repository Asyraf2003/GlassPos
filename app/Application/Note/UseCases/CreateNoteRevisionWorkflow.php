<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\Services\ApplyNoteRevisionAsActiveReplacement;
use App\Application\Note\Services\CreateTransactionWorkspaceInlinePaymentRecorder;
use App\Application\Note\Services\EditableWorkspaceNoteGuard;
use App\Application\Note\Services\NoteCurrentRevisionResolver;
use App\Application\Note\Services\NoteHistoryProjectionService;
use App\Application\Note\Services\NoteRevisionBootstrapFactory;
use App\Application\Note\Services\ReopenNoteForRevisionOutstanding;
use App\Core\Note\Revision\NoteRevision;
use App\Ports\Out\ClockPort;
use App\Ports\Out\Note\NoteReaderPort;

final class CreateNoteRevisionWorkflow
{
    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly NoteCurrentRevisionResolver $current,
        private readonly NoteRevisionBootstrapFactory $factory,
        private readonly CreateNoteRevisionPayloadNoteBuilder $payloadNotes,
        private readonly CreateNoteRevisionSettlementCommitter $settlementCommits,
        private readonly ApplyNoteRevisionAsActiveReplacement $applier,
        private readonly ReopenNoteForRevisionOutstanding $reopen,
        private readonly CreateTransactionWorkspaceInlinePaymentRecorder $payments,
        private readonly CreateNoteRevisionPaymentResultFactory $paymentResults,
        private readonly EditableWorkspaceNoteGuard $guard,
        private readonly NoteHistoryProjectionService $projection,
        private readonly ClockPort $clock,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(
        string $noteRootId,
        array $payload,
        ?string $actorId,
        bool $enforceWorkspaceEditability = true,
        ?NoteRevision $trustedSnapshot = null,
    ): CreateNoteRevisionResult {
        $root = $this->notes->getByIdForUpdate(trim($noteRootId));

        if ($root === null) {
            return CreateNoteRevisionResult::failure('Root note tidak ditemukan.');
        }
        $root->assertNotCancelled();

        $current = $this->current->resolveOrFail($root->id());
        if (trim((string) ($payload['base_revision_id'] ?? '')) !== $current->id()) {
            return CreateNoteRevisionResult::failure('STALE_REVISION: Nota telah berubah. Muat ulang editor sebelum menyimpan.', ['code' => 'STALE_REVISION']);
        }

        if ($enforceWorkspaceEditability) {
            $this->guard->assertEditable($root->id());
        }

        $number = $this->current->nextRevisionNumber($root->id());
        $reason = (string) ($payload['reason'] ?? '');
        $replacement = $this->payloadNotes->build(
            $root->id(),
            $payload,
            $current,
            $root->workItems(),
        );

        $revisionId = sprintf('%s-r%03d', $root->id(), $number);
        $createdAt = $this->clock->now();
        $this->applier->apply($root, $replacement, $payload['items'] ?? [], $trustedSnapshot);
        $this->reopen->reopenIfNeeded($root, $revisionId, $actorId, $reason, $createdAt);
        $paymentSummary = $this->payments->record($root, $payload['inline_payment'] ?? []);

        $revision = $this->factory->createNextRevision(
            $revisionId,
            $current->id(),
            $number,
            $root,
            $actorId,
            $createdAt,
            $reason,
        );

        $result = $this->settlementCommits->commit(
            $revisionId,
            $root->id(),
            $current->id(),
            $root->totalRupiah()->amount(),
            $actorId,
            $reason,
            $revision,
            $payload,
            $createdAt,
        );

        $this->projection->syncNote($root->id());

        return $this->paymentResults->withPaymentSummary($result, $paymentSummary);
    }
}
