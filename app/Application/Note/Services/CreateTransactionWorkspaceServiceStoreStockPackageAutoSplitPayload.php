<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Shared\Exceptions\DomainException;

trait CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitPayload
{
    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function service(array $item): array
    {
        return is_array($item['service'] ?? null) ? $item['service'] : [];
    }

    private function requiredInt(mixed $value, string $message): int
    {
        if (! is_int($value) || $value <= 0) {
            throw new DomainException($message);
        }

        return $value;
    }
}
