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

final class CashierNoteDetailPaymentActionPolicyFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_unpaid_note_shows_partial_and_settle_actions(): void
    {
        $user = $this->seedKasir();
        $this->seedServiceNote('note-unpaid', 'wi-unpaid', 50000);

        $response = $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-unpaid']));

        $response->assertOk();
        $response->assertSee('Bayar Sebagian');
        $response->assertSee('Lunasi');
    }

    public function test_partially_paid_note_still_shows_partial_and_settle_actions(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');

        $this->seedServiceNote('note-partial', 'wi-partial', 50000);
        $this->seedCustomerPaymentBase('pay-partial', 20000, $today);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-partial',
            'customer_payment_id' => 'pay-partial',
            'note_id' => 'note-partial',
            'work_item_id' => 'wi-partial',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-partial',
            'component_amount_rupiah_snapshot' => 50000,
            'allocated_amount_rupiah' => 20000,
            'allocation_priority' => 20,
        ]);

        $response = $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-partial']));

        $response->assertOk();
        $response->assertSee('Bayar Sebagian');
        $response->assertSee('Lunasi');
    }

    public function test_closed_note_with_new_outstanding_after_replacement_shows_partial_and_settle_actions(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');

        $this->seedServiceNote('note-replaced', 'wi-replaced', 70000, 'closed');
        $this->seedCustomerPaymentBase('pay-replaced', 50000, $today);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-replaced',
            'customer_payment_id' => 'pay-replaced',
            'note_id' => 'note-replaced',
            'work_item_id' => 'wi-replaced',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-replaced',
            'component_amount_rupiah_snapshot' => 70000,
            'allocated_amount_rupiah' => 50000,
            'allocation_priority' => 20,
        ]);

        $response = $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-replaced']));

        $response->assertOk();
        $response->assertSee('Belum Lunas / Sebagian');
        $response->assertSee('Bayar Sebagian');
        $response->assertSee('Lunasi');
    }

    public function test_fully_paid_note_hides_payment_actions(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');

        $this->seedServiceNote('note-paid', 'wi-paid', 50000, 'closed');
        $this->seedCustomerPaymentBase('pay-paid', 50000, $today);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-paid',
            'customer_payment_id' => 'pay-paid',
            'note_id' => 'note-paid',
            'work_item_id' => 'wi-paid',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-paid',
            'component_amount_rupiah_snapshot' => 50000,
            'allocated_amount_rupiah' => 50000,
            'allocation_priority' => 20,
        ]);

        $response = $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-paid']));

        $response->assertOk();
        $response->assertSee('Lunas');
        $response->assertDontSee('Bayar Sebagian');
        $response->assertDontSee('Lunasi');
        $response->assertDontSee('Buka Modal Bayar');
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Payment Action Policy',
            'email' => 'kasir-payment-action-policy@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedServiceNote(
        string $noteId,
        string $workItemId,
        int $totalRupiah,
        string $noteState = 'open',
    ): void {
        $today = date('Y-m-d');

        $this->seedNoteBase($noteId, 'Budi', $today, $totalRupiah, $noteState);
        $this->seedWorkItemBase(
            $workItemId,
            $noteId,
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            WorkItem::STATUS_OPEN,
            $totalRupiah,
        );
        $this->seedServiceDetailBase(
            $workItemId,
            'Servis Mesin',
            $totalRupiah,
            ServiceDetail::PART_SOURCE_NONE,
        );
    }
}
