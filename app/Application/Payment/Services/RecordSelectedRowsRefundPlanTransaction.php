<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Application\Payment\DTO\SelectedRowsRefundPlan;
use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\IdempotencyClaimConflictException;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class RecordSelectedRowsRefundPlanTransaction
{
    public function __construct(
        private readonly RecordSelectedRowsRefundPlanExecutor $executor,
        private readonly TransactionManagerPort $transactions,
        private readonly RecordSelectedRowsRefundIdempotencyService $idempotency,
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
                $this->idempotency->start($idempotencyPayload);
            }

            $result = $this->executor->execute($plan, $refundedAt, $reason, $actorId, $actorRole);

            if ($idempotencyPayload !== null) {
                $this->idempotency->succeed($idempotencyPayload, $plan->noteId(), $result);
            }

            $this->transactions->commit();

            return $result;
        } catch (IdempotencyClaimConflictException $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            return $idempotencyPayload !== null ? ($this->idempotency->replay($idempotencyPayload) ?? throw $e) : throw $e;
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
