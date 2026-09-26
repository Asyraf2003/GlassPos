<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Reporting\Queries\TransactionSummaryReportingQuery;
use App\Application\Note\Services\NoteOutstandingPaymentAmountResolver;
use App\Application\Note\UseCases\CreateNoteRevisionHandler;
use App\Ports\Out\Note\NoteCorrectionHistoryReaderPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

final class WholeNoteRestoreFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_restore_creates_a_new_current_revision_and_fresh_stock_effects(): void
    {
        $this->preparePrimitiveFixture();
        $cashier = $this->loginAsKasir();
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace([$this->primitiveItems()[0]], 'restore-create'))
            ->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $sourceRevisionId = (string) DB::table('notes')->value('current_revision_id');
        $this->travelTo(Carbon::parse('2026-09-15 10:00:00'));
        $this->postJson('/cashier/notes/'.$noteId.'/cancel', [
            'base_revision_id' => $sourceRevisionId,
            'idempotency_key' => 'restore-cancel',
            'reason' => 'Pelanggan batal sebelum transaksi berjalan',
        ])->assertOk();
        $cancellationId = (string) DB::table('note_mutation_events')
            ->where('note_id', $noteId)->where('mutation_type', 'note_cancelled')->value('id');
        $cancelledLineId = (string) DB::table('inventory_movements')
            ->where('source_type', 'work_item_store_stock_line')->value('source_id');
        $stockBeforeRestore = (int) DB::table('product_inventory')->where('product_id', 'primitive-p')->value('qty_on_hand');

        $command = [
            'base_revision_id' => $sourceRevisionId,
            'cancellation_event_id' => $cancellationId,
            'source_revision_id' => $sourceRevisionId,
            'idempotency_key' => 'restore-once',
            'reason' => 'Pelanggan melanjutkan transaksi',
        ];
        $response = $this->postJson('/cashier/notes/'.$noteId.'/restore', $command)
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.revision_number', 2);

        $restoredRevision = (string) DB::table('notes')->where('id', $noteId)->value('current_revision_id');
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'open', 'current_revision_id' => $restoredRevision, 'total_rupiah' => 142539]);
        $this->assertDatabaseHas('note_revisions', ['id' => $sourceRevisionId, 'revision_number' => 1, 'parent_revision_id' => null]);
        $this->assertDatabaseHas('note_revisions', ['id' => $restoredRevision, 'revision_number' => 2, 'parent_revision_id' => $sourceRevisionId]);
        $this->assertDatabaseHas('note_mutation_events', ['id' => $cancellationId, 'mutation_type' => 'note_cancelled']);
        $this->assertDatabaseHas('note_mutation_events', ['note_id' => $noteId, 'mutation_type' => 'note_restored', 'actor_id' => (string) $cashier->getAuthIdentifier()]);
        $history = app(NoteCorrectionHistoryReaderPort::class)->findLatestNoteCorrections($noteId);
        self::assertContains('Pulihkan Transaksi', array_column($history, 'event_label'));
        self::assertSame($stockBeforeRestore - 3, (int) DB::table('product_inventory')->where('product_id', 'primitive-p')->value('qty_on_hand'));
        self::assertSame(1, DB::table('inventory_movements')->where('source_type', 'work_item_store_stock_line_reversal')->where('source_id', $cancelledLineId)->count());
        self::assertSame(2, DB::table('inventory_movements')->where('source_type', 'work_item_store_stock_line')->count());
        $issueSourceIds = DB::table('inventory_movements')->where('source_type', 'work_item_store_stock_line')->pluck('source_id')->all();
        self::assertCount(2, array_unique($issueSourceIds));
        $currentWorkItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');
        $currentStockLineId = (string) DB::table('work_item_store_stock_lines')->where('work_item_id', $currentWorkItemId)->value('id');
        self::assertNotSame($cancelledLineId, $currentStockLineId);
        self::assertContains($currentStockLineId, $issueSourceIds);
        self::assertSame(1, DB::table('audit_outbox')->where('event_name', 'note_cancelled')->count());
        self::assertSame(1, DB::table('audit_outbox')->where('event_name', 'note_restored')->count());
        $effects = [
            DB::table('inventory_movements')->orderBy('id')->get()->toJson(),
            DB::table('note_revisions')->orderBy('id')->get()->toJson(),
            DB::table('note_mutation_events')->orderBy('id')->get()->toJson(),
            DB::table('audit_outbox')->orderBy('id')->get()->toJson(),
        ];
        $this->postJson('/cashier/notes/'.$noteId.'/restore', $command)
            ->assertOk()->assertJsonPath('data.restore_event_id', $response->json('data.restore_event_id'));
        self::assertSame($effects, [
            DB::table('inventory_movements')->orderBy('id')->get()->toJson(),
            DB::table('note_revisions')->orderBy('id')->get()->toJson(),
            DB::table('note_mutation_events')->orderBy('id')->get()->toJson(),
            DB::table('audit_outbox')->orderBy('id')->get()->toJson(),
        ]);
        $this->postJson('/cashier/notes/'.$noteId.'/restore', [...$command, 'reason' => 'ubah alasan'])
            ->assertStatus(409)->assertJsonPath('code', 'IDEMPOTENCY_KEY_PAYLOAD_MISMATCH');

        $newBase = (string) DB::table('notes')->where('id', $noteId)->value('current_revision_id');
        $revisionPayload = array_replace(
            $this->primitiveWorkspace([$this->primitiveItems()[1]], 'restore-intervening-revision'),
            ['base_revision_id' => $newBase],
        );
        $revision = app(CreateNoteRevisionHandler::class)->handle($noteId, $revisionPayload, (string) $cashier->getAuthIdentifier(), false);
        self::assertTrue($revision->isSuccess(), $revision->message() ?? 'Expected the intervening accepted revision.');
        $thirdRevisionId = (string) $revision->data()['revision_id'];
        $this->postJson('/cashier/notes/'.$noteId.'/cancel', [
            'base_revision_id' => $thirdRevisionId,
            'idempotency_key' => 'restore-cycle-cancel',
            'reason' => 'Batalkan lagi untuk menguji identitas siklus',
        ])->assertOk();
        $this->postJson('/cashier/notes/'.$noteId.'/restore', [
            'base_revision_id' => $thirdRevisionId,
            'cancellation_event_id' => $cancellationId,
            'source_revision_id' => $sourceRevisionId,
            'idempotency_key' => 'restore-stale-cancellation',
            'reason' => 'Gunakan identitas pembatalan lama',
        ])->assertStatus(409)->assertJsonPath('code', 'STALE_CANCELLATION');
        $secondCancellationId = (string) DB::table('note_mutation_events')
            ->where('note_id', $noteId)->where('mutation_type', 'note_cancelled')
            ->where('id', '<>', $cancellationId)->value('id');
        $this->postJson('/cashier/notes/'.$noteId.'/restore', [
            'base_revision_id' => $thirdRevisionId,
            'cancellation_event_id' => $secondCancellationId,
            'source_revision_id' => $sourceRevisionId,
            'idempotency_key' => 'restore-from-prior-version',
            'reason' => 'Pulihkan isi revisi pertama yang telah disetujui',
        ])->assertOk()->assertJsonPath('data.revision_number', 4);
        $this->assertDatabaseHas('note_revisions', ['id' => $noteId.'-r004', 'parent_revision_id' => $thirdRevisionId]);
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'open', 'current_revision_id' => $noteId.'-r004', 'total_rupiah' => 142539]);
        $this->assertDatabaseHas('note_history_projection', ['note_id' => $noteId, 'total_rupiah' => 142539, 'outstanding_rupiah' => 142539]);
        $outstanding = app(NoteOutstandingPaymentAmountResolver::class)->resolveFull($noteId);
        self::assertTrue($outstanding->isSuccess());
        self::assertSame(142539, $outstanding->data()['outstanding_rupiah']);
        $reportRows = app(TransactionSummaryReportingQuery::class)->rows('2026-09-15', '2026-09-15');
        self::assertCount(1, $reportRows);
        self::assertSame(142539, $reportRows[0]['gross_transaction_rupiah']);
    }

    public function test_restore_rebuilds_service_and_package_from_the_accepted_snapshot(): void
    {
        $this->preparePrimitiveFixture();
        $this->loginAsKasir();
        $items = [$this->primitiveItems()[1], $this->primitiveItems()[2]];
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace($items, 'restore-package-create'))
            ->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $sourceRevisionId = (string) DB::table('notes')->value('current_revision_id');
        $this->postJson('/cashier/notes/'.$noteId.'/cancel', [
            'base_revision_id' => $sourceRevisionId,
            'idempotency_key' => 'restore-package-cancel',
            'reason' => 'Batalkan paket sebelum dikerjakan',
        ])->assertOk();
        $cancellationId = (string) DB::table('note_mutation_events')
            ->where('note_id', $noteId)->where('mutation_type', 'note_cancelled')->value('id');
        $stockBeforeRestore = (int) DB::table('product_inventory')->where('product_id', 'primitive-q')->value('qty_on_hand');

        $this->postJson('/cashier/notes/'.$noteId.'/restore', [
            'base_revision_id' => $sourceRevisionId,
            'cancellation_event_id' => $cancellationId,
            'source_revision_id' => $sourceRevisionId,
            'idempotency_key' => 'restore-package-once',
            'reason' => 'Pelanggan melanjutkan servis dan paket',
        ])->assertOk();

        self::assertSame(2, DB::table('work_items')->where('note_id', $noteId)->where('status', 'open')->count());
        self::assertSame($stockBeforeRestore - 2, (int) DB::table('product_inventory')->where('product_id', 'primitive-q')->value('qty_on_hand'));
        self::assertSame(2, DB::table('inventory_movements')->where('product_id', 'primitive-q')->where('source_type', 'work_item_store_stock_line')->count());
        self::assertSame(1, DB::table('note_revisions')->where('note_root_id', $noteId)->where('parent_revision_id', $sourceRevisionId)->count());
    }

    public function test_insufficient_stock_rolls_back_restore_and_keeps_cancellation_current(): void
    {
        $this->preparePrimitiveFixture();
        $this->loginAsKasir();
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace([$this->primitiveItems()[0]], 'restore-no-stock-create'))
            ->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $sourceRevisionId = (string) DB::table('notes')->value('current_revision_id');
        $this->postJson('/cashier/notes/'.$noteId.'/cancel', [
            'base_revision_id' => $sourceRevisionId,
            'idempotency_key' => 'restore-no-stock-cancel',
            'reason' => 'Batalkan sebelum transaksi berjalan',
        ])->assertOk();
        $cancellationId = (string) DB::table('note_mutation_events')
            ->where('note_id', $noteId)->where('mutation_type', 'note_cancelled')->value('id');
        $this->postJson('/cashier/notes/'.$noteId.'/restore', [
            'base_revision_id' => 'stale-revision',
            'cancellation_event_id' => $cancellationId,
            'source_revision_id' => $sourceRevisionId,
            'idempotency_key' => 'restore-stale-base',
            'reason' => 'Base revisi lama',
        ])->assertStatus(409)->assertJsonPath('code', 'STALE_REVISION');
        DB::table('product_inventory')->where('product_id', 'primitive-p')->update(['qty_on_hand' => 0]);
        $movementCount = DB::table('inventory_movements')->count();

        $this->postJson('/cashier/notes/'.$noteId.'/restore', [
            'base_revision_id' => $sourceRevisionId,
            'cancellation_event_id' => $cancellationId,
            'source_revision_id' => $sourceRevisionId,
            'idempotency_key' => 'restore-no-stock-once',
            'reason' => 'Pelanggan kembali tetapi stok habis',
        ])->assertStatus(422);

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'note_state' => 'cancelled',
            'current_revision_id' => $sourceRevisionId,
            'total_rupiah' => 0,
        ]);
        self::assertSame(1, DB::table('note_revisions')->where('note_root_id', $noteId)->count());
        self::assertSame($movementCount, DB::table('inventory_movements')->count());
        self::assertSame(0, DB::table('note_mutation_events')->where('note_id', $noteId)->where('mutation_type', 'note_restored')->count());
        self::assertSame(0, DB::table('idempotency_records')->where('operation', 'restore_cancelled_note')->count());
    }

    public function test_restore_does_not_revive_an_external_purchase_from_an_older_revision(): void
    {
        $this->preparePrimitiveFixture();
        $cashier = $this->loginAsKasir();
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace([$this->primitiveItems()[3]], 'restore-external-create'))
            ->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $sourceRevisionId = (string) DB::table('notes')->value('current_revision_id');
        $replacement = array_replace(
            $this->primitiveWorkspace([$this->primitiveItems()[1]], 'restore-external-replaced'),
            ['base_revision_id' => $sourceRevisionId],
        );
        $revision = app(CreateNoteRevisionHandler::class)->handle($noteId, $replacement, (string) $cashier->getAuthIdentifier(), false);
        self::assertTrue($revision->isSuccess(), $revision->message() ?? 'Expected to replace the external purchase with a service revision.');
        $currentRevisionId = (string) $revision->data()['revision_id'];
        $this->postJson('/cashier/notes/'.$noteId.'/cancel', [
            'base_revision_id' => $currentRevisionId,
            'idempotency_key' => 'restore-external-cancel',
            'reason' => 'Batalkan revisi service',
        ])->assertOk();
        $cancellationId = (string) DB::table('note_mutation_events')
            ->where('note_id', $noteId)->where('mutation_type', 'note_cancelled')->value('id');

        $this->postJson('/cashier/notes/'.$noteId.'/restore', [
            'base_revision_id' => $currentRevisionId,
            'cancellation_event_id' => $cancellationId,
            'source_revision_id' => $sourceRevisionId,
            'idempotency_key' => 'restore-external-reject',
            'reason' => 'Pulihkan revisi yang memiliki pembelian eksternal',
        ])->assertStatus(422)->assertJsonPath('code', 'RESTORE_EXTERNAL_PURCHASE_UNSUPPORTED');

        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'cancelled', 'current_revision_id' => $currentRevisionId]);
        self::assertSame(2, DB::table('note_revisions')->where('note_root_id', $noteId)->count());
        self::assertSame(0, DB::table('note_mutation_events')->where('note_id', $noteId)->where('mutation_type', 'note_restored')->count());
        self::assertSame(0, DB::table('idempotency_records')->where('operation', 'restore_cancelled_note')->count());
    }
}
