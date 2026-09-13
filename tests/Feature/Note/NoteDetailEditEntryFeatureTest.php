<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class NoteDetailEditEntryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpaid_note_detail_shows_edit_nota_entry(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Edit Entry',
            'email' => 'note-edit-entry@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $today = date('Y-m-d');

        DB::table('notes')->insert([
            'id' => 'note-entry-1',
            'customer_name' => 'Budi Entry',
            'customer_phone' => '0812000001',
            'transaction_date' => $today,
            'note_state' => 'open',
            'total_rupiah' => 50000,
        ]);

        DB::table('work_items')->insert([
            'id' => 'work-item-entry-1',
            'note_id' => 'note-entry-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 50000,
        ]);

        DB::table('work_item_service_details')->insert([
            'work_item_id' => 'work-item-entry-1',
            'service_name' => 'Servis Entry',
            'service_price_rupiah' => 50000,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);

        $response = $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-entry-1']));

        $response->assertOk();
        $response->assertSee('Edit');
        $response->assertSee(route('cashier.notes.workspace.edit', ['noteId' => 'note-entry-1']), false);
    }

    public function test_raw_open_note_with_full_settlement_does_not_advertise_forbidden_cashier_edit(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir No Edit Entry',
            'email' => 'note-no-edit-entry@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $today = date('Y-m-d');

        DB::table('notes')->insert([
            'id' => 'note-entry-2',
            'customer_name' => 'Budi Paid',
            'customer_phone' => '0812000002',
            'transaction_date' => $today,
            'note_state' => 'open',
            'total_rupiah' => 50000,
        ]);

        DB::table('work_items')->insert([
            'id' => 'work-item-entry-2',
            'note_id' => 'note-entry-2',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 50000,
        ]);

        DB::table('work_item_service_details')->insert([
            'work_item_id' => 'work-item-entry-2',
            'service_name' => 'Servis Paid',
            'service_price_rupiah' => 50000,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);

        DB::table('customer_payments')->insert([
            'id' => 'payment-entry-1',
            'amount_rupiah' => 50000,
            'paid_at' => $today,
        ]);

        DB::table('payment_allocations')->insert([
            'id' => 'allocation-entry-1',
            'customer_payment_id' => 'payment-entry-1',
            'note_id' => 'note-entry-2',
            'amount_rupiah' => 50000,
        ]);

        $response = $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-entry-2']));

        $response->assertOk();
        self::assertFalse($response->viewData('note')['can_edit_workspace']);
        $response->assertDontSee('href="'.route('cashier.notes.workspace.edit', ['noteId' => 'note-entry-2']).'"', false);
    }
}
