<?php

declare(strict_types=1);

namespace App\Ports\Out\IdentityAccess;

interface UserAccessWriterPort
{
    public function findUserIdByEmail(string $email): ?string;

    public function createUser(string $name, string $email, string $password): string;

    public function createActorAccess(string $actorId, string $role): void;

    public function enableAdminTransactionCapability(string $actorId): void;

    public function enableAdminCashierAreaAccess(string $actorId): void;
}
