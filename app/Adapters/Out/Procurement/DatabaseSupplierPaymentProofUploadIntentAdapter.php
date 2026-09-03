<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Ports\Out\Procurement\SupplierPaymentProofUploadIntentPort;
use DateTimeImmutable;

final class DatabaseSupplierPaymentProofUploadIntentAdapter implements SupplierPaymentProofUploadIntentPort
{
    public function __construct(
        private readonly DatabaseSupplierPaymentProofUploadIntentReader $reader,
        private readonly DatabaseSupplierPaymentProofUploadIntentCreator $creator,
        private readonly DatabaseSupplierPaymentProofUploadIntentStateWriter $state,
    ) {
    }

    public function findForPrepare(
        string $actorId,
        string $scopeType,
        string $scopeId,
        string $idempotencyKey,
    ): ?array {
        return $this->reader->findForPrepare($actorId, $scopeType, $scopeId, $idempotencyKey);
    }

    public function findByIdForActor(string $uploadIntentId, string $actorId): ?array
    {
        return $this->reader->findByIdForActor($uploadIntentId, $actorId);
    }

    public function createPrepared(
        string $uploadIntentId,
        string $actorId,
        string $scopeType,
        string $scopeId,
        ?string $reservedSupplierPaymentId,
        string $idempotencyKey,
        string $requestHash,
        DateTimeImmutable $expiresAt,
        array $files,
    ): bool {
        return $this->creator->createPrepared(
            $uploadIntentId,
            $actorId,
            $scopeType,
            $scopeId,
            $reservedSupplierPaymentId,
            $idempotencyKey,
            $requestHash,
            $expiresAt,
            $files,
        );
    }

    public function claimForFinalize(string $uploadIntentId, string $actorId): bool
    {
        return $this->state->claimForFinalize($uploadIntentId, $actorId);
    }

    public function releaseFinalizeClaim(string $uploadIntentId, string $actorId): bool
    {
        return $this->state->releaseFinalizeClaim($uploadIntentId, $actorId);
    }

    public function recordVerifiedFile(
        string $uploadIntentId,
        string $fileId,
        string $finalStoragePath,
        string $verifiedMimeType,
        int $verifiedSizeBytes,
    ): bool {
        return $this->state->recordVerifiedFile(
            $uploadIntentId,
            $fileId,
            $finalStoragePath,
            $verifiedMimeType,
            $verifiedSizeBytes,
        );
    }

    public function markFinalized(
        string $uploadIntentId,
        string $actorId,
        array $resultPayload,
    ): bool {
        return $this->state->markFinalized($uploadIntentId, $actorId, $resultPayload);
    }
}
