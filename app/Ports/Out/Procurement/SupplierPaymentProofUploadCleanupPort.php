<?php

declare(strict_types=1);

namespace App\Ports\Out\Procurement;

use DateTimeImmutable;

interface SupplierPaymentProofUploadCleanupPort
{
    /** @return list<array<string,mixed>> */
    public function findExpiredOrStale(
        DateTimeImmutable $expiredAt,
        DateTimeImmutable $staleFinalizingAt,
        int $limit,
    ): array;

    public function claimForCleanup(
        string $uploadIntentId,
        DateTimeImmutable $expiredAt,
        DateTimeImmutable $staleFinalizingAt,
    ): bool;

    public function releaseCleanupClaim(string $uploadIntentId): void;

    public function markCleanupCompleted(string $uploadIntentId): bool;
}
