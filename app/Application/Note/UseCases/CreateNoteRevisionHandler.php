<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\Services\CreateNoteRevisionIdempotencyService;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class CreateNoteRevisionHandler
{
    public function __construct(
        private readonly CreateNoteRevisionWorkflow $workflow,
        private readonly CreateNoteRevisionIdempotencyService $idempotency,
        private readonly TransactionManagerPort $transactions,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function handle(
        string $noteRootId,
        array $payload,
        ?string $actorId = null,
        bool $enforceWorkspaceEditability = true,
    ): CreateNoteRevisionResult {
        $started = false;
        $payload['_actor_id'] = trim((string) $actorId);

        $replayed = $this->idempotency->replay($payload);

        if ($replayed !== null) {
            return $replayed;
        }

        try {
            $this->transactions->begin();
            $started = true;
            $this->idempotency->start($payload);

            $result = $this->workflow->execute(
                $noteRootId,
                $payload,
                $actorId,
                $enforceWorkspaceEditability,
            );

            if ($result->isSuccess()) {
                $this->idempotency->succeed($payload, trim($noteRootId), $result);
            }

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
}
