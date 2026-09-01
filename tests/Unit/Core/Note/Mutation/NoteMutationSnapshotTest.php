<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Note\Mutation;

use App\Core\Note\Mutation\NoteMutationSnapshot;
use App\Core\Shared\Exceptions\DomainException;
use PHPUnit\Framework\TestCase;

final class NoteMutationSnapshotTest extends TestCase
{
    public function test_can_create_valid_before_snapshot(): void
    {
        $snapshot = NoteMutationSnapshot::create(
            'snapshot-1',
            'mutation-1',
            NoteMutationSnapshot::BEFORE,
            '{"note_id":"note-1","status":"before"}',
        );

        $this->assertSame('snapshot-1', $snapshot->id());
        $this->assertSame('mutation-1', $snapshot->noteMutationEventId());
        $this->assertSame(NoteMutationSnapshot::BEFORE, $snapshot->snapshotKind());
        $this->assertSame('{"note_id":"note-1","status":"before"}', $snapshot->payloadJson());
    }

    public function test_rejects_invalid_snapshot_kind(): void
    {
        $this->expectException(DomainException::class);

        NoteMutationSnapshot::create(
            'snapshot-1',
            'mutation-1',
            'middle',
            '{"note_id":"note-1"}',
        );
    }
}
