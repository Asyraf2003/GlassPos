<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Reporting\Queries\TransactionSummaryReportingQuery;
use App\Application\Note\Services\NoteOutstandingPaymentAmountResolver;
use App\Application\Reporting\UseCases\GetOperationalProfitSummaryHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

final class WholeNoteCancellationFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_unpaid_cancellation_neutralizes_current_effects_and_preserves_history(): void
    {
        $this->preparePrimitiveFixture();
        $cashier = $this->loginAsKasir();
        $items = array_slice($this->primitiveItems(), 0, 2);
        $items[0]['product_lines'][0]['qty'] = 2;
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace($items, 'cancel-create'))->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $base = (string) DB::table('notes')->value('current_revision_id');
        $original = $this->primitiveRows('note_revision_lines');
        $issue = (array) DB::table('inventory_movements')->where('source_type', 'work_item_store_stock_line')->first();
        $this->advancePrimitiveTime();
        $payload = ['base_revision_id' => $base, 'idempotency_key' => 'cancel-once', 'reason' => 'Pelanggan membatalkan seluruh transaksi'];
        $response = $this->postJson('/cashier/notes/'.$noteId.'/cancel', $payload)->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'cancelled', 'total_rupiah' => 0, 'current_revision_id' => $base]);
        $this->assertDatabaseHas('note_history_projection', ['note_id' => $noteId, 'total_rupiah' => 0, 'outstanding_rupiah' => 0]);
        self::assertSame(0, DB::table('work_items')->where('status', '<>', 'canceled')->count());
        $this->assertDatabaseHas('product_inventory', ['product_id' => 'primitive-p', 'qty_on_hand' => 17]);
        $this->assertDatabaseHas('inventory_movements', ['source_type' => 'work_item_store_stock_line_reversal', 'source_id' => $issue['source_id'], 'qty_delta' => 2, 'unit_cost_rupiah' => 19721]);
        self::assertSame($issue, (array) DB::table('inventory_movements')->where('id', $issue['id'])->first());
        self::assertSame($original, $this->primitiveRows('note_revision_lines'));
        foreach (['customer_payments', 'customer_refunds', 'note_revision_surplus_dispositions'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertDatabaseHas('note_mutation_events', ['note_id' => $noteId, 'mutation_type' => 'note_cancelled', 'actor_id' => (string) $cashier->getAuthIdentifier(), 'reason' => $payload['reason']]);
        self::assertSame(1, DB::table('audit_outbox')->where('event_name', 'note_cancelled')->count());
        self::assertTrue(app(NoteOutstandingPaymentAmountResolver::class)->resolveFull($noteId)->isFailure());
        self::assertSame([], app(TransactionSummaryReportingQuery::class)->rows('2026-09-15', '2026-09-15'));
        $profit = app(GetOperationalProfitSummaryHandler::class)->handle('2026-09-15', '2026-09-15');
        self::assertTrue($profit->isSuccess(), (string) $profit->message());
        self::assertSame(0, $profit->data()['row']['store_stock_cogs_rupiah']);
        self::assertSame(0, $profit->data()['row']['cash_operational_profit_rupiah']);
        $this->get(route('cashier.notes.show', ['noteId' => $noteId]))
            ->assertOk()->assertSee('Dibatalkan')->assertDontSee('Bayar Sebagian')->assertDontSee('Lunasi');
        $effects = [$this->primitiveRows('inventory_movements'), $this->primitiveRows('note_mutation_events'), $this->primitiveRows('audit_outbox')];
        $this->postJson('/cashier/notes/'.$noteId.'/cancel', $payload)->assertOk()->assertJsonPath('data.cancellation_id', $response->json('data.cancellation_id'));
        self::assertSame($effects, [$this->primitiveRows('inventory_movements'), $this->primitiveRows('note_mutation_events'), $this->primitiveRows('audit_outbox')]);
        $this->postJson('/cashier/notes/'.$noteId.'/cancel', [...$payload, 'reason' => 'alasan diubah'])
            ->assertStatus(409)->assertJsonPath('code', 'IDEMPOTENCY_KEY_PAYLOAD_MISMATCH');
        $this->postJson('/cashier/notes/'.$noteId.'/cancel', [...$payload, 'idempotency_key' => 'cancel-again'])
            ->assertStatus(409)->assertJsonPath('code', 'NOTE_ALREADY_CANCELLED');
        self::assertSame($effects, [$this->primitiveRows('inventory_movements'), $this->primitiveRows('note_mutation_events'), $this->primitiveRows('audit_outbox')]);
    }

    public function test_recorded_payment_routes_cancellation_attempt_to_refund_lifecycle(): void
    {
        $this->preparePrimitiveFixture();
        $this->loginAsKasir();
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace(array_slice($this->primitiveItems(), 1, 1), 'cancel-paid-create'))->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $base = (string) DB::table('notes')->value('current_revision_id');
        $this->post(route('cashier.notes.payments.store', ['noteId' => $noteId]), $this->primitivePayment('cancel-paid-payment', 63719, 'cash', 70003))->assertSessionHasNoErrors();
        $paymentCount = DB::table('customer_payments')->count();
        $this->postJson('/cashier/notes/'.$noteId.'/cancel', [
            'base_revision_id' => $base, 'idempotency_key' => 'cancel-paid', 'reason' => 'Permintaan batal setelah bayar',
        ])->assertStatus(422)->assertJsonPath('code', 'REFUND_REQUIRED');
        self::assertSame($paymentCount, DB::table('customer_payments')->count());
        self::assertSame(0, DB::table('customer_refunds')->count());
        self::assertDatabaseMissing('notes', ['id' => $noteId, 'note_state' => 'cancelled']);
    }

    public function test_external_purchase_uses_existing_external_lifecycle(): void
    {
        $this->preparePrimitiveFixture();
        $this->loginAsKasir();
        $external = $this->primitiveItems()[3];
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace([$external], 'cancel-external-create'))->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $base = (string) DB::table('notes')->value('current_revision_id');
        $this->postJson('/cashier/notes/'.$noteId.'/cancel', [
            'base_revision_id' => $base, 'idempotency_key' => 'cancel-external', 'reason' => 'Permintaan batal',
        ])->assertStatus(422)->assertJsonPath('code', 'EXTERNAL_REFUND_REQUIRED');
        self::assertDatabaseMissing('notes', ['id' => $noteId, 'note_state' => 'cancelled']);
    }

    public function test_service_only_and_package_components_cancel_without_losing_revision_history(): void
    {
        $this->preparePrimitiveFixture();
        $this->loginAsKasir();
        $items = [$this->primitiveItems()[1], $this->primitiveItems()[2]];
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace($items, 'cancel-package-create'))->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $base = (string) DB::table('notes')->value('current_revision_id');
        $linesBefore = $this->primitiveRows('note_revision_lines');
        $this->postJson('/cashier/notes/'.$noteId.'/cancel', [
            'base_revision_id' => $base, 'idempotency_key' => 'cancel-package', 'reason' => 'Pembatalan service dan paket',
        ])->assertOk();
        $this->assertDatabaseHas('product_inventory', ['product_id' => 'primitive-q', 'qty_on_hand' => 23]);
        $this->assertDatabaseHas('inventory_movements', ['source_type' => 'work_item_store_stock_line_reversal', 'qty_delta' => 2, 'unit_cost_rupiah' => 11503]);
        self::assertSame(1, DB::table('inventory_movements')->where('source_type', 'work_item_store_stock_line_reversal')->count());
        self::assertSame($linesBefore, $this->primitiveRows('note_revision_lines'));
        self::assertSame(0, DB::table('work_items')->where('note_id', $noteId)->where('status', '<>', 'canceled')->count());
    }

    public function test_stale_base_revision_cannot_cancel_or_compensate_inventory(): void
    {
        $this->preparePrimitiveFixture();
        $this->loginAsKasir();
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace(array_slice($this->primitiveItems(), 0, 1), 'cancel-stale-create'))->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $this->postJson('/cashier/notes/'.$noteId.'/cancel', [
            'base_revision_id' => 'stale-revision', 'idempotency_key' => 'cancel-stale', 'reason' => 'Base stale',
        ])->assertStatus(409)->assertJsonPath('code', 'STALE_REVISION');
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'open', 'total_rupiah' => 142539]);
        self::assertSame(0, DB::table('inventory_movements')->where('source_type', 'work_item_store_stock_line_reversal')->count());
    }

    public function test_admin_capability_keeps_broader_cancellation_date_scope(): void
    {
        $this->preparePrimitiveFixture();
        $this->loginAsKasir();
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace(array_slice($this->primitiveItems(), 0, 1), 'cancel-admin-create'))->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $base = (string) DB::table('notes')->value('current_revision_id');
        Carbon::setTestNow('2026-09-18 11:00:00');
        $this->loginAsAuthorizedAdmin();
        $this->postJson(route('admin.notes.cancel', ['noteId' => $noteId]), [
            'base_revision_id' => $base, 'idempotency_key' => 'cancel-admin', 'reason' => 'Koreksi oleh admin',
        ])->assertOk();
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'cancelled']);
    }
}
