<?php

declare(strict_types=1);

namespace App\Ports\Out\Audit;

use DateTimeImmutable;

interface AuditOutboxProcessorPort
{
    /** @return list<object> */
    public function eligible(int $limit, bool $retryFailed, int $maxAttempts, DateTimeImmutable $now): array;

    public function claim(string $rowId, string $status, DateTimeImmutable $now): ?object;

    public function markProcessed(string $rowId, DateTimeImmutable $now): void;

    public function recordFailure(string $rowId, string $message, int $maxAttempts, DateTimeImmutable $now): void;
}
