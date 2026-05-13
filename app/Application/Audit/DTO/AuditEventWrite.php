<?php

declare(strict_types=1);

namespace App\Application\Audit\DTO;

use DateTimeInterface;
use InvalidArgumentException;

final class AuditEventWrite
{
    /** @var array<string, mixed> */
    private readonly array $metadata;

    /** @var list<AuditEventSnapshotWrite> */
    private readonly array $snapshots;

    /**
     * @param array<string, mixed> $metadata
     * @param list<AuditEventSnapshotWrite> $snapshots
     */
    public function __construct(
        private readonly string $id,
        private readonly string $boundedContext,
        private readonly string $aggregateType,
        private readonly string $aggregateId,
        private readonly string $eventName,
        private readonly ?string $actorId,
        private readonly ?string $actorRole,
        private readonly ?string $reason,
        private readonly ?string $sourceChannel,
        private readonly ?string $requestId,
        private readonly ?string $correlationId,
        private readonly DateTimeInterface $occurredAt,
        array $metadata = [],
        array $snapshots = [],
    ) {
        foreach ([
            'id' => $this->id,
            'bounded_context' => $this->boundedContext,
            'aggregate_type' => $this->aggregateType,
            'aggregate_id' => $this->aggregateId,
            'event_name' => $this->eventName,
        ] as $field => $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException($field . ' is required.');
            }
        }

        $this->metadata = $metadata;
        $this->snapshots = $this->validatedSnapshots($snapshots);
    }

    public function id(): string
    {
        return trim($this->id);
    }

    public function boundedContext(): string
    {
        return trim($this->boundedContext);
    }

    public function aggregateType(): string
    {
        return trim($this->aggregateType);
    }

    public function aggregateId(): string
    {
        return trim($this->aggregateId);
    }

    public function eventName(): string
    {
        return trim($this->eventName);
    }

    public function actorId(): ?string
    {
        return $this->nullableTrim($this->actorId);
    }

    public function actorRole(): ?string
    {
        return $this->nullableTrim($this->actorRole);
    }

    public function reason(): ?string
    {
        return $this->nullableTrim($this->reason);
    }

    public function sourceChannel(): ?string
    {
        return $this->nullableTrim($this->sourceChannel);
    }

    public function requestId(): ?string
    {
        return $this->nullableTrim($this->requestId);
    }

    public function correlationId(): ?string
    {
        return $this->nullableTrim($this->correlationId);
    }

    public function occurredAt(): DateTimeInterface
    {
        return $this->occurredAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /**
     * @return list<AuditEventSnapshotWrite>
     */
    public function snapshots(): array
    {
        return $this->snapshots;
    }

    private function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param list<AuditEventSnapshotWrite> $snapshots
     * @return list<AuditEventSnapshotWrite>
     */
    private function validatedSnapshots(array $snapshots): array
    {
        $seen = [];

        foreach ($snapshots as $snapshot) {
            if (! $snapshot instanceof AuditEventSnapshotWrite) {
                throw new InvalidArgumentException('snapshots must contain AuditEventSnapshotWrite only.');
            }

            $kind = $snapshot->kind();

            if (isset($seen[$kind])) {
                throw new InvalidArgumentException('duplicate snapshot_kind: ' . $kind);
            }

            $seen[$kind] = true;
        }

        return array_values($snapshots);
    }
}
