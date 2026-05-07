<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class RecordSelectedRowsNotePaymentFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_it_allocates_payment_only_to_selected_open_rows(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 100000, 'open');

        DB::table('notes')->where('id', 'note-1')->update([
            'current_revision_id' => 'note-1-r001',
            'latest_revision_number' => 1,
        ]);

        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-1', 'Servis A', 50000, ServiceDetail::PART_SOURCE_NONE);

        $this->seedWorkItemBase('wi-2', 'note-1', 2, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-2', 'Servis B', 50000, ServiceDetail::PART_SOURCE_NONE);

        DB::table('note_revisions')->insert([
            'id' => 'note-1-r001',
            'note_root_id' => 'note-1',
            'revision_number' => 1,
            'parent_revision_id' => null,
            'created_by_actor_id' => null,
            'reason' => 'selected row payment fixture',
            'customer_name' => 'Budi',
            'customer_phone' => null,
            'transaction_date' => $today,
            'grand_total_rupiah' => 100000,
            'line_count' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('note_revision_lines')->insert([
            [
                'id' => 'note-1-r001-l001',
                'note_revision_id' => 'note-1-r001',
                'work_item_root_id' => 'wi-1',
                'line_no' => 1,
                'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
                'status' => WorkItem::STATUS_OPEN,
                'service_label' => 'Servis A',
                'service_price_rupiah' => 50000,
                'subtotal_rupiah' => 50000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'note-1-r001-l002',
                'note_revision_id' => 'note-1-r001',
                'work_item_root_id' => 'wi-2',
                'line_no' => 2,
                'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
                'status' => WorkItem::STATUS_OPEN,
                'service_label' => 'Servis B',
                'service_price_rupiah' => 50000,
                'subtotal_rupiah' => 50000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($user)->post(route('cashier.notes.payments.store', ['noteId' => 'note-1']), [
            'selected_row_ids' => ['wi-2::service_fee::wi-2'],
            'payment_scope' => 'partial',
            'payment_method' => 'cash',
            'paid_at' => $today,
            'amount_paid' => '30000',
            'amount_received' => '30000',
        ]);

        $response->assertRedirect(route('cashier.notes.show', ['noteId' => 'note-1']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payment_component_allocations', [
            'note_id' => 'note-1',
            'work_item_id' => 'wi-2',
            'allocated_amount_rupiah' => 30000,
        ]);

        $this->assertDatabaseMissing('payment_component_allocations', [
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'allocated_amount_rupiah' => 30000,
        ]);
    }

    public function test_it_rejects_payment_without_selected_rows(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 50000, 'open');
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-1', 'Servis A', 50000, ServiceDetail::PART_SOURCE_NONE);

        $response = $this->from(route('cashier.notes.show', ['noteId' => 'note-1']))
            ->actingAs($user)
            ->post(route('cashier.notes.payments.store', ['noteId' => 'note-1']), [
                'selected_row_ids' => [],
                'payment_scope' => 'partial',
                'payment_method' => 'cash',
                'paid_at' => $today,
                'amount_paid' => '10000',
                'amount_received' => '10000',
            ]);

        $response->assertRedirect(route('cashier.notes.show', ['noteId' => 'note-1']));
        $response->assertSessionHasErrors(['selected_row_ids']);
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Selected Rows Payment',
            'email' => 'kasir-selected-rows-payment@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }
}
