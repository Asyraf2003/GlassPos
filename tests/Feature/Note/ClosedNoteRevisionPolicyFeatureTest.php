<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class ClosedNoteRevisionPolicyFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_admin_can_submit_closed_paid_note_revision_through_active_revision_route(): void
    {
        $user = $this->loginAsAuthorizedAdmin();

        $this->seedClosedPaidServiceOnlyNote();

        $response = $this->actingAs($user)->patch(
            route('admin.notes.workspace.update', ['noteId' => 'note-closed-policy-001']),
            $this->revisionPayload('Budi Closed Policy Admin Revised', 120000),
        );

        $response->assertRedirect(route('admin.notes.show', ['noteId' => 'note-closed-policy-001']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('notes', [
            'id' => 'note-closed-policy-001',
            'customer_name' => 'Budi Closed Policy Admin Revised',
            'customer_phone' => '08123456789',
            'transaction_date' => '2026-05-21',
            'total_rupiah' => 120000,
            'current_revision_id' => 'note-closed-policy-001-r002',
            'latest_revision_number' => 2,
        ]);

        $this->assertDatabaseHas('note_revisions', [
            'id' => 'note-closed-policy-001-r002',
            'note_root_id' => 'note-closed-policy-001',
            'revision_number' => 2,
            'customer_name' => 'Budi Closed Policy Admin Revised',
            'grand_total_rupiah' => 120000,
        ]);

        $this->assertDatabaseHas('note_revision_settlements', [
            'id' => 'note-closed-policy-001-r002-settlement',
            'note_revision_id' => 'note-closed-policy-001-r002',
            'note_root_id' => 'note-closed-policy-001',
            'gross_total_rupiah' => 120000,
            'carry_forward_paid_rupiah' => 100000,
            'carry_forward_refunded_rupiah' => 0,
            'net_paid_rupiah' => 100000,
            'outstanding_rupiah' => 20000,
            'surplus_rupiah' => 0,
            'settlement_status' => 'underpaid',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'note_revision_created',
        ]);
    }

    public function test_cashier_cannot_submit_closed_paid_note_revision_through_active_revision_route(): void
    {
        $user = $this->seedKasir();

        $this->seedClosedPaidServiceOnlyNote();

        $response = $this->actingAs($user)->patch(
            route('cashier.notes.workspace.update', ['noteId' => 'note-closed-policy-001']),
            $this->revisionPayload('Budi Closed Policy Cashier Revised', 120000),
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('notes', [
            'id' => 'note-closed-policy-001',
            'customer_name' => 'Budi Closed Policy Original',
            'customer_phone' => null,
            'transaction_date' => '2026-05-20',
            'total_rupiah' => 100000,
            'current_revision_id' => 'note-closed-policy-001-r001',
            'latest_revision_number' => 1,
        ]);

        $this->assertDatabaseMissing('note_revisions', [
            'id' => 'note-closed-policy-001-r002',
            'note_root_id' => 'note-closed-policy-001',
        ]);

        $this->assertDatabaseMissing('note_revision_settlements', [
            'id' => 'note-closed-policy-001-r002-settlement',
            'note_root_id' => 'note-closed-policy-001',
        ]);
    }

    private function seedClosedPaidServiceOnlyNote(): void
    {
        $this->seedNoteBase(
            'note-closed-policy-001',
            'Budi Closed Policy Original',
            '2026-05-20',
            100000,
            'closed',
        );

        $this->seedWorkItemBase(
            'wi-closed-policy-old-001',
            'note-closed-policy-001',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            WorkItem::STATUS_OPEN,
            100000,
        );

        $this->seedServiceDetailBase(
            'wi-closed-policy-old-001',
            'Servis Closed Policy Original',
            100000,
            ServiceDetail::PART_SOURCE_NONE,
        );

        $this->seedServiceOnlyCurrentRevision(
            'note-closed-policy-001',
            'note-closed-policy-001-r001',
            'wi-closed-policy-old-001',
            'Budi Closed Policy Original',
            '2026-05-20',
            100000,
            'Servis Closed Policy Original',
            100000,
        );

        $this->seedCustomerPaymentBase(
            'payment-closed-policy-001',
            100000,
            '2026-05-20',
        );

        $this->seedPaymentAllocationBase(
            'payment-allocation-closed-policy-001',
            'payment-closed-policy-001',
            'note-closed-policy-001',
            100000,
        );

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-closed-policy-old-001',
            'customer_payment_id' => 'payment-closed-policy-001',
            'note_id' => 'note-closed-policy-001',
            'work_item_id' => 'wi-closed-policy-old-001',
            'component_type' => PaymentComponentType::SERVICE_FEE,
            'component_ref_id' => 'wi-closed-policy-old-001',
            'component_amount_rupiah_snapshot' => 100000,
            'allocated_amount_rupiah' => 100000,
            'allocation_priority' => 1,
        ]);
    }

    /** @return array<string, mixed> */
    private function revisionPayload(string $customerName, int $servicePriceRupiah): array
    {
        return [
            'reason' => 'Closed note revision policy characterization.',
            'note' => [
                'customer_name' => $customerName,
                'customer_phone' => '08123456789',
                'transaction_date' => '2026-05-21',
            ],
            'items' => [
                [
                    'entry_mode' => 'service',
                    'description' => null,
                    'part_source' => 'none',
                    'service' => [
                        'name' => 'Servis Closed Policy Revised',
                        'price_rupiah' => $servicePriceRupiah,
                        'notes' => null,
                    ],
                    'product_lines' => [],
                    'external_purchase_lines' => [],
                ],
            ],
        ];
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Closed Policy',
            'email' => 'kasir-closed-policy@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }
}
