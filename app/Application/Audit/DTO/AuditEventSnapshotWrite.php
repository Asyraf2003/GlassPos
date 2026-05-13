<?php

declare(strict_types=1);

namespace App\Application\Audit\DTO;

use InvalidArgumentException;

final class AuditEventSnapshotWrite
{
    /** @var array<string, mixed> */
    private readonly array $payload;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly string $kind,
        array $payload,
    ) {
        if (trim($this->kind) === '') {
            throw new InvalidArgumentException('snapshot_kind is required.');
        }

        $this->payload = $payload;
    }

    public function kind(): string
    {
        return trim($this->kind);
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }
}
