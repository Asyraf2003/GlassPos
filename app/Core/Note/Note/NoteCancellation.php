<?php

declare(strict_types=1);

namespace App\Core\Note\Note;

use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;

trait NoteCancellation
{
    public function cancel(): void
    {
        if ($this->isCancelled()) {
            throw new DomainException('NOTE_ALREADY_CANCELLED');
        }

        foreach ($this->workItems as $item) {
            $item->cancel();
        }

        $this->totalRupiah = Money::zero();
        $this->noteState = self::STATE_CANCELLED;
    }

    public function assertNotCancelled(): void
    {
        if ($this->isCancelled()) {
            throw new DomainException('NOTE_CANCELLED');
        }
    }

    public function beginRestoreAsAcceptedRevision(): void
    {
        if (! $this->isCancelled()) {
            throw new DomainException('NOTE_NOT_CANCELLED');
        }

        $this->noteState = self::STATE_OPEN;
    }
}
