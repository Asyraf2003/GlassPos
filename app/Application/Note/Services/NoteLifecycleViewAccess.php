<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Note\Policies\CashierNoteAccessGuard;
use App\Core\Note\Note\Note;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\ClockPort;
use App\Ports\Out\IdentityAccess\ActorAccessReaderPort;
use App\Ports\Out\IdentityAccess\AdminTransactionCapabilityStatePort;

final class NoteLifecycleViewAccess
{
    public function __construct(
        private readonly ActorAccessReaderPort $actors,
        private readonly AdminTransactionCapabilityStatePort $capabilities,
        private readonly CashierNoteAccessGuard $dates,
        private readonly ClockPort $clock,
    ) {}

    public function allowed(Note $note, string $actorId): bool
    {
        $actor = $this->actors->findByActorId($actorId);
        if ($actor?->isAdmin()) {
            return $this->capabilities->getByActorId($actorId)->isActive();
        }
        if (! $actor?->isKasir()) {
            return false;
        }
        try {
            $this->dates->assertCanView($note, $this->clock->now());

            return true;
        } catch (DomainException) {
            return false;
        }
    }
}
