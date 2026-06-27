<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class CashierNoteRevisionSubmitFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_workspace_update_route_creates_new_revision_instead_of_overwriting_root_identity(): void
    {
        $user = $this->seedKasir();
        $this->seedOpenServiceOnlyNote();

        $this->actingAs($user)->get(route('cashier.notes.show', ['noteId' => 'note-1']))->assertOk();

        $response = $this->actingAs($user)->patch(route('cashier.notes.workspace.update', ['noteId' => 'note-1']), [
            'reason' => 'Koreksi manual dari workspace.',
            'note' => [
                'customer_name' => 'Budi Revised',
                'customer_phone' => '08123',
                'transaction_date' => date('Y-m-d'),
            ],
            'items' => [
                [
                    'entry_mode' => 'service',
                    'description' => null,
                    'part_source' => 'none',
                    'service' => [
                        'name' => 'Servis Revised',
                        'price_rupiah' => '75000',
                        'notes' => null,
                    ],
                    'product_lines' => [],
                    'external_purchase_lines' => [],
                ],
            ],
            'inline_payment' => [
                'decision' => 'skip',
            ],
        ]);

        $response->assertRedirect(route('cashier.notes.show', ['noteId' => 'note-1']));

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'current_revision_id' => 'note-1-r002',
            'latest_revision_number' => 2,
        ]);

        $this->assertDatabaseHas('note_revisions', [
            'id' => 'note-1-r002',
            'note_root_id' => 'note-1',
            'revision_number' => 2,
            'reason' => 'Koreksi manual dari workspace.',
            'customer_name' => 'Budi Revised',
        ]);

        $this->assertDatabaseHas('note_revision_settlements', [
            'id' => 'note-1-r002-settlement',
            'note_revision_id' => 'note-1-r002',
            'note_root_id' => 'note-1',
            'gross_total_rupiah' => 75000,
            'carry_forward_paid_rupiah' => 0,
            'carry_forward_refunded_rupiah' => 0,
            'net_paid_rupiah' => 0,
            'outstanding_rupiah' => 75000,
            'surplus_rupiah' => 0,
            'settlement_status' => 'underpaid',
        ]);
    }

    public function test_workspace_update_route_records_manual_dp_after_revised_total_and_keeps_existing_payment(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');

        $this->seedOpenServiceOnlyNote();
        $this->seedServiceOnlyCurrentRevision(
            'note-1',
            'note-1-r001',
            'wi-1',
            'Budi',
            $today,
            50000,
            'Servis Lama',
            50000,
        );
        $this->seedCustomerPaymentBase('payment-existing-dp-1', 20000, $today);
        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-existing-dp-1',
            'customer_payment_id' => 'payment-existing-dp-1',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'component_type' => PaymentComponentType::SERVICE_FEE,
            'component_ref_id' => 'wi-1',
            'component_amount_rupiah_snapshot' => 50000,
            'allocated_amount_rupiah' => 20000,
            'allocation_priority' => 1,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $response = $this->actingAs($user)->patch(route('cashier.notes.workspace.update', ['noteId' => 'note-1']), [
            'note' => [
                'customer_name' => 'Budi DP Revised',
                'customer_phone' => '08123',
                'transaction_date' => $today,
            ],
            'items' => [
                [
                    'entry_mode' => 'service',
                    'description' => null,
                    'part_source' => 'none',
                    'service' => [
                        'name' => 'Servis Revised DP',
                        'price_rupiah' => '100000',
                        'notes' => null,
                    ],
                    'product_lines' => [],
                    'external_purchase_lines' => [],
                ],
            ],
            'inline_payment' => [
                'decision' => 'pay_partial',
                'payment_method' => 'transfer',
                'paid_at' => $today,
                'amount_paid_rupiah' => '30000',
                'amount_received_rupiah' => null,
            ],
        ]);

        $response->assertRedirect(route('cashier.notes.show', ['noteId' => 'note-1']));
        $response->assertSessionHas('success', 'Revisi nota dan pembayaran berhasil dicatat.');

        $this->assertDatabaseHas('note_revisions', [
            'id' => 'note-1-r002',
            'note_root_id' => 'note-1',
            'revision_number' => 2,
            'customer_name' => 'Budi DP Revised',
            'grand_total_rupiah' => 100000,
        ]);

        $this->assertDatabaseHas('note_revision_settlements', [
            'id' => 'note-1-r002-settlement',
            'note_revision_id' => 'note-1-r002',
            'note_root_id' => 'note-1',
            'gross_total_rupiah' => 100000,
            'carry_forward_paid_rupiah' => 50000,
            'carry_forward_refunded_rupiah' => 0,
            'net_paid_rupiah' => 50000,
            'outstanding_rupiah' => 50000,
            'surplus_rupiah' => 0,
            'settlement_status' => 'underpaid',
        ]);

        $this->assertDatabaseHas('customer_payments', [
            'amount_rupiah' => 30000,
            'payment_method' => 'transfer',
            'paid_at' => $today,
        ]);

        $componentAllocatedTotal = (int) DB::table('payment_component_allocations')
            ->where('note_id', 'note-1')
            ->sum('allocated_amount_rupiah');

        self::assertSame(50000, $componentAllocatedTotal);
    }

    public function test_workspace_update_route_rejects_open_note_that_is_already_settled(): void
    {
        $user = $this->seedKasir();
        $this->seedOpenServiceOnlyNote();
        $this->seedServiceOnlyCurrentRevision(
            'note-1',
            'note-1-r001',
            'wi-1',
            'Budi',
            date('Y-m-d'),
            50000,
            'Servis Lama',
            50000,
        );
        $this->seedCustomerPaymentBase('payment-1', 50000, date('Y-m-d'));
        $this->seedPaymentAllocationBase('allocation-1', 'payment-1', 'note-1', 50000);

        $response = $this->actingAs($user)
            ->from(route('cashier.notes.workspace.edit', ['noteId' => 'note-1']))
            ->patch(route('cashier.notes.workspace.update', ['noteId' => 'note-1']), [
                'note' => [
                    'customer_name' => 'Budi Settled Rewrite',
                    'customer_phone' => '08123',
                    'transaction_date' => date('Y-m-d'),
                ],
                'items' => [
                    [
                        'entry_mode' => 'service',
                        'description' => null,
                        'part_source' => 'none',
                        'service' => [
                            'name' => 'Servis Settled Rewrite',
                            'price_rupiah' => '75000',
                            'notes' => null,
                        ],
                        'product_lines' => [],
                        'external_purchase_lines' => [],
                    ],
                ],
                'inline_payment' => [
                    'decision' => 'skip',
                ],
            ]);

        $response->assertRedirect(route('cashier.notes.workspace.edit', ['noteId' => 'note-1']));
        $response->assertSessionHasErrors([
            'revision' => 'Nota close tidak boleh diedit lewat workspace.',
        ]);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'customer_name' => 'Budi',
            'note_state' => 'open',
            'total_rupiah' => 50000,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-1',
            'note_id' => 'note-1',
            'subtotal_rupiah' => 50000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => 'wi-1',
            'service_name' => 'Servis Lama',
            'service_price_rupiah' => 50000,
        ]);

        $this->assertDatabaseHas('payment_allocations', [
            'id' => 'allocation-1',
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'amount_rupiah' => 50000,
        ]);

        $this->assertDatabaseMissing('note_revisions', [
            'id' => 'note-1-r002',
        ]);
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Revision Submit',
            'email' => 'kasir-revision-submit@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedOpenServiceOnlyNote(): void
    {
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 50000, 'open');
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-1', 'Servis Lama', 50000, ServiceDetail::PART_SOURCE_NONE);
    }
}
