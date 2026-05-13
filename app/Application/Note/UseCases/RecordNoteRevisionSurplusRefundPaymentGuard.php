<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\DTO\NoteRevisionSurplusRefundDueSource;
use App\Application\Note\DTO\NoteRevisionSurplusRefundPayment;
use App\Core\Shared\Exceptions\DomainException;

final class RecordNoteRevisionSurplusRefundPaymentGuard
{
    public function assertCommandAllowed(RecordNoteRevisionSurplusRefundPaymentCommand $command): string
    {
        if (trim($command->actorId) === '' || trim($command->actorRole) !== 'admin') {
            throw new DomainException('Surplus refund_paid hanya boleh dicatat oleh admin.');
        }

        $reason = trim($command->reason);

        if ($reason === '') {
            throw new DomainException('Alasan surplus refund_paid wajib diisi.');
        }

        if ($command->amountRupiah <= 0) {
            throw new DomainException('Nominal surplus refund_paid tidak valid.');
        }

        if (trim($command->idempotencyKey) === '') {
            throw new DomainException('Idempotency key surplus refund_paid wajib diisi.');
        }

        return $reason;
    }

    public function sourceOrFail(
        ?NoteRevisionSurplusRefundDueSource $source,
    ): NoteRevisionSurplusRefundDueSource {
        if ($source === null || $source->remainingRefundDueRupiah <= 0) {
            throw new DomainException('Source refund_due tidak valid atau sudah lunas.');
        }

        return $source;
    }

    public function assertAmountFits(
        int $amountRupiah,
        NoteRevisionSurplusRefundDueSource $source,
    ): void {
        if ($amountRupiah > $source->remainingRefundDueRupiah) {
            throw new DomainException('Nominal surplus refund_paid melebihi sisa refund_due.');
        }
    }

    public function assertRepeatedPayloadMatches(
        RecordNoteRevisionSurplusRefundPaymentCommand $command,
        NoteRevisionSurplusRefundPayment $existing,
    ): void {
        $sameAmount = $existing->amountRupiah === $command->amountRupiah;
        $sameEffectiveDate = $existing->effectiveDateString() === $command->effectiveDate->format('Y-m-d');

        if (! $sameAmount || ! $sameEffectiveDate) {
            throw new DomainException('Idempotency key surplus refund_paid sudah digunakan dengan payload berbeda.');
        }
    }
}
