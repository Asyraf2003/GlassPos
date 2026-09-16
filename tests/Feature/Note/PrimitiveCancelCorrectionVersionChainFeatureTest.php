<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

final class PrimitiveCancelCorrectionVersionChainFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_chain_c_draft_unpaid_removal_new_revision_work_and_paid_refund_are_distinct(): void
    {
        $this->preparePrimitiveFixture();
        $cashier = $this->loginAsKasir();
        $items = array_slice($this->primitiveItems(), 0, 2);
        $items[0]['product_lines'][0]['qty'] = 2;
        $payload = $this->primitiveWorkspace($items, 'chain-c-create');
        $opening = $this->primitiveRows('inventory_movements');
        $draft = $payload + ['workspace_mode' => 'create'];
        $this->postJson(route('cashier.notes.workspace.draft.save'), $draft)->assertOk()->assertJsonPath('data.saved', true);
        $this->assertDatabaseCount('transaction_workspace_drafts', 1);
        foreach (['notes', 'customer_payments', 'customer_refunds', 'note_mutation_events', 'note_history_projection'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        self::assertSame($opening, $this->primitiveRows('inventory_movements'));
        $admin = $this->loginAsAuthorizedAdmin();
        $this->postJson(route('admin.notes.workspace.draft.save'), $draft)->assertOk();
        $otherDraft = (array) DB::table('transaction_workspace_drafts')->where('actor_id', $admin->getAuthIdentifier())->first();
        $this->advancePrimitiveTime();
        $this->actingAs($cashier)->post(route('notes.workspace.store'), $payload)->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $this->assertDatabaseMissing('transaction_workspace_drafts', ['actor_id' => $cashier->getAuthIdentifier(), 'workspace_key' => 'create']);
        self::assertSame($otherDraft, (array) DB::table('transaction_workspace_drafts')->where('actor_id', $admin->getAuthIdentifier())->first());
        $this->projection($noteId, 158745, 0, 158745, 'open');
        $oldP = (string) DB::table('work_items')->where('transaction_type', 'store_stock_sale_only')->value('id');
        $oldPart = (string) DB::table('work_item_store_stock_lines')->value('id');
        $r1 = (string) DB::table('notes')->value('current_revision_id');
        $r1Lines = $this->primitiveRows('note_revision_lines');
        $this->assertDatabaseHas('inventory_movements', ['source_id' => $oldPart, 'source_type' => 'work_item_store_stock_line', 'qty_delta' => -2]);
        $this->advancePrimitiveTime();
        $this->patch(route('cashier.notes.workspace.update', ['noteId' => $noteId]), $this->primitiveWorkspace([$items[1]], 'chain-c-remove-unpaid'))->assertSessionHasNoErrors();
        $this->projection($noteId, 63719, 0, 63719, 'open');
        $this->assertDatabaseCount('note_revisions', 2);
        $this->assertDatabaseCount('customer_refunds', 0);
        $this->assertDatabaseCount('note_revision_surplus_dispositions', 0);
        $this->assertDatabaseHas('inventory_movements', ['source_id' => $oldPart, 'source_type' => 'transaction_workspace_updated', 'qty_delta' => 2]);
        self::assertSame($r1Lines, DB::table('note_revision_lines')->where('note_revision_id', $r1)->orderBy('id')->get()->toJson());
        $stock = $this->primitiveRows('inventory_movements');
        $this->advancePrimitiveTime();
        $paymentRoute = route('cashier.notes.payments.store', ['noteId' => $noteId]);
        $this->post($paymentRoute, $this->primitivePayment('chain-c-service-pay', 63719, 'cash', 70003))->assertSessionHasNoErrors();
        $this->projection($noteId, 63719, 63719, 0, 'closed');
        $this->assertDatabaseHas('customer_payment_cash_details', ['amount_paid_rupiah' => 63719, 'amount_received_rupiah' => 70003, 'change_rupiah' => 6284]);
        self::assertSame($stock, $this->primitiveRows('inventory_movements'));
        $items[0]['product_lines'][0]['qty'] = 1;
        $this->advancePrimitiveTime();
        $this->actingAs($admin)->patch(route('admin.notes.workspace.update', ['noteId' => $noteId]), $this->primitiveWorkspace($items, 'chain-c-new-product'))->assertSessionHasNoErrors();
        $this->projection($noteId, 111232, 63719, 47513, 'open');
        $this->assertDatabaseCount('note_revisions', 3);
        $newP = (string) DB::table('work_items')->where('transaction_type', 'store_stock_sale_only')->value('id');
        $newPart = (string) DB::table('work_item_store_stock_lines')->value('id');
        self::assertNotSame($oldP, $newP);
        self::assertNotSame($oldPart, $newPart);
        $this->assertDatabaseHas('inventory_movements', ['source_id' => $newPart, 'source_type' => 'work_item_store_stock_line', 'qty_delta' => -1]);
        $stock = $this->primitiveRows('inventory_movements');
        $this->advancePrimitiveTime();
        $this->actingAs($cashier)->post($paymentRoute, $this->primitivePayment('chain-c-product-pay', 47513, 'transfer'))->assertSessionHasNoErrors();
        $this->projection($noteId, 111232, 111232, 0, 'closed');
        $this->assertDatabaseCount('customer_payments', 2);
        $this->assertDatabaseCount('customer_payment_cash_details', 1);
        self::assertSame($stock, $this->primitiveRows('inventory_movements'));
        self::assertSame(2, DB::table('note_mutation_events')->where('mutation_type', 'note_closed')->count());
        $this->advancePrimitiveTime();
        $refundRoute = route('cashier.notes.refunds.store', ['noteId' => $noteId]);
        $refund = ['selected_row_ids' => [$newP], 'refunded_at' => '2026-09-15', 'reason' => 'Chain C refund current paid product', 'idempotency_key' => 'chain-c-refund'];
        $this->post($refundRoute, $refund)->assertSessionHasNoErrors();
        $this->projection($noteId, 63719, 63719, 0, 'closed');
        self::assertSame(47513, (int) DB::table('customer_refunds')->sum('amount_rupiah'));
        $this->assertDatabaseHas('work_items', ['id' => $newP, 'status' => 'canceled']);
        $this->assertDatabaseHas('inventory_movements', ['source_id' => $newPart, 'source_type' => 'work_item_store_stock_line_reversal', 'qty_delta' => 1]);
        $this->assertDatabaseHas('product_inventory', ['product_id' => 'primitive-p', 'qty_on_hand' => 17]);
        self::assertSame(4, DB::table('inventory_movements')->where('source_type', '<>', 'seed_fixture')->count());
        $money = $this->primitiveRows('customer_refunds');
        $stock = $this->primitiveRows('inventory_movements');
        $this->post($refundRoute, $refund)->assertSessionHasNoErrors();
        foreach ([$oldP, $newP] as $stale) {
            $this->post($refundRoute, array_replace($refund, ['selected_row_ids' => [$stale], 'idempotency_key' => 'chain-c-stale-'.$stale]))->assertSessionHasErrors();
            self::assertSame($money, $this->primitiveRows('customer_refunds'));
            self::assertSame($stock, $this->primitiveRows('inventory_movements'));
            $this->projection($noteId, 63719, 63719, 0, 'closed');
        }
    }

    private function projection(string $id, int $total, int $paid, int $outstanding, string $state): void
    {
        $this->assertDatabaseHas('note_history_projection', ['note_id' => $id, 'total_rupiah' => $total, 'net_paid_rupiah' => $paid, 'outstanding_rupiah' => $outstanding]);
        $this->assertDatabaseHas('notes', ['id' => $id, 'note_state' => $state]);
    }
}
