<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class CashierNoteSurplusRefundDueUiAccessFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_cashier_detail_does_not_render_admin_refund_due_action_when_pending_surplus_exists(): void
    {
        $cashier = $this->seedCashier();

        $this->seedClosedPaidCurrentRevisionNote(
            noteId: 'note-cashier-surplus-ui-001',
            revisionId: 'note-cashier-surplus-ui-001-r001',
            workItemId: 'wi-cashier-surplus-ui-001',
        );

        $this->seedPendingSurplusSettlement(
            settlementId: 'settlement-cashier-surplus-ui-001',
            noteId: 'note-cashier-surplus-ui-001',
            revisionId: 'note-cashier-surplus-ui-001-r001',
            surplusRupiah: 122000,
        );

        $response = $this->actingAs($cashier)
            ->get(route('cashier.notes.show', ['noteId' => 'note-cashier-surplus-ui-001']));

        $response->assertOk();
        $response->assertDontSee('Tandai Pengembalian Belum Dibayar');
        $response->assertDontSee(route('admin.notes.revision-settlements.refund-due.store', [
            'settlementId' => 'settlement-cashier-surplus-ui-001',
        ]), false);
        $response->assertDontSee('data-refund-due-form', false);
        $response->assertDontSee('data-refund-due-submit', false);
        $response->assertDontSee('data-refund-due-max-rupiah="122000"', false);
    }

    private function seedCashier(): User
    {
        $user = User::query()->create([
            'name' => 'Cashier Surplus UI',
            'email' => 'cashier-surplus-ui@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedClosedPaidCurrentRevisionNote(
        string $noteId,
        string $revisionId,
        string $workItemId,
    ): void {
        $transactionDate = date('Y-m-d');

        $this->seedNoteBase($noteId, 'Customer Cashier Surplus UI', $transactionDate, 143000, 'closed');
        $this->seedWorkItemBase($workItemId, $noteId, 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 143000);
        $this->seedServiceDetailBase($workItemId, 'Servis Cashier Surplus UI', 143000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedCustomerPaymentBase('pay-'.$noteId, 143000, $transactionDate);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-'.$noteId,
            'customer_payment_id' => 'pay-'.$noteId,
            'note_id' => $noteId,
            'work_item_id' => $workItemId,
            'component_type' => 'service_fee',
            'component_ref_id' => $workItemId,
            'component_amount_rupiah_snapshot' => 143000,
            'allocated_amount_rupiah' => 143000,
            'allocation_priority' => 20,
        ]);

        $this->seedServiceOnlyCurrentRevision(
            noteId: $noteId,
            revisionId: $revisionId,
            workItemId: $workItemId,
            customerName: 'Customer Cashier Surplus UI',
            transactionDate: $transactionDate,
            grandTotalRupiah: 143000,
            serviceName: 'Servis Cashier Surplus UI',
            servicePriceRupiah: 143000,
            status: WorkItem::STATUS_OPEN,
            customerPhone: '08123456789',
        );
    }

    private function seedPendingSurplusSettlement(
        string $settlementId,
        string $noteId,
        string $revisionId,
        int $surplusRupiah,
    ): void {
        DB::table('note_revision_settlements')->insert([
            'id' => $settlementId,
            'note_revision_id' => $revisionId,
            'note_root_id' => $noteId,
            'gross_total_rupiah' => 143000,
            'carry_forward_paid_rupiah' => 265000,
            'carry_forward_refunded_rupiah' => 0,
            'net_paid_rupiah' => 265000,
            'outstanding_rupiah' => 0,
            'surplus_rupiah' => $surplusRupiah,
            'settlement_status' => 'overpaid_pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => null,
        ]);
    }
}
