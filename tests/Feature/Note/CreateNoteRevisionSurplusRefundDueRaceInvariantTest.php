<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\DTO\NoteRevisionSurplusDisposition;
use App\Application\Note\DTO\NoteRevisionSurplusPending;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueAuditEventFactory;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueCommand;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueDispositionFactory;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueGuard;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueHandler;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueResultFactory;
use App\Application\Audit\DTO\AuditEventWrite;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\ClockPort;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionWriterPort;
use App\Ports\Out\TransactionManagerPort;
use App\Ports\Out\UuidPort;
use DateTimeImmutable;
use Tests\TestCase;

final class CreateNoteRevisionSurplusRefundDueRaceInvariantTest extends TestCase
{
    public function test_stale_pending_double_create_must_not_exceed_settlement_surplus(): void
    {
        $writer = new CapturingRefundDueWriterFake();
        $reader = new LockAwarePendingRefundDueReaderFake(122000, $writer);
        $handler = new CreateNoteRevisionSurplusRefundDueHandler(
            $reader,
            $writer,
            new NoopRefundDueAuditWriterFake(),
            new NoopRefundDueTransactionManagerFake(),
            new CreateNoteRevisionSurplusRefundDueGuard(),
            new CreateNoteRevisionSurplusRefundDueDispositionFactory(
                new SequenceRefundDueUuidPortFake([
                    'disposition-race-001',
                    'audit-race-001',
                    'disposition-race-002',
                    'audit-race-002',
                ]),
                new FixedRefundDueClockPortFake(new DateTimeImmutable('2026-05-13 10:00:00')),
            ),
            new CreateNoteRevisionSurplusRefundDueAuditEventFactory(),
            new CreateNoteRevisionSurplusRefundDueResultFactory(),
        );

        $first = $handler->handle($this->command('request-race-001', 80000));
        $second = $handler->handle($this->command('request-race-002', 80000));

        $this->assertTrue($first->isSuccess());
        $this->assertFalse(
            $second->isSuccess(),
            'Second stale refund_due creation must fail instead of allowing active disposition total to exceed settlement surplus.',
        );

        $this->assertLessThanOrEqual(122000, $writer->activeTotalRupiah());
    }

    private function command(string $requestId, int $amountRupiah): CreateNoteRevisionSurplusRefundDueCommand
    {
        return new CreateNoteRevisionSurplusRefundDueCommand(
            noteRevisionSettlementId: 'settlement-race-001',
            amountRupiah: $amountRupiah,
            reason: 'Race invariant proof.',
            actorId: 'admin-race-001',
            actorRole: 'admin',
            sourceChannel: 'web_admin',
            requestId: $requestId,
            correlationId: 'correlation-'.$requestId,
        );
    }
}

final class LockAwarePendingRefundDueReaderFake implements NoteRevisionSurplusDispositionReaderPort
{
    public function __construct(
        private readonly int $surplusRupiah,
        private readonly CapturingRefundDueWriterFake $writer,
    ) {
    }

    public function findPendingBySettlementId(string $settlementId): NoteRevisionSurplusPending
    {
        return $this->pendingWithActiveDisposition(0);
    }

    public function findPendingBySettlementIdForUpdate(string $settlementId): NoteRevisionSurplusPending
    {
        return $this->pendingWithActiveDisposition($this->writer->activeTotalRupiah());
    }

    public function findPendingByNoteRootId(string $noteRootId): array
    {
        return [];
    }

    public function sumActiveRefundDueAmountByNoteRootId(string $noteRootId): int
    {
        return $this->writer->activeTotalRupiah();
    }

    private function pendingWithActiveDisposition(int $activeDispositionRupiah): NoteRevisionSurplusPending
    {
        return NoteRevisionSurplusPending::create(
            'settlement-race-001',
            'note-root-race-001',
            'note-revision-race-001',
            $this->surplusRupiah,
            $activeDispositionRupiah,
        );
    }
}

final class CapturingRefundDueWriterFake implements NoteRevisionSurplusDispositionWriterPort
{
    /** @var list<NoteRevisionSurplusDisposition> */
    private array $dispositions = [];

    public function create(NoteRevisionSurplusDisposition $disposition): void
    {
        $this->dispositions[] = $disposition;
    }

    public function activeTotalRupiah(): int
    {
        return array_sum(array_map(
            static fn (NoteRevisionSurplusDisposition $disposition): int => $disposition->amountRupiah,
            $this->dispositions,
        ));
    }
}

final class NoopRefundDueAuditWriterFake implements AuditEventWriterPort
{
    public function write(AuditEventWrite $event): void
    {
    }
}

final class NoopRefundDueTransactionManagerFake implements TransactionManagerPort
{
    public function begin(): void
    {
    }

    public function commit(): void
    {
    }

    public function rollBack(): void
    {
    }
}

final class SequenceRefundDueUuidPortFake implements UuidPort
{
    /** @param list<string> $ids */
    public function __construct(private array $ids)
    {
    }

    public function generate(): string
    {
        return array_shift($this->ids) ?? 'generated-race-id';
    }
}

final class FixedRefundDueClockPortFake implements ClockPort
{
    public function __construct(private readonly DateTimeImmutable $now)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
