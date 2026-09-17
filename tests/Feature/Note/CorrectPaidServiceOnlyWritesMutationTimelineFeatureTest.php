<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\UseCases\CorrectPaidServiceOnlyWorkItemHandler;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CorrectPaidServiceOnlyWritesMutationTimelineFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_writes_native_mutation_event_and_before_after_snapshots(): void
    {
        DB::table('notes')->insert([
            'id' => 'note-1',
            'customer_name' => 'Budi',
            'transaction_date' => '2026-04-02',
            'total_rupiah' => 50000,
        ]);

        DB::table('work_items')->insert([
            'id' => 'wi-1',
            'note_id' => 'note-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 50000,
        ]);

        DB::table('work_item_service_details')->insert([
            'work_item_id' => 'wi-1',
            'service_name' => 'Servis Lama',
            'service_price_rupiah' => 50000,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);

        DB::table('customer_payments')->insert([
            'id' => 'pay-1',
            'amount_rupiah' => 50000,
            'paid_at' => '2026-04-02',
        ]);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-1',
            'customer_payment_id' => 'pay-1',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-1',
            'component_amount_rupiah_snapshot' => 50000,
            'allocated_amount_rupiah' => 50000,
            'allocation_priority' => 1,
        ]);

        $result = app(CorrectPaidServiceOnlyWorkItemHandler::class)->handle(
            'note-1',
            1,
            'Servis Baru',
            45000,
            ServiceDetail::PART_SOURCE_NONE,
            'Koreksi nominal',
            'actor-1',
            $this->revisionBaseForTest('note-1'),
        );

        $this->assertTrue($result->isSuccess());
        $this->assertDatabaseCount('note_mutation_events', 1);
        $this->assertDatabaseCount('note_mutation_snapshots', 2);

        $eventId = (string) DB::table('note_mutation_events')->value('id');

        $this->assertDatabaseHas('note_mutation_events', [
            'id' => $eventId,
            'note_id' => 'note-1',
            'mutation_type' => 'paid_service_only_work_item_corrected',
            'actor_id' => 'actor-1',
            'actor_role' => 'admin',
            'reason' => 'Koreksi nominal',
        ]);

        $this->assertDatabaseHas('note_mutation_snapshots', [
            'note_mutation_event_id' => $eventId,
            'snapshot_kind' => 'before',
        ]);

        $this->assertDatabaseHas('note_mutation_snapshots', [
            'note_mutation_event_id' => $eventId,
            'snapshot_kind' => 'after',
        ]);
    }
}
