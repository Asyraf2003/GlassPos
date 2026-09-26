<?php

declare(strict_types=1);

namespace App\Application\Note\Policies;

use App\Core\Note\Note\Note;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;

final class NoteAddabilityPolicy
{
    public function __construct(private readonly NotePaidStatusPolicy $paidStatus) {}

    public function assertAllowed(Note $note): void
    {
        $note->assertNotCancelled();
        if ($note->totalRupiah()->equals(Money::zero())) {
            return;
        }

        if ($note->isRefunded()) {
            throw new DomainException('Item baru tidak boleh ditambahkan ke note yang sudah refund.');
        }

        if ($this->paidStatus->isPaid($note)) {
            throw new DomainException('Item baru tidak boleh ditambahkan ke note yang sudah lunas.');
        }
    }
}
