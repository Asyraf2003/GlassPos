<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\UseCases\AddWorkItemHandler;
use App\Application\Shared\DTO\Result;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AddServiceOnlyWorkItemFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_service_only_work_item_handler_stores_work_item_and_updates_note_total(): void
    {
        $this->loginAsKasir();
        DB::table('notes')->insert([
            'id' => 'note-1',
            'customer_name' => 'Budi Santoso',
            'transaction_date' => '2026-03-14',
            'total_rupiah' => 0,
        ]);

        $handler = app(AddWorkItemHandler::class);

        $result = $handler->handle(
            'note-1',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            [
                'service_name' => 'Servis Karburator',
                'service_price_rupiah' => 50000,
                'part_source' => ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
            ],
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'total_rupiah' => 50000,
        ]);

        $this->assertDatabaseHas('work_items', [
            'note_id' => 'note-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 50000,
        ]);

        $workItem = DB::table('work_items')
            ->where('note_id', 'note-1')
            ->where('line_no', 1)
            ->first();

        $this->assertNotNull($workItem);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => (string) $workItem->id,
            'service_name' => 'Servis Karburator',
            'service_price_rupiah' => 50000,
            'part_source' => ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
        ]);

        $this->assertDatabaseCount('work_item_external_purchase_lines', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_add_service_only_work_item_handler_rejects_duplicate_line_no_in_same_note(): void
    {
        $this->loginAsKasir();
        DB::table('notes')->insert([
            'id' => 'note-1',
            'customer_name' => 'Budi Santoso',
            'transaction_date' => '2026-03-14',
            'total_rupiah' => 30000,
        ]);

        DB::table('work_items')->insert([
            'id' => 'work-item-1',
            'note_id' => 'note-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 30000,
        ]);

        DB::table('work_item_service_details')->insert([
            'work_item_id' => 'work-item-1',
            'service_name' => 'Servis Ringan',
            'service_price_rupiah' => 30000,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);

        $handler = app(AddWorkItemHandler::class);

        $result = $handler->handle(
            'note-1',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            [
                'service_name' => 'Servis Karburator',
                'service_price_rupiah' => 50000,
                'part_source' => ServiceDetail::PART_SOURCE_NONE,
            ],
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isFailure());

        $this->assertDatabaseCount('work_items', 1);
        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'total_rupiah' => 30000,
        ]);
    }
    public function test_work_items_reject_duplicate_line_no_for_same_note(): void
    {
        DB::table('notes')->insert([
            'id' => 'note-line-race-1',
            'customer_name' => 'Budi Santoso',
            'transaction_date' => '2026-03-14',
            'total_rupiah' => 0,
        ]);

        DB::table('work_items')->insert([
            'id' => 'work-item-line-race-1',
            'note_id' => 'note-line-race-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 30000,
        ]);

        try {
            DB::table('work_items')->insert([
                'id' => 'work-item-line-race-2',
                'note_id' => 'note-line-race-1',
                'line_no' => 1,
                'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
                'status' => WorkItem::STATUS_OPEN,
                'subtotal_rupiah' => 50000,
            ]);
        } catch (QueryException) {
            // Expected: database invariant rejects duplicate line number for the same note.
        }

        $this->assertSame(
            1,
            DB::table('work_items')
                ->where('note_id', 'note-line-race-1')
                ->where('line_no', 1)
                ->count(),
            'Work item line number must be unique per note to prevent duplicate row numbers under concurrent add-row flows.'
        );
    }

}
