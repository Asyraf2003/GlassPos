<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateTransactionWorkspaceServiceExternalPurchaseFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_store_workspace_service_with_external_purchase_payload_and_redirect_to_history(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Service External',
            'email' => 'service-external@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $response = $this->actingAs($user)->post(route('notes.workspace.store'), [
            'note' => [
                'customer_name' => 'Budi',
                'customer_phone' => '08123',
                'transaction_date' => '2026-03-15',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pay_now' => 0,
                'service' => [
                    'name' => 'Servis Bearing',
                    'price_rupiah' => 80000,
                    'notes' => '',
                ],
                'product_lines' => [[
                    'product_id' => '',
                    'qty' => '',
                    'unit_price_rupiah' => '',
                ]],
                'external_purchase_lines' => [[
                    'label' => 'Bearing NTN',
                    'qty' => 1,
                    'unit_cost_rupiah' => 120000,
                ]],
            ]],
            'inline_payment' => [
                'decision' => 'skip',
                'payment_method' => null,
                'paid_at' => '2026-03-15',
            ],
        ]);

        $response->assertRedirect(route('cashier.notes.index'));

        $noteId = (string) DB::table('notes')->value('id');
        $workItemId = (string) DB::table('work_items')->value('id');

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'customer_name' => 'Budi',
            'total_rupiah' => 200000,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => $workItemId,
            'note_id' => $noteId,
            'transaction_type' => 'service_with_external_purchase',
            'subtotal_rupiah' => 200000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => $workItemId,
            'service_name' => 'Servis Bearing',
            'service_price_rupiah' => 80000,
            'part_source' => 'none',
        ]);

        $this->assertDatabaseHas('work_item_external_purchase_lines', [
            'work_item_id' => $workItemId,
            'cost_description' => 'Bearing NTN',
            'qty' => 1,
            'unit_cost_rupiah' => 120000,
            'line_total_rupiah' => 120000,
        ]);

        $this->assertDatabaseCount('work_item_store_stock_lines', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }
    public function test_cashier_can_store_workspace_service_external_purchase_with_package_total_auto_split(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Service External Package',
            'email' => 'service-external-package@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $response = $this->actingAs($user)->post(route('notes.workspace.store'), [
            'note' => [
                'customer_name' => 'Budi External Package',
                'customer_phone' => '08123',
                'transaction_date' => '2026-03-15',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 180000,
                'pay_now' => 0,
                'service' => [
                    'name' => 'Servis Bearing External Package',
                    'price_rupiah' => 0,
                    'notes' => '',
                ],
                'product_lines' => [[
                    'product_id' => '',
                    'qty' => '',
                    'unit_price_rupiah' => '',
                ]],
                'external_purchase_lines' => [[
                    'label' => '',
                    'qty' => '',
                    'unit_cost_rupiah' => '',
                    'total_rupiah' => 80000,
                ]],
            ]],
            'inline_payment' => [
                'decision' => 'skip',
                'payment_method' => null,
                'paid_at' => '2026-03-15',
            ],
        ]);

        $response->assertRedirect(route('cashier.notes.index'));

        $noteId = (string) DB::table('notes')->where('customer_name', 'Budi External Package')->value('id');
        $workItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'customer_name' => 'Budi External Package',
            'total_rupiah' => 180000,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => $workItemId,
            'note_id' => $noteId,
            'transaction_type' => 'service_with_external_purchase',
            'subtotal_rupiah' => 180000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => $workItemId,
            'service_name' => 'Servis Bearing External Package',
            'service_price_rupiah' => 100000,
            'part_source' => 'none',
        ]);

        $this->assertDatabaseHas('work_item_external_purchase_lines', [
            'work_item_id' => $workItemId,
            'cost_description' => '',
            'qty' => 1,
            'unit_cost_rupiah' => 80000,
            'line_total_rupiah' => 80000,
        ]);

        $audit = DB::table('audit_logs')
            ->where('event', 'transaction_workspace_created')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);

        $context = json_decode((string) $audit->context, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($noteId, $context['note_id'] ?? null);
        $this->assertSame([
            [
                'work_item_id' => $workItemId,
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 180000,
                'external_total_rupiah' => 80000,
                'service_price_rupiah' => 100000,
                'source' => 'external_purchase',
                'label' => null,
                'qty' => null,
            ],
        ], $context['package_allocations'] ?? null);

        $this->assertDatabaseCount('work_item_store_stock_lines', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

}
