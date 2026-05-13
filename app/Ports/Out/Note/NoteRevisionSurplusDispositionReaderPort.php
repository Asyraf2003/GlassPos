<?php

declare(strict_types=1);

namespace App\Ports\Out\Note;

use App\Application\Note\DTO\NoteRevisionSurplusPending;

interface NoteRevisionSurplusDispositionReaderPort
{
    public function findPendingBySettlementId(string $settlementId): ?NoteRevisionSurplusPending;
}
