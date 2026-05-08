<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\Services\ApplyNoteRevisionAsActiveReplacement;
use App\Application\Note\Services\EditableWorkspaceNoteGuard;
use App\Application\Note\Services\NoteCurrentRevisionResolver;
use App\Application\Note\Services\NoteRevisionBootstrapFactory;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\ClockPort;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class CreateNoteRevisionHandler
{
    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly NoteCurrentRevisionResolver $current,
        private readonly NoteRevisionBootstrapFactory $factory,
        private readonly CreateNoteRevisionPayloadNoteBuilder $payloadNotes,
        private readonly CreateNoteRevisionCommitter $committer,
        private readonly ApplyNoteRevisionAsActiveReplacement $applier,
        private readonly EditableWorkspaceNoteGuard $guard,
        private readonly ClockPort $clock,
        private readonly TransactionManagerPort $transactions,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function handle(
        string $noteRootId,
        array $payload,
        ?string $actorId = null,
    ): CreateNoteRevisionResult {
        $started = false;

        try {
            $this->transactions->begin();
            $started = true;
            $result = $this->createRevisionAndApply($noteRootId, $payload, $actorId);
            $this->transactions->commit();

            return $result;
        } catch (DomainException $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            return CreateNoteRevisionResult::failure($e->getMessage());
        } catch (Throwable $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            throw $e;
        }
    }

    /** @param array<string, mixed> $payload */
    private function createRevisionAndApply(
        string $noteRootId,
        array $payload,
        ?string $actorId,
    ): CreateNoteRevisionResult {
        $root = $this->notes->getByIdForUpdate(trim($noteRootId));

        if ($root === null) {
            return CreateNoteRevisionResult::failure('Root note tidak ditemukan.');
        }

        $this->guard->assertEditable($root->id());

        $current = $this->current->resolveOrFail($root->id());
        $number = $this->current->nextRevisionNumber($root->id());
        $reason = (string) ($payload['reason'] ?? '');
        $replacement = $this->payloadNotes->build(
            $root->id(),
            $payload,
            $current,
            $root->workItems(),
        );

        $this->applier->apply($root, $replacement, $payload['items'] ?? []);

        $revision = $this->factory->createNextRevision(
            sprintf('%s-r%03d', $root->id(), $number),
            $current->id(),
            $number,
            $root,
            $actorId,
            $this->clock->now(),
            $reason,
        );

        return $this->committer->commit(
            $root->id(),
            $current->id(),
            $actorId,
            $reason,
            $revision,
        );
    }
}
