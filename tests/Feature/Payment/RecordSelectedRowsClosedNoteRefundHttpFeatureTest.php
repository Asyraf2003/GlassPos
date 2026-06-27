<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Core\Note\Note\Note;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class RecordSelectedRowsClosedNoteRefundHttpFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_it_rejects_refund_for_selected_close_row_when_note_still_has_open_line(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');
        $this->seedOpenNoteWithOnePaidLine($today);

        $response = $this->actingAs($user)->post(route('cashier.notes.refunds.store', ['noteId' => 'note-1']), [
            'selected_row_ids' => ['wi-1'],
            'refunded_at' => $today,
            'reason' => 'Refund line terpilih',
        ]);

        $response->assertSessionHasErrors(['refund']);

        $this->assertDatabaseCount('customer_refunds', 0);
        $this->assertDatabaseCount('refund_component_allocations', 0);

        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-1',
            'status' => WorkItem::STATUS_OPEN,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-2',
            'status' => WorkItem::STATUS_OPEN,
        ]);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'note_state' => Note::STATE_OPEN,
            'total_rupiah' => 100000,
        ]);

        $this->assertDatabaseMissing('note_mutation_events', [
            'note_id' => 'note-1',
            'mutation_type' => 'note_rows_canceled_via_refund',
        ]);
    }

    public function test_open_note_with_close_line_shows_refund_launcher(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');
        $this->seedOpenNoteWithOnePaidLine($today);

        $this->actingAs($user)->get(route('cashier.notes.show', ['noteId' => 'note-1']))
            ->assertOk()
            ->assertSee('Pengembalian Dana')
            ->assertSee('Pengembalian Dana / Batalkan Rincian')
            ->assertDontSee('Refund Line Close Terpilih')
            ->assertDontSee('Panel Refund');
    }

    public function test_forged_unpaid_selected_row_refund_does_not_auto_finalize_open_unpaid_note(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');

        $this->seedNoteBase('note-unpaid-refund', 'Unpaid Forged Refund', $today, 50000, Note::STATE_OPEN);
        $this->seedWorkItemBase('wi-unpaid-refund-1', 'note-unpaid-refund', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-unpaid-refund-1', 'Servis Unpaid', 50000, ServiceDetail::PART_SOURCE_NONE);

        $this->actingAs($user)->post(route('cashier.notes.refunds.store', ['noteId' => 'note-unpaid-refund']), [
            'selected_row_ids' => ['wi-unpaid-refund-1'],
            'refunded_at' => $today,
            'reason' => 'Forged refund unpaid row',
        ]);

        $this->assertDatabaseCount('customer_refunds', 0);
        $this->assertDatabaseCount('refund_component_allocations', 0);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-unpaid-refund',
            'note_state' => Note::STATE_OPEN,
        ]);

        $this->assertDatabaseMissing('notes', [
            'id' => 'note-unpaid-refund',
            'note_state' => Note::STATE_REFUNDED,
        ]);

        $this->assertDatabaseMissing('note_mutation_events', [
            'note_id' => 'note-unpaid-refund',
            'mutation_type' => 'note_refunded_after_selected_rows_refund',
        ]);
    }

    public function test_unpaid_selected_row_refund_is_rejected_without_canceling_row_or_changing_note_total(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');

        $this->seedNoteBase('note-unpaid-row-reject', 'Unpaid Row Reject', $today, 50000, Note::STATE_OPEN);
        $this->seedWorkItemBase('wi-unpaid-row-reject-1', 'note-unpaid-row-reject', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-unpaid-row-reject-1', 'Servis Unpaid Reject', 50000, ServiceDetail::PART_SOURCE_NONE);

        $this->actingAs($user)->post(route('cashier.notes.refunds.store', ['noteId' => 'note-unpaid-row-reject']), [
            'selected_row_ids' => ['wi-unpaid-row-reject-1'],
            'refunded_at' => $today,
            'reason' => 'Forged refund unpaid row must not cancel row',
        ]);

        $this->assertDatabaseCount('customer_refunds', 0);
        $this->assertDatabaseCount('refund_component_allocations', 0);

        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-unpaid-row-reject-1',
            'status' => WorkItem::STATUS_OPEN,
        ]);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-unpaid-row-reject',
            'note_state' => Note::STATE_OPEN,
            'total_rupiah' => 50000,
        ]);

        $this->assertDatabaseMissing('note_mutation_events', [
            'note_id' => 'note-unpaid-row-reject',
            'mutation_type' => 'note_rows_canceled_via_refund',
        ]);
    }

    public function test_close_selected_row_refund_is_rejected_when_parent_note_is_open(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');
        $this->seedOpenNoteWithOnePaidLine($today);

        $this->actingAs($user)->post(route('cashier.notes.refunds.store', ['noteId' => 'note-1']), [
            'selected_row_ids' => ['wi-1'],
            'refunded_at' => $today,
            'reason' => 'Forged refund close row on open parent note',
        ]);

        $this->assertDatabaseCount('customer_refunds', 0);
        $this->assertDatabaseCount('refund_component_allocations', 0);

        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-1',
            'status' => WorkItem::STATUS_OPEN,
        ]);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'note_state' => Note::STATE_OPEN,
            'total_rupiah' => 100000,
        ]);

        $this->assertDatabaseMissing('note_mutation_events', [
            'note_id' => 'note-1',
            'mutation_type' => 'note_rows_canceled_via_refund',
        ]);
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Refund Selected Rows',
            'email' => 'kasir-refund-selected-rows@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedOpenNoteWithOnePaidLine(string $today): void
    {
        $this->seedNoteBase('note-1', 'Budi', $today, 100000, 'open');
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-1', 'Servis A', 50000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedWorkItemBase('wi-2', 'note-1', 2, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-2', 'Servis B', 50000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedCustomerPaymentBase('payment-1', 50000, $today);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-1',
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-1',
            'component_amount_rupiah_snapshot' => 50000,
            'allocated_amount_rupiah' => 50000,
            'allocation_priority' => 1,
        ]);
    }
}
