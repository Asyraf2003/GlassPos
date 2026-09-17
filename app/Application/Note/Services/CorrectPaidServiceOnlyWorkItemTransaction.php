<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class CorrectPaidServiceOnlyWorkItemTransaction
{
    public function __construct(
        private readonly TransactionManagerPort $transactions,
        private readonly CorrectPaidServiceOnlyWorkItemMutation $mutation,
        private readonly CorrectPaidServiceOnlyWorkItemFinalizer $finalizer,
        private readonly NoteHistoryProjectionService $projection,
    ) {
    }

    public function run(
        string $noteId,
        int $lineNo,
        string $serviceName,
        int $servicePriceRupiah,
        string $partSource,
        string $reason,
        string $performedByActorId,
        string $baseRevisionId = ''
    ): Result {
        $started = false;

        try {
            $this->transactions->begin();
            $started = true;

            $context = $this->mutation->apply(
                $noteId,
                $lineNo,
                $serviceName,
                $servicePriceRupiah,
                $partSource,
                $reason,
                $performedByActorId,
                $baseRevisionId,
            );

            $refundReq = $this->finalizer->finalize(
                $performedByActorId,
                $lineNo,
                $reason,
                $context,
            );

            $this->projection->syncNote($context['after_note']->id());
            $this->transactions->commit();

            return Result::success(
                $this->finalizer->successPayload($context, $refundReq),
                'Correction nominal service_only berhasil disimpan.'
            );
        } catch (DomainException $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            if (str_starts_with($e->getMessage(), 'STALE_REVISION:')) {
                return Result::failure($e->getMessage(), ['revision' => ['STALE_REVISION']]);
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
