<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class ClosedNoteFullRefundExternalPurchaseLifecycleFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_selected_row_refund_for_closed_external_purchase_note_is_default_blocked(): void
    {
        $user = $this->seedKasir();
        $this->seedClosedPaidExternalPurchaseNote();

        $this->actingAs($user)
            ->from(route('cashier.notes.index'))
            ->post(route('cashier.notes.refunds.store', ['noteId' => 'note-1']), [
                'selected_row_ids' => ['wi-1'],
                'refunded_at' => date('Y-m-d'),
                'reason' => 'Refund full selected external row',
            ])
            ->assertRedirect(route('cashier.notes.index'))
            ->assertSessionHasErrors(['refund']);

        $this->assertDatabaseCount('customer_refunds', 0);
        $this->assertDatabaseCount('refund_component_allocations', 0);
        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'note_state' => 'closed',
        ]);

        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_full_selected_row_refund_for_closed_external_purchase_note_keeps_inventory_untouched_when_blocked(): void
    {
        $user = $this->seedKasir();
        $this->seedClosedPaidExternalPurchaseNote();

        $this->actingAs($user)
            ->from(route('cashier.notes.index'))
            ->post(route('cashier.notes.refunds.store', ['noteId' => 'note-1']), [
                'selected_row_ids' => ['wi-1'],
                'refunded_at' => date('Y-m-d'),
                'reason' => 'Refund full selected row and keep inventory untouched',
            ])
            ->assertRedirect(route('cashier.notes.index'))
            ->assertSessionHasErrors(['refund']);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'note_state' => 'closed',
        ]);

        $this->assertDatabaseCount('customer_refunds', 0);
        $this->assertDatabaseCount('refund_component_allocations', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Refund External Purchase',
            'email' => 'cashier-refund-external@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedClosedPaidExternalPurchaseNote(): void
    {
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 11000, 'closed');
        $this->seedWorkItemBase(
            'wi-1',
            'note-1',
            1,
            WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE,
            WorkItem::STATUS_OPEN,
            11000
        );
        $this->seedServiceDetailBase('wi-1', 'Servis + Barang Luar', 9000, ServiceDetail::PART_SOURCE_NONE);

        DB::table('work_item_external_purchase_lines')->insert([
            'id' => 'ext-1',
            'work_item_id' => 'wi-1',
            'cost_description' => 'Beli luar',
            'unit_cost_rupiah' => 2000,
            'qty' => 1,
            'line_total_rupiah' => 2000,
        ]);

        $this->seedCustomerPaymentBase('payment-1', 11000, $today);
        $this->seedPaymentAllocationBase('allocation-1', 'payment-1', 'note-1', 11000);

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pca-1',
                'customer_payment_id' => 'payment-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-1',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-1',
                'component_amount_rupiah_snapshot' => 9000,
                'allocated_amount_rupiah' => 9000,
                'allocation_priority' => 1,
            ],
            [
                'id' => 'pca-2',
                'customer_payment_id' => 'payment-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-1',
                'component_type' => 'service_external_purchase_part',
                'component_ref_id' => 'ext-1',
                'component_amount_rupiah_snapshot' => 2000,
                'allocated_amount_rupiah' => 2000,
                'allocation_priority' => 2,
            ],
        ]);
    }
}
