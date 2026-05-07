<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class CashierClosedReplacementOutstandingPaymentFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_cashier_can_pay_outstanding_after_closed_note_replacement(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 70000, 'closed');
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 70000);
        $this->seedServiceDetailBase('wi-1', 'Servis Mesin', 70000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedCustomerPaymentBase('pay-old', 50000, $today);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-old',
            'customer_payment_id' => 'pay-old',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-1',
            'component_amount_rupiah_snapshot' => 70000,
            'allocated_amount_rupiah' => 50000,
            'allocation_priority' => 20,
        ]);

        $this->seedServiceOnlyCurrentRevision(
            'note-1',
            'note-1-r001',
            'wi-1',
            'Budi',
            $today,
            70000,
            'Servis Mesin',
            70000,
        );

        $response = $this->actingAs($user)->post(
            route('cashier.notes.payments.store', ['noteId' => 'note-1']),
            [
                'selected_row_ids' => ['wi-1::service_fee::wi-1'],
                'payment_method' => 'cash',
                'paid_at' => $today,
                'amount_received' => 20000,
            ],
        );

        $response->assertRedirect(route('cashier.notes.show', ['noteId' => 'note-1']));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customer_payments', [
            'amount_rupiah' => 20000,
            'paid_at' => $today,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'component_type' => 'service_fee',
            'allocated_amount_rupiah' => 20000,
        ]);
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Closed Replacement Outstanding Payment',
            'email' => 'kasir-closed-replacement-outstanding-payment@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }
}
