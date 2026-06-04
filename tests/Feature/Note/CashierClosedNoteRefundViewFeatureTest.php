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

final class CashierClosedNoteRefundViewFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_closed_note_detail_shows_refund_launcher_and_hides_payment_launcher(): void
    {
        $user = $this->seedKasir();
        $this->seedClosedPaidServiceOnlyNote();

        $this->actingAs($user)->get(route('cashier.notes.show', ['noteId' => 'note-1']))
            ->assertOk()
            ->assertSee('Detail Nota')
            ->assertSee('Info Nota')
            ->assertSee('Rincian Nota')
            ->assertSee('Review &amp; Pembayaran', false)
            ->assertSee('Riwayat Nota')
            ->assertSee('Refund')
            ->assertSee('Refund / Batalkan Line')
            ->assertDontSee('name="customer_payment_id"', false)
            ->assertDontSee('name="amount_rupiah"', false)
            ->assertDontSee('Lunasi')
            ->assertSee('Edit');
    }

    public function test_open_note_detail_shows_workspace_edit_and_payment_actions(): void
    {
        $user = $this->seedKasir();
        $this->seedOpenPartialPaidServiceOnlyNote();

        $this->actingAs($user)->get(route('cashier.notes.show', ['noteId' => 'note-1']))
            ->assertOk()
            ->assertSee('Detail Nota')
            ->assertSee('Info Nota')
            ->assertSee('Rincian Nota')
            ->assertSee('Review &amp; Pembayaran', false)
            ->assertSee('Riwayat Nota')
            ->assertSee('Edit')
            ->assertSee('Bayar')
            ->assertSee('Lunasi');
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();
        $user = User::query()->create(['name' => 'Kasir Refund View', 'email' => 'cashier-refund-view@example.test', 'password' => 'password']);
        DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => 'kasir']);
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
        DB::table('payment_component_allocations')->insert(['id' => 'pca-1', 'customer_payment_id' => 'payment-1', 'note_id' => 'note-1', 'work_item_id' => 'wi-1', 'component_type' => 'service_fee', 'component_ref_id' => 'wi-1', 'component_amount_rupiah_snapshot' => 50000, 'allocated_amount_rupiah' => 50000, 'allocation_priority' => 1]);
    }

    private function seedOpenPartialPaidServiceOnlyNote(): void
    {
        $today = date('Y-m-d');
        $this->seedNoteBase('note-1', 'Budi', $today, 50000, 'open');
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-1', 'Servis A', 50000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedCustomerPaymentBase('payment-1', 20000, $today);
        $this->seedPaymentAllocationBase('allocation-1', 'payment-1', 'note-1', 20000);
        DB::table('payment_component_allocations')->insert(['id' => 'pca-1', 'customer_payment_id' => 'payment-1', 'note_id' => 'note-1', 'work_item_id' => 'wi-1', 'component_type' => 'service_fee', 'component_ref_id' => 'wi-1', 'component_amount_rupiah_snapshot' => 50000, 'allocated_amount_rupiah' => 20000, 'allocation_priority' => 1]);
    }
}
