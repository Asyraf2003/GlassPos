<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\Mutation\NoteMutationEvent;
use App\Core\Note\Mutation\NoteMutationSnapshot;
use App\Ports\Out\Note\NoteMutationEventWriterPort;
use App\Ports\Out\Note\NoteMutationSnapshotWriterPort;
use App\Ports\Out\UuidPort;
use DateTimeImmutable;
use JsonException;

final class PersistNoteMutationTimeline
{
    public function __construct(
        private readonly NoteMutationEventWriterPort $events,
        private readonly NoteMutationSnapshotWriterPort $snapshots,
        private readonly UuidPort $uuid,
    ) {}

    public function record(
        string $noteId,
        string $mutationType,
        string $actorId,
        string $actorRole,
        string $reason,
        DateTimeImmutable $occurredAt,
        array $before,
        array $after,
        ?string $relatedCustomerPaymentId = null,
        ?string $relatedCustomerRefundId = null,
        array $metadata = [],
    ): string {
        $event = NoteMutationEvent::create(
            $this->uuid->generate(),
            $noteId,
            $mutationType,
            $actorId,
            $actorRole,
            $reason,
            $occurredAt,
            $relatedCustomerPaymentId,
            $relatedCustomerRefundId,
        );

        $this->events->create($event);
        $this->snapshots->createMany([
            NoteMutationSnapshot::create($this->uuid->generate(), $event->id(), NoteMutationSnapshot::BEFORE, $this->encode($this->withMeta($before, $metadata))),
            NoteMutationSnapshot::create($this->uuid->generate(), $event->id(), NoteMutationSnapshot::AFTER, $this->encode($this->withMeta($after, $metadata))),
        ]);

        return $event->id();
    }

    private function withMeta(array $payload, array $metadata): array
    {
        if ($metadata === []) {
            return $payload;
        }

        $payload['meta'] = $metadata;

        return $payload;
    }

    private function encode(array $payload): string
    {
        try {
            return json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return '{}';
        }
    }
}
