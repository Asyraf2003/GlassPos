<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Note\Mutation;

use App\Core\Note\Mutation\NoteMutationEvent;
use App\Core\Shared\Exceptions\DomainException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class NoteMutationEventTest extends TestCase
{
    public function test_can_create_valid_note_mutation_event(): void
    {
        $event = NoteMutationEvent::create(
            'mutation-1',
            'note-1',
            'paid_note_corrected',
            'actor-1',
            'admin',
            'Perbaikan nominal jasa.',
            new DateTimeImmutable('2026-04-02 10:00:00'),
            'payment-1',
            'refund-1',
        );

        $this->assertSame('mutation-1', $event->id());
        $this->assertSame('note-1', $event->noteId());
        $this->assertSame('paid_note_corrected', $event->mutationType());
        $this->assertSame('actor-1', $event->actorId());
        $this->assertSame('admin', $event->actorRole());
        $this->assertSame('Perbaikan nominal jasa.', $event->reason());
        $this->assertSame('payment-1', $event->relatedCustomerPaymentId());
        $this->assertSame('refund-1', $event->relatedCustomerRefundId());
    }

    public function test_rejects_empty_reason(): void
    {
        $this->expectException(DomainException::class);

        NoteMutationEvent::create(
            'mutation-1',
            'note-1',
            'paid_note_corrected',
            'actor-1',
            'admin',
            '',
            new DateTimeImmutable('2026-04-02 10:00:00'),
        );
    }
}
