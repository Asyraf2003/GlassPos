<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Note;

use App\Core\Note\Note\Note;
use App\Core\Note\WorkItem\ExternalPurchaseLine;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\StoreStockLine;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class NoteTest extends TestCase
{
    public function test_it_adds_multiple_work_items_and_accumulates_total_exactly(): void
    {
        $note = Note::create(
            'note-1',
            'Budi Santoso',
            '08123456789',
            new DateTimeImmutable('2026-03-14'),
        );

        $serviceOnly = WorkItem::createServiceOnly(
            'work-item-1',
            'note-1',
            1,
            ServiceDetail::create(
                'Servis Karburator',
                Money::fromInt(50000),
                ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
            ),
        );

        $externalPurchase = WorkItem::createServiceWithExternalPurchase(
            'work-item-2',
            'note-1',
            2,
            ServiceDetail::create(
                'Servis Mesin',
                Money::fromInt(70000),
                ServiceDetail::PART_SOURCE_NONE,
            ),
            [
                ExternalPurchaseLine::create(
                    'external-line-1',
                    'Busi beli luar',
                    Money::fromInt(15000),
                    2,
                ),
                ExternalPurchaseLine::create(
                    'external-line-2',
                    'Kabel gas beli luar',
                    Money::fromInt(10000),
                    1,
                ),
            ],
        );

        $storeStockSaleOnly = WorkItem::createStoreStockSaleOnly(
            'work-item-3',
            'note-1',
            3,
            [
                StoreStockLine::create(
                    'store-line-1',
                    'product-1',
                    2,
                    Money::fromInt(40000),
                ),
            ],
        );

        $note->addWorkItem($serviceOnly);
        $note->addWorkItem($externalPurchase);
        $note->addWorkItem($storeStockSaleOnly);

        $this->assertCount(3, $note->workItems());
        $this->assertSame('08123456789', $note->customerPhone());
        $this->assertSame(200000, $note->totalRupiah()->amount());
    }

    public function test_it_rejects_duplicate_line_number_in_same_note(): void
    {
        $note = Note::create(
            'note-1',
            'Budi Santoso',
            null,
            new DateTimeImmutable('2026-03-14'),
        );

        $first = WorkItem::createServiceOnly(
            'work-item-1',
            'note-1',
            1,
            ServiceDetail::create(
                'Servis Karburator',
                Money::fromInt(50000),
                ServiceDetail::PART_SOURCE_NONE,
            ),
        );

        $duplicateLine = WorkItem::createServiceOnly(
            'work-item-2',
            'note-1',
            1,
            ServiceDetail::create(
                'Servis Tune Up',
                Money::fromInt(60000),
                ServiceDetail::PART_SOURCE_NONE,
            ),
        );

        $note->addWorkItem($first);

        $this->expectException(DomainException::class);

        $note->addWorkItem($duplicateLine);
    }

    public function test_it_rejects_rehydrate_when_total_is_inconsistent_with_work_item_subtotals(): void
    {
        $workItems = [
            WorkItem::createServiceOnly(
                'work-item-1',
                'note-1',
                1,
                ServiceDetail::create(
                    'Servis Karburator',
                    Money::fromInt(50000),
                    ServiceDetail::PART_SOURCE_NONE,
                ),
            ),
            WorkItem::createStoreStockSaleOnly(
                'work-item-2',
                'note-1',
                2,
                [
                    StoreStockLine::create(
                        'store-line-1',
                        'product-1',
                        2,
                        Money::fromInt(40000),
                    ),
                ],
            ),
        ];

        $this->expectException(DomainException::class);

        Note::rehydrate(
            'note-1',
            'Budi Santoso',
            null,
            new DateTimeImmutable('2026-03-14'),
            Money::fromInt(99999),
            $workItems,
        );
    }
}
