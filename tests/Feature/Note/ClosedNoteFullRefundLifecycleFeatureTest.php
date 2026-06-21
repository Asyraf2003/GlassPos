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

final class ClosedNoteFullRefundLifecycleFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_full_refund_for_closed_service_only_note_is_default_blocked(): void
    {
        $user = $this->seedKasir();
        $this->seedClosedPaidServiceOnlyNote();

        $this->actingAs($user)
            ->from(route('cashier.notes.index'))
            ->post(route('cashier.notes.refunds.store', ['noteId' => 'note-1']), [
                'selected_row_ids' => ['wi-1'],
                'customer_payment_id' => 'payment-1',
                'amount_rupiah' => 50000,
                'refunded_at' => date('Y-m-d'),
                'reason' => 'Refund penuh servis',
            ])
            ->assertRedirect(route('cashier.notes.index'))
            ->assertSessionHasErrors(['refund']);

        $this->assertDatabaseCount('customer_refunds', 0);
        $this->assertDatabaseCount('refund_component_allocations', 0);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'note_state' => 'closed',
        ]);
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Refund Lifecycle',
            'email' => 'cashier-refund-lifecycle@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedClosedPaidServiceOnlyNote(): void
    {
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 50000, 'closed');
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-1', 'Servis A', 50000, ServiceDetail::PART_SOURCE_NONE);

        $this->seedCustomerPaymentBase('payment-1', 50000, $today);
        $this->seedPaymentAllocationBase('allocation-1', 'payment-1', 'note-1', 50000);

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
