<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\Services\ApplyNoteRevisionAsActiveReplacement;
use App\Application\Note\Services\BuildCreateNoteRevisionSettlement;
use App\Application\Note\Services\CreateTransactionWorkspaceInlinePaymentRecorder;
use App\Application\Note\Services\EditableWorkspaceNoteGuard;
use App\Application\Note\Services\NoteCurrentRevisionResolver;
use App\Application\Note\Services\NoteRevisionBootstrapFactory;
use App\Ports\Out\ClockPort;
use App\Ports\Out\Note\NoteReaderPort;

final class CreateNoteRevisionWorkflow
{
    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly NoteCurrentRevisionResolver $current,
        private readonly NoteRevisionBootstrapFactory $factory,
        private readonly CreateNoteRevisionPayloadNoteBuilder $payloadNotes,
        private readonly CreateNoteRevisionCommitter $committer,
        private readonly ApplyNoteRevisionAsActiveReplacement $applier,
        private readonly BuildCreateNoteRevisionSettlement $settlements,
        private readonly CreateTransactionWorkspaceInlinePaymentRecorder $payments,
        private readonly CreateNoteRevisionPaymentResultFactory $paymentResults,
        private readonly EditableWorkspaceNoteGuard $guard,
        private readonly ClockPort $clock,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function execute(
        string $noteRootId,
        array $payload,
        ?string $actorId,
        bool $enforceWorkspaceEditability = true,
    ): CreateNoteRevisionResult {
        $root = $this->notes->getByIdForUpdate(trim($noteRootId));

        if ($root === null) {
            return CreateNoteRevisionResult::failure('Root note tidak ditemukan.');
        }

        if ($enforceWorkspaceEditability) {
            $this->guard->assertEditable($root->id());
        }

        $current = $this->current->resolveOrFail($root->id());
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

        $this->applier->apply($root, $replacement, $payload['items'] ?? []);
        $paymentSummary = $this->payments->record($root, $payload['inline_payment'] ?? []);

        $settlement = $this->settlements->build(
            sprintf('%s-settlement', $revisionId),
            $revisionId,
            $root->id(),
            $root->totalRupiah()->amount(),
            $createdAt,
        );

        $revision = $this->factory->createNextRevision(
            $revisionId,
            $current->id(),
            $number,
            $root,
            $actorId,
            $createdAt,
            $reason,
        );

        $result = $this->committer->commit(
            $root->id(),
            $current->id(),
            $actorId,
            $reason,
            $revision,
            $settlement,
        );

        return $this->paymentResults->withPaymentSummary($result, $paymentSummary);
    }
}
