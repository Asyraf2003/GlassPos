<?php

declare(strict_types=1);

namespace App\Ports\Out\Procurement;

use DateTimeImmutable;

interface SupplierPaymentProofUploadIntentPort
{
    /**
     * @return array<string,mixed>|null
     */
    public function findForPrepare(
        string $actorId,
        string $scopeType,
        string $scopeId,
        string $idempotencyKey,
    ): ?array;

    /**
     * @return array<string,mixed>|null
     */
    public function findByIdForActor(string $uploadIntentId, string $actorId): ?array;

    /** @return array<string,mixed>|null */
    public function findByIdForActorForUpdate(string $uploadIntentId, string $actorId): ?array;

    /**
     * @param list<array{
     *   id:string,
     *   ordinal:int,
     *   staging_path:string,
     *   original_filename:string,
     *   declared_mime_type:string,
     *   declared_size_bytes:int
     * }> $files
     */
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
    ): bool;

    public function claimForFinalize(string $uploadIntentId, string $actorId): bool;

    public function releaseFinalizeClaim(string $uploadIntentId, string $actorId): bool;

    public function recordVerifiedFile(
        string $uploadIntentId,
        string $fileId,
        string $finalStoragePath,
        string $verifiedMimeType,
        int $verifiedSizeBytes,
    ): bool;

    public function clearVerifiedFiles(string $uploadIntentId): void;

    /** @param array<string,mixed> $resultPayload */
    public function markFinalized(
        string $uploadIntentId,
        string $actorId,
        array $resultPayload,
    ): bool;
}
