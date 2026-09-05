<?php

declare(strict_types=1);

namespace App\Application\IdentityAccess\UseCases;

use App\Application\Audit\DTO\AuditEventSnapshotWrite;
use App\Application\Audit\DTO\AuditEventWrite;
use App\Application\Shared\DTO\Result;
use App\Core\IdentityAccess\Role\Role;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\ClockPort;
use App\Ports\Out\IdentityAccess\UserAccessWriterPort;
use App\Ports\Out\TransactionManagerPort;
use App\Ports\Out\UuidPort;
use InvalidArgumentException;
use Throwable;

final class CreateUserWithAccessHandler
{
    public function __construct(
        private readonly UserAccessWriterPort $users,
        private readonly AuditEventWriterPort $audit,
        private readonly ClockPort $clock,
        private readonly UuidPort $uuid,
        private readonly TransactionManagerPort $transactions,
    ) {}

    public function handle(
        string $name,
        string $email,
        string $password,
        string $role,
        ?string $performedByActorId = null,
        ?string $sourceChannel = null,
    ): Result {
        $normalizedEmail = mb_strtolower(trim($email));
        $existingId = $this->users->findUserIdByEmail($normalizedEmail);
        if ($existingId !== null) {
            return Result::success(['id' => $existingId, 'created' => false], 'User sudah ada.');
        }

        try {
            $actorRole = Role::fromString(trim($role));
        } catch (InvalidArgumentException $e) {
            return Result::failure($e->getMessage(), ['role' => ['UNSUPPORTED_ROLE']]);
        }

        $this->transactions->begin();

        try {
            $actorId = $this->users->createUser($name, $normalizedEmail, $password);
            $this->users->createActorAccess($actorId, $actorRole->value());

            if ($actorRole->isAdmin()) {
                $this->users->enableAdminTransactionCapability($actorId);
                $this->users->enableAdminCashierAreaAccess($actorId);
            }

            $snapshot = [
                'id' => $actorId,
                'name' => trim($name),
                'email' => $normalizedEmail,
                'role' => $actorRole->value(),
                'admin_transaction_capability' => $actorRole->isAdmin(),
                'admin_cashier_area_access' => $actorRole->isAdmin(),
            ];

            $this->audit->write(new AuditEventWrite(
                id: $this->uuid->generate(),
                boundedContext: 'identity_access',
                aggregateType: 'user_access',
                aggregateId: $actorId,
                eventName: 'user_access_created',
                actorId: $this->nullable($performedByActorId),
                actorRole: null,
                reason: null,
                sourceChannel: $this->nullable($sourceChannel),
                requestId: null,
                correlationId: null,
                occurredAt: $this->clock->now(),
                metadata: ['role' => $actorRole->value()],
                snapshots: [new AuditEventSnapshotWrite('after', $snapshot)],
            ));

            $this->transactions->commit();

            return Result::success($snapshot + ['created' => true], 'User berhasil dibuat.');
        } catch (Throwable $e) {
            $this->transactions->rollBack();
            throw $e;
        }
    }

    private function nullable(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
