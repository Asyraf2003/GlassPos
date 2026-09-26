<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\Services\NoteCancellationAccess;
use App\Application\Note\Services\NoteRestoreIdempotency;
use App\Application\Note\Services\RestoreCancelledNoteWorkflow;
use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\IdempotencyClaimConflictException;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class RestoreCancelledNoteHandler
{
    public function __construct(
        private readonly NoteCancellationAccess $access,
        private readonly NoteRestoreIdempotency $idempotency,
        private readonly RestoreCancelledNoteWorkflow $workflow,
        private readonly TransactionManagerPort $transactions,
    ) {}

    public function handle(string $noteId, string $actorId, array $input): Result
    {
        $command = [
            '_note_root_id' => trim($noteId),
            '_actor_id' => trim($actorId),
            'base_revision_id' => trim((string) ($input['base_revision_id'] ?? '')),
            'cancellation_event_id' => trim((string) ($input['cancellation_event_id'] ?? '')),
            'source_revision_id' => trim((string) ($input['source_revision_id'] ?? '')),
            'idempotency_key' => trim((string) ($input['idempotency_key'] ?? '')),
            'reason' => trim((string) ($input['reason'] ?? '')),
        ];
        if (in_array('', [
            $command['_note_root_id'], $command['_actor_id'], $command['base_revision_id'],
            $command['cancellation_event_id'], $command['source_revision_id'],
            $command['idempotency_key'], $command['reason'],
        ], true)) {
            return Result::failure('Data pemulihan belum lengkap.', ['restore' => ['INVALID_RESTORE_COMMAND']]);
        }
        try {
            $role = $this->access->authorizeRestore($noteId, $actorId);
        } catch (DomainException $e) {
            $code = in_array($e->getMessage(), ['CANCELLATION_DATE_FORBIDDEN', 'NOTE_NOT_FOUND'], true)
                ? $e->getMessage()
                : 'CANCELLATION_FORBIDDEN';

            return Result::failure($e->getMessage(), ['restore' => [$code]]);
        }
        $replay = $this->idempotency->replay($command);
        if ($replay !== null) {
            return $replay;
        }
        $started = false;
        try {
            $this->transactions->begin();
            $started = true;
            $this->idempotency->start($command);
            $result = $this->workflow->execute($command, $role);
            $this->idempotency->succeed($command, $result);
            $this->transactions->commit();

            return $result;
        } catch (IdempotencyClaimConflictException $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            return $this->idempotency->replay($command) ?? throw $e;
        } catch (DomainException $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            return Result::failure($e->getMessage(), ['restore' => [$e->getMessage()]]);
        } catch (Throwable $e) {
            if ($started) {
                $this->transactions->rollBack();
            }
            throw $e;
        }
    }
}
