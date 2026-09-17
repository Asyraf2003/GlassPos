<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AddNoteRowsHttpFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_add_rows_to_open_unpaid_note_for_today(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir',
            'email' => 'cashier@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        DB::table('notes')->insert([
            'id' => 'note-1',
            'customer_name' => 'Budi',
            'transaction_date' => now()->toDateString(),
            'total_rupiah' => 50000,
            'note_state' => 'open',
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
            'service_name' => 'Servis A',
            'service_price_rupiah' => 50000,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);

        $this->actingAs($user)->get(route('cashier.notes.workspace.edit', ['noteId' => 'note-1']))->assertOk();
        $base = (string) DB::table('notes')->where('id', 'note-1')->value('current_revision_id');
        $response = $this->actingAs($user)->post('/cashier/notes/note-1/rows', [
            'base_revision_id' => $base,
            'rows' => [[
                'line_type' => 'service',
                'service_name' => 'Servis B',
                'service_price_rupiah' => 25000,
            ]],
        ]);

        $response->assertRedirect(route('cashier.notes.show', ['noteId' => 'note-1']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('note_revisions', 2);
        $this->postJson('/cashier/notes/note-1/rows', [
            'base_revision_id' => $base,
            'rows' => [['line_type' => 'service', 'service_name' => 'Stale row', 'service_price_rupiah' => 90001]],
        ])->assertStatus(409)->assertJsonPath('code', 'STALE_REVISION');
        $this->assertDatabaseCount('note_revisions', 2);
        $this->assertDatabaseHas('work_items', [
            'note_id' => 'note-1',
            'line_no' => 2,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'subtotal_rupiah' => 25000,
        ]);
        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'total_rupiah' => 75000,
        ]);
    }

    public function test_cashier_cannot_add_rows_to_closed_paid_note(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir',
            'email' => 'cashier-closed@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        DB::table('notes')->insert([
            'id' => 'note-2',
            'customer_name' => 'Andi',
            'transaction_date' => now()->toDateString(),
            'total_rupiah' => 50000,
            'note_state' => 'closed',
        ]);

        DB::table('work_items')->insert([
            'id' => 'wi-2',
            'note_id' => 'note-2',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 50000,
        ]);

        DB::table('work_item_service_details')->insert([
            'work_item_id' => 'wi-2',
            'service_name' => 'Servis A',
            'service_price_rupiah' => 50000,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);

        DB::table('customer_payments')->insert([
            'id' => 'cp-1',
            'amount_rupiah' => 50000,
            'paid_at' => now()->toDateString(),
        ]);

        DB::table('payment_allocations')->insert([
            'id' => 'pa-1',
            'customer_payment_id' => 'cp-1',
            'note_id' => 'note-2',
            'amount_rupiah' => 50000,
        ]);

        $response = $this->actingAs($user)->from('/cashier/notes/note-2')->post('/cashier/notes/note-2/rows', [
            'rows' => [[
                'line_type' => 'service',
                'service_name' => 'Servis B',
                'service_price_rupiah' => 25000,
            ]],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('work_items', [
            'note_id' => 'note-2',
            'line_no' => 2,
            'subtotal_rupiah' => 25000,
        ]);
    }
}
