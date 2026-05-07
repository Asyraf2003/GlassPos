<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class RecordNotePaymentHttpFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_cashier_can_record_note_payment_and_store_component_allocations(): void
    {
        $this->loginAsKasir();
        $user = User::query()->create([
            'name' => 'Kasir Aktif',
            'email' => 'cashier@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $today = date('Y-m-d');

        DB::table('notes')->insert([
            'id' => 'note-1',
            'customer_name' => 'Budi',
            'transaction_date' => $today,
            'note_state' => 'open',
            'total_rupiah' => 150000,
        ]);

        DB::table('work_items')->insert([
            ['id' => 'wi-1', 'note_id' => 'note-1', 'line_no' => 1, 'transaction_type' => WorkItem::TYPE_SERVICE_ONLY, 'status' => WorkItem::STATUS_OPEN, 'subtotal_rupiah' => 50000],
            ['id' => 'wi-2', 'note_id' => 'note-1', 'line_no' => 2, 'transaction_type' => WorkItem::TYPE_SERVICE_ONLY, 'status' => WorkItem::STATUS_OPEN, 'subtotal_rupiah' => 100000],
        ]);

        DB::table('work_item_service_details')->insert([
            ['work_item_id' => 'wi-1', 'service_name' => 'Servis A', 'service_price_rupiah' => 50000, 'part_source' => ServiceDetail::PART_SOURCE_NONE],
            ['work_item_id' => 'wi-2', 'service_name' => 'Servis B', 'service_price_rupiah' => 100000, 'part_source' => ServiceDetail::PART_SOURCE_NONE],
        ]);

        $response = $this->actingAs($user)->post('/cashier/notes/note-1/payments', [
            'selected_row_ids' => ['wi-1::service_fee::wi-1'],
            'payment_method' => 'cash',
            'paid_at' => $today,
            'amount_received' => 70000,
        ]);

        $response->assertRedirect(route('cashier.notes.show', ['noteId' => 'note-1']));
        $this->assertDatabaseHas('customer_payments', [
            'amount_rupiah' => 50000,
            'paid_at' => $today,
            'payment_method' => 'cash',
        ]);

        $paymentId = (string) DB::table('customer_payments')->value('id');
        $this->assertNotSame('', $paymentId);

        $this->assertDatabaseHas('customer_payment_cash_details', [
            'customer_payment_id' => $paymentId,
            'amount_paid_rupiah' => 50000,
            'amount_received_rupiah' => 70000,
            'change_rupiah' => 20000,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => $paymentId,
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-1',
            'allocated_amount_rupiah' => 50000,
        ]);
    }

    public function test_rejects_selected_row_payment_when_note_already_paid_via_legacy_allocation(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Legacy Paid',
            'email' => 'cashier-legacy-paid@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $today = date('Y-m-d');

        DB::table('notes')->insert([
            'id' => 'note-legacy-paid-1',
            'current_revision_id' => 'note-legacy-paid-1-r001',
            'latest_revision_number' => 1,
            'customer_name' => 'Budi Legacy',
            'transaction_date' => $today,
            'note_state' => 'open',
            'total_rupiah' => 50000,
        ]);

        DB::table('work_items')->insert([
            'id' => 'wi-legacy-paid-1',
            'note_id' => 'note-legacy-paid-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 50000,
        ]);

        DB::table('work_item_service_details')->insert([
            'work_item_id' => 'wi-legacy-paid-1',
            'service_name' => 'Servis Legacy Paid',
            'service_price_rupiah' => 50000,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);

        DB::table('note_revisions')->insert([
            'id' => 'note-legacy-paid-1-r001',
            'note_root_id' => 'note-legacy-paid-1',
            'revision_number' => 1,
            'parent_revision_id' => null,
            'created_by_actor_id' => null,
            'reason' => 'legacy paid selected row characterization',
            'customer_name' => 'Budi Legacy',
            'customer_phone' => null,
            'transaction_date' => $today,
            'grand_total_rupiah' => 50000,
            'line_count' => 1,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => null,
        ]);

        DB::table('note_revision_lines')->insert([
            'id' => 'note-legacy-paid-1-r001-l001',
            'note_revision_id' => 'note-legacy-paid-1-r001',
            'work_item_root_id' => 'wi-legacy-paid-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'service_label' => 'Servis Legacy Paid',
            'service_price_rupiah' => 50000,
            'subtotal_rupiah' => 50000,
            'payload' => null,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => null,
        ]);

        DB::table('customer_payments')->insert([
            'id' => 'legacy-payment-1',
            'amount_rupiah' => 50000,
            'paid_at' => $today,
        ]);

        DB::table('payment_allocations')->insert([
            'id' => 'legacy-allocation-1',
            'customer_payment_id' => 'legacy-payment-1',
            'note_id' => 'note-legacy-paid-1',
            'amount_rupiah' => 50000,
        ]);

        $response = $this->actingAs($user)->post('/cashier/notes/note-legacy-paid-1/payments', [
            'selected_row_ids' => ['wi-legacy-paid-1'],
            'payment_method' => 'cash',
            'paid_at' => $today,
            'amount_received' => 50000,
        ]);

        $response->assertSessionHasErrors([
            'payment' => 'Hanya billing row outstanding yang boleh dipilih untuk pembayaran.',
        ]);

        $this->assertSame(1, DB::table('customer_payments')->count());
        $this->assertSame(0, DB::table('payment_component_allocations')->count());
    }

}
