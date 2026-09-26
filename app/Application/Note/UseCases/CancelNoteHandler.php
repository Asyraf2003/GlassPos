<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\Services\CancelNoteWorkflow;
use App\Application\Note\Services\NoteCancellationAccess;
use App\Application\Note\Services\NoteCancellationIdempotency;
use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\IdempotencyClaimConflictException;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class CancelNoteHandler
{
    public function __construct(
        private readonly NoteCancellationAccess $access,
        private readonly NoteCancellationIdempotency $idempotency,
        private readonly CancelNoteWorkflow $workflow,
        private readonly TransactionManagerPort $transactions,
    ) {}

    public function handle(string $noteId, string $actorId, array $input): Result
    {
        $payload = [
            '_note_root_id' => trim($noteId), '_actor_id' => trim($actorId),
            'base_revision_id' => trim((string) ($input['base_revision_id'] ?? '')),
            'idempotency_key' => trim((string) ($input['idempotency_key'] ?? '')),
            'reason' => trim((string) ($input['reason'] ?? '')),
        ];
        if (in_array('', [$payload['_note_root_id'], $payload['_actor_id'], $payload['base_revision_id'], $payload['idempotency_key'], $payload['reason']], true)) {
            return Result::failure('Data pembatalan belum lengkap.', ['cancellation' => ['INVALID_CANCELLATION_COMMAND']]);
        }
        try {
            $role = $this->access->authorize($noteId, $actorId);
        } catch (DomainException $e) {
            $code = in_array($e->getMessage(), ['CANCELLATION_DATE_FORBIDDEN', 'NOTE_NOT_FOUND'], true)
                ? $e->getMessage()
                : 'CANCELLATION_FORBIDDEN';

            return Result::failure($e->getMessage(), ['cancellation' => [$code]]);
        }
        $replay = $this->idempotency->replay($payload);
        if ($replay !== null) {
            return $replay;
        }
        $started = false;
        try {
            $this->transactions->begin();
            $started = true;
            $this->idempotency->start($payload);
            $result = $this->workflow->execute($payload, $role);
            $this->idempotency->succeed($payload, $result);
            $this->transactions->commit();

            return $result;
        } catch (IdempotencyClaimConflictException $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            return $this->idempotency->replay($payload) ?? throw $e;
        } catch (DomainException $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            return Result::failure($e->getMessage(), ['cancellation' => [$e->getMessage()]]);
        } catch (Throwable $e) {
            if ($started) {
                $this->transactions->rollBack();
            }
            throw $e;
        }
    }
}
