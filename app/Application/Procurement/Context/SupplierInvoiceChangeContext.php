<?php

declare(strict_types=1);

namespace App\Application\Procurement\Context;

final class SupplierInvoiceChangeContext
{
    private ?string $actorId = null;
    private ?string $actorRole = null;
    private ?string $sourceChannel = null;
    private ?string $reason = null;
    private ?string $sourceOperationId = null;

    public function set(
        ?string $actorId,
        ?string $actorRole,
        ?string $sourceChannel,
        ?string $reason = null,
        ?string $sourceOperationId = null,
    ): void {
        $this->actorId = $this->normalize($actorId);
        $this->actorRole = $this->normalize($actorRole);
        $this->sourceChannel = $this->normalize($sourceChannel);
        $this->reason = $this->normalize($reason);
        $this->sourceOperationId = $this->normalize($sourceOperationId);
    }

    /**
     * @return array{
     *     actor_id:?string,
     *     actor_role:?string,
     *     source_channel:?string,
     *     source_operation_id:?string,
     *     reason:?string
     * }
     */
    public function snapshot(): array
    {
        return [
            'actor_id' => $this->actorId,
            'actor_role' => $this->actorRole,
            'source_channel' => $this->sourceChannel,
            'reason' => $this->reason,
            'source_operation_id' => $this->sourceOperationId,
        ];
    }

    public function clear(): void
    {
        $this->actorId = null;
        $this->actorRole = null;
        $this->sourceChannel = null;
        $this->reason = null;
        $this->sourceOperationId = null;
    }

    private function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
