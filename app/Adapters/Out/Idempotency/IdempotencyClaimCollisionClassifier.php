<?php

declare(strict_types=1);

namespace App\Adapters\Out\Idempotency;

use Illuminate\Database\UniqueConstraintViolationException;

final class IdempotencyClaimCollisionClassifier
{
    public function matches(UniqueConstraintViolationException $exception): bool
    {
        $info = $exception->errorInfo ?? [];
        $state = (string) ($info[0] ?? '');
        $message = (string) ($info[2] ?? '');

        if ($state === '23000' && (int) ($info[1] ?? 0) === 1062) {
            return preg_match("/for key ['`](?:idempotency_records\\.)?idempotency_records_scope_key_unique['`](?:$|\\s)/i", $message) === 1;
        }

        return $state === '23505'
            && preg_match('/unique constraint "idempotency_records_scope_key_unique"(?:$|\\s)/i', $message) === 1;
    }
}
