<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class UpdateTransactionWorkspaceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_submit_workspace_update_as_new_revision(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Update Workspace',
            'email' => 'update-workspace@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        DB::table('notes')->insert([
            'id' => 'note-update-1',
            'customer_name' => 'Budi Lama',
            'customer_phone' => '0811111111',
            'transaction_date' => date('Y-m-d'),
            'total_rupiah' => 50000,
            'note_state' => 'open',
        ]);

        DB::table('work_items')->insert([
            'id' => 'work-item-old-1',
            'note_id' => 'note-update-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 50000,
        ]);

        DB::table('work_item_service_details')->insert([
            'work_item_id' => 'work-item-old-1',
            'service_name' => 'Servis Lama',
            'service_price_rupiah' => 50000,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);

        $this->actingAs($user)->get(route('cashier.notes.show', ['noteId' => 'note-update-1']))->assertOk();

        $response = $this->actingAs($user)->patch(
            route('cashier.notes.workspace.update', ['noteId' => 'note-update-1']),
            [
            'base_revision_id' => $this->revisionBaseForTest('note-update-1'),
                'note' => [
                    'customer_name' => 'Budi Baru',
                    'customer_phone' => '0822222222',
                    'transaction_date' => date('Y-m-d'),
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
                    'decision' => 'skip',
                ],
            ],
        );

        $response->assertRedirect(route('cashier.notes.show', ['noteId' => 'note-update-1']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('notes', [
            'id' => 'note-update-1',
            'current_revision_id' => 'note-update-1-r002',
            'latest_revision_number' => 2,
        ]);

        $this->assertDatabaseHas('note_revisions', [
            'id' => 'note-update-1-r002',
            'note_root_id' => 'note-update-1',
            'revision_number' => 2,
            'customer_name' => 'Budi Baru',
            'customer_phone' => '0822222222',
            'grand_total_rupiah' => 70000,
        ]);

        $this->assertDatabaseHas('note_revision_lines', [
            'note_revision_id' => 'note-update-1-r002',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'subtotal_rupiah' => 70000,
            'service_label' => 'Servis Baru',
            'service_price_rupiah' => 70000,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'note_revision_created',
        ]);
    }

    public function test_cashier_can_submit_workspace_update_and_keep_payment_outside_revision_creation(): void
    {
        $this->loginAsKasir();

        $today = date('Y-m-d');

        $user = User::query()->create([
            'name' => 'Kasir Update Workspace Bayar',
            'email' => 'update-workspace-payment@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        DB::table('notes')->insert([
            'id' => 'note-update-payment-1',
            'customer_name' => 'Budi Lama',
            'customer_phone' => '0811111111',
            'transaction_date' => $today,
            'total_rupiah' => 50000,
            'note_state' => 'open',
        ]);

        DB::table('work_items')->insert([
            'id' => 'work-item-update-payment-old-1',
            'note_id' => 'note-update-payment-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 50000,
        ]);

        DB::table('work_item_service_details')->insert([
            'work_item_id' => 'work-item-update-payment-old-1',
            'service_name' => 'Servis Lama',
            'service_price_rupiah' => 50000,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);

        $this->actingAs($user)->get(route('cashier.notes.show', ['noteId' => 'note-update-payment-1']))->assertOk();

        $response = $this->actingAs($user)->patch(
            route('cashier.notes.workspace.update', ['noteId' => 'note-update-payment-1']),
            [
            'base_revision_id' => $this->revisionBaseForTest('note-update-payment-1'),
                'note' => [
                    'customer_name' => 'Budi Bayar',
                    'customer_phone' => '0822222222',
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
                    'decision' => 'skip',
                ],
            ],
        );

        $response->assertRedirect(route('cashier.notes.show', ['noteId' => 'note-update-payment-1']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('notes', [
            'id' => 'note-update-payment-1',
            'current_revision_id' => 'note-update-payment-1-r002',
            'latest_revision_number' => 2,
        ]);

        $this->assertDatabaseHas('note_revisions', [
            'id' => 'note-update-payment-1-r002',
            'note_root_id' => 'note-update-payment-1',
            'revision_number' => 2,
            'customer_name' => 'Budi Bayar',
            'customer_phone' => '0822222222',
            'grand_total_rupiah' => 70000,
        ]);

        $this->assertDatabaseMissing('customer_payments', [
            'paid_at' => $today,
            'amount_rupiah' => 30000,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'note_revision_created',
        ]);
    }

}
