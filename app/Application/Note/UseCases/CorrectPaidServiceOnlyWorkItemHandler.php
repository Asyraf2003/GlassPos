<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\Services\CorrectPaidServiceOnlyWorkItemTransaction;
use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;

final class CorrectPaidServiceOnlyWorkItemHandler
{
    public function __construct(
        private readonly CorrectPaidServiceOnlyWorkItemTransaction $transaction,
    ) {
    }

    public function handle(string $noteId, int $lineNo, string $serviceName, int $servicePriceRupiah, string $partSource, string $reason, string $performedByActorId, string $baseRevisionId = ''): Result
    {
        try {
            if ($lineNo <= 0) {
                throw new DomainException('Line number harus > 0.');
            }

            if (trim($reason) === '') {
                return Result::failure('Alasan correction wajib diisi.', ['correction' => ['AUDIT_REASON_REQUIRED']]);
            }

            if (trim($performedByActorId) === '') {
                throw new DomainException('Actor correction wajib ada.');
            }

            return $this->transaction->run(
                $noteId,
                $lineNo,
                $serviceName,
                $servicePriceRupiah,
                $partSource,
                $reason,
                $performedByActorId,
                $baseRevisionId,
            );
        } catch (DomainException $e) {
            return Result::failure($e->getMessage(), ['work_item' => ['INVALID_WORK_ITEM_STATE']]);
        }
    }
}
