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

final class CashierHybridNoteDetailFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_open_note_detail_shows_revision_and_hybrid_payment_sections(): void
    {
        $user = $this->seedKasir();
        $this->seedOpenPartialPaidServiceOnlyNote();

        $this->actingAs($user)->get(route('cashier.notes.show', ['noteId' => 'note-1']))
            ->assertOk()
            ->assertSee('Riwayat Nota')
            ->assertSee('Riwayat Perubahan Nota')
            ->assertSee('Perubahan Aktif')
            ->assertSee('Rincian Nota')
            ->assertSee('Bayar')
            ->assertSee('Lunasi');
    }

    public function test_open_note_without_additional_revision_history_still_shows_revision_section(): void
    {
        $user = $this->seedKasir();
        $this->seedOpenPartialPaidServiceOnlyNote();

        $this->actingAs($user)->get(route('cashier.notes.show', ['noteId' => 'note-1']))
            ->assertOk()
            ->assertSee('Riwayat Nota')
            ->assertSee('Riwayat Perubahan Nota')
            ->assertSee('Perubahan Aktif');
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Hybrid Detail',
            'email' => 'kasir-hybrid-detail@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedOpenPartialPaidServiceOnlyNote(): void
    {
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 50000, 'open');
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-1', 'Servis Hybrid', 50000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedCustomerPaymentBase('payment-1', 20000, $today);
        $this->seedPaymentAllocationBase('allocation-1', 'payment-1', 'note-1', 20000);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-1',
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-1',
            'component_amount_rupiah_snapshot' => 50000,
            'allocated_amount_rupiah' => 20000,
            'allocation_priority' => 1,
        ]);
    }
}
