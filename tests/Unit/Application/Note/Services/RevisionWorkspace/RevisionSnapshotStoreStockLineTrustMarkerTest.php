<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Note\Services\RevisionWorkspace;

use App\Application\Note\Services\RevisionWorkspace\RevisionSnapshotStoreStockLineKeyer;
use App\Application\Note\Services\RevisionWorkspace\RevisionSnapshotStoreStockLineTrustInventory;
use App\Application\Note\Services\RevisionWorkspace\RevisionSnapshotStoreStockLineTrustMarker;
use App\Core\Note\WorkItem\StoreStockLine;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class RevisionSnapshotStoreStockLineTrustMarkerTest extends TestCase
{
    public function test_it_marks_revision_snapshot_line_as_trusted_when_it_matches_current_server_work_item(): void
    {
        $marker = $this->marker();

        $items = [[
            'entry_mode' => 'product',
            'product_lines' => [[
                'product_id' => 'product-1',
                'qty' => 3,
                'unit_price_rupiah' => 100000,
                'price_basis' => 'revision_snapshot',
            ]],
        ]];

        $workItems = [
            WorkItem::createStoreStockSaleOnly(
                'wi-old-1',
                'note-1',
                1,
                [
                    StoreStockLine::create(
                        'ssl-old-1',
                        'product-1',
                        3,
                        Money::fromInt(300000)
                    ),
                ]
            ),
        ];

        $marked = $marker->mark($items, null, $workItems);

        $this->assertTrue($marked[0]['product_lines'][0]['_server_trusted_revision_snapshot']);
    }

    public function test_it_does_not_mark_forged_revision_snapshot_line_when_amount_does_not_match_current_server_work_item(): void
    {
        $marker = $this->marker();

        $items = [[
            'entry_mode' => 'product',
            'product_lines' => [[
                'product_id' => 'product-1',
                'qty' => 1,
                'unit_price_rupiah' => 1,
                'price_basis' => 'revision_snapshot',
            ]],
        ]];

        $workItems = [
            WorkItem::createStoreStockSaleOnly(
                'wi-old-1',
                'note-1',
                1,
                [
                    StoreStockLine::create(
                        'ssl-old-1',
                        'product-1',
                        3,
                        Money::fromInt(300000)
                    ),
                ]
            ),
        ];

        $marked = $marker->mark($items, null, $workItems);

        $this->assertFalse($marked[0]['product_lines'][0]['_server_trusted_revision_snapshot']);
    }


    private function marker(): RevisionSnapshotStoreStockLineTrustMarker
    {
        $keyer = new RevisionSnapshotStoreStockLineKeyer();

        return new RevisionSnapshotStoreStockLineTrustMarker(
            new RevisionSnapshotStoreStockLineTrustInventory($keyer),
            $keyer,
        );
    }
}
