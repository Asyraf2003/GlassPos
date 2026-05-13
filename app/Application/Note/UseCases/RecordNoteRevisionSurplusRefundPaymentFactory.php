<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\DTO\NoteRevisionSurplusRefundDueSource;
use App\Application\Note\DTO\NoteRevisionSurplusRefundPayment;
use App\Ports\Out\ClockPort;
use App\Ports\Out\UuidPort;

final class RecordNoteRevisionSurplusRefundPaymentFactory
{
    public function __construct(
        private readonly UuidPort $uuid,
        private readonly ClockPort $clock,
    ) {
    }

    public function create(
        RecordNoteRevisionSurplusRefundPaymentCommand $command,
        NoteRevisionSurplusRefundDueSource $source,
    ): NoteRevisionSurplusRefundPayment {
        $occurredAt = $command->occurredAt ?? $this->clock->now();

        return NoteRevisionSurplusRefundPayment::create(
            $this->uuid->generate(),
            $source->dispositionId,
            $source->noteRevisionSettlementId,
            $source->noteRootId,
            $source->noteRevisionId,
            $command->amountRupiah,
            $command->effectiveDate,
            $occurredAt,
            NoteRevisionSurplusRefundPayment::STATUS_ACTIVE,
            $command->idempotencyKey,
            $this->uuid->generate(),
            $this->clock->now(),
        );
    }
}
