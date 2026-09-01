<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Note;

use App\Core\Note\Note\Note;
use App\Core\Note\WorkItem\ExternalPurchaseLine;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class NoteMutationTest extends TestCase
{
    public function test_it_updates_note_header(): void
    {
        $note = Note::create(
            'note-1',
            'Pelanggan Lama',
            '08123',
            new DateTimeImmutable('2026-04-01'),
        );

        $note->updateHeader(
            'Pelanggan Baru',
            '08234',
            new DateTimeImmutable('2026-04-02'),
        );

        $this->assertSame('Pelanggan Baru', $note->customerName());
        $this->assertSame('08234', $note->customerPhone());
        $this->assertSame('2026-04-02', $note->transactionDate()->format('Y-m-d'));
    }

    public function test_it_replaces_work_items_and_recalculates_total(): void
    {
        $note = Note::create(
            'note-1',
            'Budi',
            null,
            new DateTimeImmutable('2026-04-01'),
        );

        $note->addWorkItem(
            WorkItem::createServiceOnly(
                'work-item-1',
                'note-1',
                1,
                ServiceDetail::create(
                    'Servis Lama',
                    Money::fromInt(50000),
                    ServiceDetail::PART_SOURCE_NONE,
                ),
            ),
        );

        $this->assertSame(50000, $note->totalRupiah()->amount());

        $note->replaceWorkItems([
            WorkItem::createServiceWithExternalPurchase(
                'work-item-2',
                'note-1',
                1,
                ServiceDetail::create(
                    'Servis Baru',
                    Money::fromInt(70000),
                    ServiceDetail::PART_SOURCE_NONE,
                ),
                [
                    ExternalPurchaseLine::create(
                        'external-line-1',
                        'Busi luar',
                        Money::fromInt(15000),
                        2,
                    ),
                ],
            ),
        ]);

        $this->assertCount(1, $note->workItems());
        $this->assertSame('work-item-2', $note->workItems()[0]->id());
        $this->assertSame(100000, $note->totalRupiah()->amount());
    }

    public function test_it_rejects_replacing_work_items_from_different_note(): void
    {
        $note = Note::create(
            'note-1',
            'Budi',
            null,
            new DateTimeImmutable('2026-04-01'),
        );

        $this->expectException(DomainException::class);

        $note->replaceWorkItems([
            WorkItem::createServiceOnly(
                'work-item-2',
                'note-2',
                1,
                ServiceDetail::create(
                    'Servis Salah Note',
                    Money::fromInt(70000),
                    ServiceDetail::PART_SOURCE_NONE,
                ),
            ),
        ]);
    }
}
