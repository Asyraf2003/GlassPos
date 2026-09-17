<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

final class PrimitiveRevisionIdentityContractFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_stale_editor_must_not_replace_a_newer_committed_revision(): void
    {
        $this->preparePrimitiveFixture();
        $this->loginAsKasir();
        $item = ['entry_mode' => 'service', 'part_source' => 'none', 'service' => ['name' => 'QA service', 'price_rupiah' => 63719]];
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace([$item], 'identity-create'))->assertSessionHasNoErrors();
        $id = (string) DB::table('notes')->value('id');
        $edit = route('cashier.notes.workspace.edit', ['noteId' => $id]);
        $update = route('cashier.notes.workspace.update', ['noteId' => $id]);
        // Two editors load R1 before either submission; only existing request fields are used.
        $this->get($edit)->assertOk();
        $first = $this->primitiveWorkspace([$item], 'identity-editor-one');
        $this->get($edit)->assertOk();
        $stale = $this->primitiveWorkspace([$item], 'identity-editor-two');
        $r1 = (string) DB::table('notes')->value('current_revision_id');
        $first['base_revision_id'] = $r1;
        $stale['base_revision_id'] = $r1;
        $first['items'][0]['service']['price_rupiah'] = 81258;
        $stale['items'][0]['service']['price_rupiah'] = 51983;
        $this->advancePrimitiveTime();
        $this->patch($update, $first)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('note_revisions', 2);
        $this->assertDatabaseHas('notes', ['id' => $id, 'total_rupiah' => 81258]);
        $r2 = (string) DB::table('notes')->value('current_revision_id');
        self::assertNotSame($r1, $r2);
        $this->advancePrimitiveTime();
        $before = $this->domainEvidence();
        $response = $this->patch($update, $stale);
        $actual = DB::table('notes')->where('id', $id)->first();
        self::assertSame($r2, $actual->current_revision_id, sprintf(
            'Stale R1 editor must preserve R2. Actual revisions=%d, total=%d, pointer=%s, HTTP=%d.',
            DB::table('note_revisions')->count(), $actual->total_rupiah, $actual->current_revision_id, $response->getStatusCode(),
        ));
        $response->assertSessionHasErrors(['revision' => 'STALE_REVISION: Nota telah berubah. Muat ulang editor sebelum menyimpan.']);
        self::assertSame($before, $this->domainEvidence());
        $this->assertDatabaseCount('note_revisions', 2);
        $this->assertDatabaseHas('notes', ['id' => $id, 'total_rupiah' => 81258]);
        // Exact retry is replayed before checking the now-stale R1 base.
        $this->postJson(route('cashier.notes.workspace.draft.save'), $stale + ['workspace_mode' => 'edit', 'note_id' => $id])->assertOk();
        $this->patch($update, $first)->assertSessionHasNoErrors();
        self::assertSame($before, $this->domainEvidence());
        $changed = $first;
        $changed['items'][0]['service']['price_rupiah'] = 90001;
        $this->patch($update, $changed)->assertSessionHasErrors('revision');
        self::assertSame($before, $this->domainEvidence());
        $this->patchJson($update, $stale)->assertStatus(409)->assertJsonPath('code', 'STALE_REVISION');
        self::assertSame($before, $this->domainEvidence());
        $unrelated = $first;
        $unrelated['base_revision_id'] = 'other-root-r001';
        $unrelated['idempotency_key'] = 'identity-unrelated';
        $this->patchJson($update, $unrelated)->assertStatus(409)->assertJsonPath('code', 'STALE_REVISION');
        self::assertSame($before, $this->domainEvidence());
        $missing = $first;
        unset($missing['base_revision_id']);
        $this->patchJson($update, $missing)->assertStatus(422)->assertJsonValidationErrors('base_revision_id');
        self::assertSame($before, $this->domainEvidence());
        $this->flushSession();
        // A saved stale draft keeps its R1 token when R2 is now current.
        $this->postJson(route('cashier.notes.workspace.draft.save'), $stale + ['workspace_mode' => 'edit', 'note_id' => $id])->assertOk();
        $this->get($edit)->assertOk()->assertSee('name="base_revision_id" value="'.$r1.'"', false);
        $fresh = $first;
        $fresh['base_revision_id'] = $r2;
        $fresh['idempotency_key'] = 'identity-current';
        $fresh['items'][0]['service']['price_rupiah'] = 90001;
        $this->patch($update, $fresh)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('note_revisions', 3);
        $this->assertDatabaseHas('notes', ['id' => $id, 'total_rupiah' => 90001]);
    }

    private function domainEvidence(): array
    {
        $rows = [];
        foreach (['notes', 'work_items', 'work_item_service_details', 'work_item_store_stock_lines', 'work_item_external_purchase_lines',
            'note_revisions', 'note_revision_lines', 'note_revision_settlements', 'customer_payments', 'customer_payment_cash_details',
            'payment_allocations', 'payment_component_allocations', 'customer_refunds', 'refund_component_allocations',
            'note_revision_surplus_dispositions', 'note_revision_surplus_refund_payments', 'inventory_movements', 'product_inventory',
            'product_inventory_costing', 'note_history_projection', 'note_mutation_events', 'note_mutation_snapshots',
            'audit_logs', 'audit_events', 'audit_outbox', 'idempotency_records'] as $table) {
            $key = match ($table) {
                'work_item_service_details' => 'work_item_id',
                'customer_payment_cash_details' => 'customer_payment_id',
                'product_inventory', 'product_inventory_costing' => 'product_id',
                'note_history_projection' => 'note_id',
                default => 'id',
            };
            $rows[$table] = DB::table($table)->orderBy($key)->get()->toJson();
        }
        return $rows;
    }
}
