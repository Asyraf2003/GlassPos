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

final class CashierRefundRejectsOpenLineFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_detail_does_not_render_open_partially_paid_line_as_refundable(): void
    {
        $user = $this->seedKasir();
        $this->seedOpenPartialPaidNote();

        $html = $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-1']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Servis Open', $html);
        $this->assertStringNotContainsString('data-refund-row="1"', $html);
        $this->assertStringNotContainsString('data-row-id="wi-1"', $html);
    }

    public function test_refund_rejects_operationally_open_partially_paid_line_selection(): void
    {
        $user = $this->seedKasir();
        $this->seedOpenPartialPaidNote();

        $this->from(route('cashier.notes.show', ['noteId' => 'note-1']))
            ->actingAs($user)
            ->post(route('cashier.notes.refunds.store', ['noteId' => 'note-1']), [
                'selected_row_ids' => ['wi-1'],
                'stock_returns' => ['wi-1' => true],
                'refunded_at' => date('Y-m-d'),
                'reason' => 'Refund line open operasional',
            ]);

        $this->assertDatabaseCount('refund_component_allocations', 0);

        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-1',
            'status' => WorkItem::STATUS_OPEN,
        ]);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'note_state' => 'open',
            'total_rupiah' => 20000,
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
            'name' => 'Kasir Refund Open',
            'email' => 'kasir-refund-open@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedOpenPartialPaidNote(): void
    {
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 20000, 'open');
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 20000);
        $this->seedServiceDetailBase('wi-1', 'Servis Open', 20000, ServiceDetail::PART_SOURCE_NONE);

        $this->seedCustomerPaymentBase('payment-1', 10000, $today);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-1',
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-1',
            'component_amount_rupiah_snapshot' => 20000,
            'allocated_amount_rupiah' => 10000,
            'allocation_priority' => 1,
        ]);
    }
}
