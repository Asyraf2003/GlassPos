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

final class CashierClosedNoteWorkspaceReplacementSubmitFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_cashier_cannot_submit_closed_note_workspace_replacement_as_new_revision(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi Lama', $today, 50000, 'closed');
        $this->seedWorkItemBase('wi-old-1', 'note-1', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-old-1', 'Servis Lama', 50000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedCustomerPaymentBase('pay-1', 50000, $today);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-old-1',
            'customer_payment_id' => 'pay-1',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-old-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-old-1',
            'component_amount_rupiah_snapshot' => 50000,
            'allocated_amount_rupiah' => 50000,
            'allocation_priority' => 20,
        ]);

        $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-1']))
            ->assertOk();

        $response = $this->actingAs($user)->patch(
            route('cashier.notes.workspace.update', ['noteId' => 'note-1']),
            [
            'base_revision_id' => $this->revisionBaseForTest('note-1'),
                'note' => [
                    'customer_name' => 'Budi Baru',
                    'customer_phone' => '08123456789',
                    'transaction_date' => $today,
                ],
                'items' => [
                    [
                        'entry_mode' => 'service',
                        'description' => null,
                        'part_source' => 'none',
                        'service' => [
                            'name' => 'Servis Baru',
                            'price_rupiah' => '70000',
                            'notes' => null,
                        ],
                        'product_lines' => [],
                        'external_purchase_lines' => [],
                    ],
                ],
                'inline_payment' => [
                    'decision' => 'pay_full',
                    'payment_method' => null,
                    'paid_at' => null,
                    'amount_paid_rupiah' => null,
                    'amount_received_rupiah' => null,
                ],
            ],
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'customer_name' => 'Budi Lama',
            'customer_phone' => null,
            'transaction_date' => $today,
            'total_rupiah' => 50000,
            'latest_revision_number' => 1,
        ]);

        $this->assertDatabaseMissing('notes', [
            'id' => 'note-1',
            'customer_name' => 'Budi Baru',
            'total_rupiah' => 70000,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-old-1',
            'note_id' => 'note-1',
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'subtotal_rupiah' => 50000,
        ]);

        $this->assertDatabaseMissing('note_revisions', [
            'note_root_id' => 'note-1',
            'revision_number' => 2,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'id' => 'pca-old-1',
            'customer_payment_id' => 'pay-1',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-old-1',
            'allocated_amount_rupiah' => 50000,
        ]);
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Closed Replacement Submit',
            'email' => 'kasir-closed-replacement-submit@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }
}
