<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class EditTransactionWorkspacePackageAutoSplitCharacterizationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_edit_workspace_preloads_service_store_stock_package_auto_split_multi_product_revision(): void
    {
        $this->seedOpenMultiProductPackageNote();

        $user = User::query()->create([
            'name' => 'Admin Package Revision Characterization',
            'email' => 'admin-package-revision-characterization@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get(
            route('admin.notes.workspace.edit', ['noteId' => 'note-edit-package-multi-001'])
        );

        $response->assertOk();

        $response->assertSee('Servis Paket Multi Original', false);
        $response->assertSee('product-package-edit-a', false);
        $response->assertSee('product-package-edit-b', false);
        $response->assertSee('250000', false);
    }


    public function test_admin_can_submit_service_store_stock_package_auto_split_multi_product_revision(): void
    {
        $this->seedOpenMultiProductPackageNote();

        $user = User::query()->create([
            'name' => 'Admin Package Revision Submit Characterization',
            'email' => 'admin-package-revision-submit-characterization@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        DB::table('admin_transaction_capability_states')->updateOrInsert(
            ['actor_id' => (string) $user->getAuthIdentifier()],
            ['active' => true],
        );

        $response = $this->actingAs($user)->patch(
            route('admin.notes.workspace.update', ['noteId' => 'note-edit-package-multi-001']),
            [
                'reason' => 'Package multi-product revision submit characterization.',
                'note' => [
                    'customer_name' => 'Budi Edit Package Multi Revised',
                    'customer_phone' => '08123456789',
                    'transaction_date' => '2026-06-01',
                    'operational_note' => 'Alasan revisi package multi.',
                ],
                'items' => [
                    [
                        'entry_mode' => 'service',
                        'description' => null,
                        'part_source' => 'store_stock',
                        'pricing_mode' => 'package_auto_split',
                        'package_total_rupiah' => 300000,
                        'service' => [
                            'name' => 'Servis Paket Multi Revised',
                            'price_rupiah' => 0,
                            'notes' => null,
                        ],
                        'product_lines' => [
                            [
                                'product_id' => 'product-package-edit-a',
                                'qty' => 2,
                                'unit_price_rupiah' => 50000,
                                'price_basis' => 'revision_snapshot',
                            ],
                            [
                                'product_id' => 'product-package-edit-b',
                                'qty' => 2,
                                'unit_price_rupiah' => 30000,
                                'price_basis' => 'revision_snapshot',
                            ],
                        ],
                        'external_purchase_lines' => [],
                    ],
                ],
            ]
        );

        $response->assertRedirect(route('admin.notes.show', ['noteId' => 'note-edit-package-multi-001']));

        $this->assertDatabaseHas('notes', [
            'id' => 'note-edit-package-multi-001',
            'customer_name' => 'Budi Edit Package Multi Revised',
            'customer_phone' => '08123456789',
            'transaction_date' => '2026-06-01',
            'operational_note' => 'Alasan revisi package multi.',
            'total_rupiah' => 300000,
            'current_revision_id' => 'note-edit-package-multi-001-r002',
            'latest_revision_number' => 2,
        ]);

        $this->assertDatabaseHas('note_revisions', [
            'id' => 'note-edit-package-multi-001-r002',
            'note_root_id' => 'note-edit-package-multi-001',
            'revision_number' => 2,
            'parent_revision_id' => 'note-edit-package-multi-001-r001',
            'reason' => 'Package multi-product revision submit characterization.',
            'customer_name' => 'Budi Edit Package Multi Revised',
            'customer_phone' => '08123456789',
            'transaction_date' => '2026-06-01',
            'grand_total_rupiah' => 300000,
            'line_count' => 1,
        ]);

        $workItemId = (string) DB::table('work_items')
            ->where('note_id', 'note-edit-package-multi-001')
            ->value('id');

        self::assertNotSame('', $workItemId);

        $this->assertDatabaseCount('work_items', 1);
        $this->assertDatabaseHas('work_items', [
            'id' => $workItemId,
            'note_id' => 'note-edit-package-multi-001',
            'transaction_type' => WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            'subtotal_rupiah' => 300000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => $workItemId,
            'service_name' => 'Servis Paket Multi Revised',
            'service_price_rupiah' => 28000,
            'package_profit_rupiah' => 112000,
            'part_source' => 'none',
        ]);

        $this->assertDatabaseCount('work_item_store_stock_lines', 2);
        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'work_item_id' => $workItemId,
            'product_id' => 'product-package-edit-a',
            'qty' => 2,
            'line_total_rupiah' => 100000,
        ]);
        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'work_item_id' => $workItemId,
            'product_id' => 'product-package-edit-b',
            'qty' => 2,
            'line_total_rupiah' => 60000,
        ]);

        $revisionPayload = DB::table('note_revision_lines')
            ->where('note_revision_id', 'note-edit-package-multi-001-r002')
            ->where('work_item_root_id', $workItemId)
            ->value('payload');

        self::assertIsString($revisionPayload);

        $decoded = json_decode($revisionPayload, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('package_auto_split', $decoded['pricing_mode'] ?? null);
        self::assertSame(300000, $decoded['package_total_rupiah'] ?? null);
        self::assertSame(160000, $decoded['parts_total_rupiah'] ?? null);
        self::assertSame(28000, $decoded['service_price_rupiah'] ?? null);
        self::assertArrayHasKey('package_base_service_price_rupiah', $decoded);
        self::assertNull($decoded['package_base_service_price_rupiah']);
        self::assertSame(0, $decoded['package_service_extra_rupiah'] ?? null);
        self::assertSame(112000, $decoded['package_profit_rupiah'] ?? null);
        self::assertSame(140000, $decoded['total_service_component_rupiah'] ?? null);
        self::assertSame(28000, $decoded['service']['service_price_rupiah'] ?? null);
        self::assertCount(2, $decoded['store_stock_lines'] ?? []);
    }


    public function test_package_auto_split_multi_product_revision_reverses_old_stock_and_issues_replacement_stock(): void
    {
        $this->seedOpenMultiProductPackageNote();

        DB::table('product_inventory')
            ->where('product_id', 'product-package-edit-a')
            ->update(['qty_on_hand' => 8]);

        DB::table('product_inventory')
            ->where('product_id', 'product-package-edit-b')
            ->update(['qty_on_hand' => 9]);

        DB::table('product_inventory_costing')
            ->where('product_id', 'product-package-edit-a')
            ->update([
                'avg_cost_rupiah' => 35000,
                'inventory_value_rupiah' => 280000,
            ]);

        DB::table('product_inventory_costing')
            ->where('product_id', 'product-package-edit-b')
            ->update([
                'avg_cost_rupiah' => 20000,
                'inventory_value_rupiah' => 180000,
            ]);

        DB::table('inventory_movements')->insert([
            [
                'id' => 'move-edit-package-old-a',
                'product_id' => 'product-package-edit-a',
                'movement_type' => 'stock_out',
                'source_type' => 'work_item_store_stock_line',
                'source_id' => 'ssl-edit-package-multi-a',
                'tanggal_mutasi' => '2026-05-31',
                'qty_delta' => -2,
                'unit_cost_rupiah' => 35000,
                'total_cost_rupiah' => -70000,
            ],
            [
                'id' => 'move-edit-package-old-b',
                'product_id' => 'product-package-edit-b',
                'movement_type' => 'stock_out',
                'source_type' => 'work_item_store_stock_line',
                'source_id' => 'ssl-edit-package-multi-b',
                'tanggal_mutasi' => '2026-05-31',
                'qty_delta' => -1,
                'unit_cost_rupiah' => 20000,
                'total_cost_rupiah' => -20000,
            ],
        ]);

        $user = User::query()->create([
            'name' => 'Admin Package Revision Inventory Characterization',
            'email' => 'admin-package-revision-inventory-characterization@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        DB::table('admin_transaction_capability_states')->updateOrInsert(
            ['actor_id' => (string) $user->getAuthIdentifier()],
            ['active' => true],
        );

        $response = $this->actingAs($user)->patch(
            route('admin.notes.workspace.update', ['noteId' => 'note-edit-package-multi-001']),
            [
                'reason' => 'Package multi-product inventory revision characterization.',
                'note' => [
                    'customer_name' => 'Budi Edit Package Multi Inventory Revised',
                    'customer_phone' => '08123456789',
                    'transaction_date' => '2026-06-01',
                    'operational_note' => 'Alasan revisi stok package multi.',
                ],
                'items' => [
                    [
                        'entry_mode' => 'service',
                        'description' => null,
                        'part_source' => 'store_stock',
                        'pricing_mode' => 'package_auto_split',
                        'package_total_rupiah' => 300000,
                        'service' => [
                            'name' => 'Servis Paket Multi Inventory Revised',
                            'price_rupiah' => 0,
                            'notes' => null,
                        ],
                        'product_lines' => [
                            [
                                'product_id' => 'product-package-edit-a',
                                'qty' => 2,
                                'unit_price_rupiah' => 50000,
                                'price_basis' => 'revision_snapshot',
                            ],
                            [
                                'product_id' => 'product-package-edit-b',
                                'qty' => 2,
                                'unit_price_rupiah' => 30000,
                                'price_basis' => 'revision_snapshot',
                            ],
                        ],
                        'external_purchase_lines' => [],
                    ],
                ],
            ]
        );

        $response->assertRedirect(route('admin.notes.show', ['noteId' => 'note-edit-package-multi-001']));

        $workItemId = (string) DB::table('work_items')
            ->where('note_id', 'note-edit-package-multi-001')
            ->value('id');

        self::assertNotSame('', $workItemId);

        $replacementLineIds = DB::table('work_item_store_stock_lines')
            ->where('work_item_id', $workItemId)
            ->pluck('id', 'product_id');

        self::assertArrayHasKey('product-package-edit-a', $replacementLineIds->all());
        self::assertArrayHasKey('product-package-edit-b', $replacementLineIds->all());

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-package-edit-a',
            'movement_type' => 'stock_in',
            'source_type' => 'transaction_workspace_updated',
            'source_id' => 'ssl-edit-package-multi-a',
            'tanggal_mutasi' => '2026-06-01',
            'qty_delta' => 2,
            'unit_cost_rupiah' => 35000,
            'total_cost_rupiah' => 70000,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-package-edit-b',
            'movement_type' => 'stock_in',
            'source_type' => 'transaction_workspace_updated',
            'source_id' => 'ssl-edit-package-multi-b',
            'tanggal_mutasi' => '2026-06-01',
            'qty_delta' => 1,
            'unit_cost_rupiah' => 20000,
            'total_cost_rupiah' => 20000,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-package-edit-a',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => (string) $replacementLineIds['product-package-edit-a'],
            'tanggal_mutasi' => '2026-06-01',
            'qty_delta' => -2,
            'unit_cost_rupiah' => 35000,
            'total_cost_rupiah' => -70000,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-package-edit-b',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => (string) $replacementLineIds['product-package-edit-b'],
            'tanggal_mutasi' => '2026-06-01',
            'qty_delta' => -2,
            'unit_cost_rupiah' => 20000,
            'total_cost_rupiah' => -40000,
        ]);

        self::assertSame(
            1,
            DB::table('inventory_movements')
                ->where('source_type', 'transaction_workspace_updated')
                ->where('source_id', 'ssl-edit-package-multi-a')
                ->where('movement_type', 'stock_in')
                ->count(),
        );

        self::assertSame(
            1,
            DB::table('inventory_movements')
                ->where('source_type', 'transaction_workspace_updated')
                ->where('source_id', 'ssl-edit-package-multi-b')
                ->where('movement_type', 'stock_in')
                ->count(),
        );

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-package-edit-a',
            'qty_on_hand' => 8,
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-package-edit-b',
            'qty_on_hand' => 8,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-package-edit-a',
            'avg_cost_rupiah' => 35000,
            'inventory_value_rupiah' => 280000,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-package-edit-b',
            'avg_cost_rupiah' => 20000,
            'inventory_value_rupiah' => 160000,
        ]);
    }

    public function test_package_auto_split_multi_product_revision_rebuilds_payment_allocations_and_records_underpaid_settlement(): void
    {
        $this->seedOpenMultiProductPackageNote();

        $this->seedCustomerPaymentBase(
            'payment-edit-package-multi-partial-001',
            200000,
            '2026-05-31',
        );

        $this->seedPaymentAllocationBase(
            'payment-allocation-edit-package-multi-partial-001',
            'payment-edit-package-multi-partial-001',
            'note-edit-package-multi-001',
            200000,
        );

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pca-edit-package-multi-old-a',
                'customer_payment_id' => 'payment-edit-package-multi-partial-001',
                'note_id' => 'note-edit-package-multi-001',
                'work_item_id' => 'wi-edit-package-multi-001',
                'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
                'component_ref_id' => 'ssl-edit-package-multi-a',
                'component_amount_rupiah_snapshot' => 100000,
                'allocated_amount_rupiah' => 100000,
                'allocation_priority' => 1,
            ],
            [
                'id' => 'pca-edit-package-multi-old-b',
                'customer_payment_id' => 'payment-edit-package-multi-partial-001',
                'note_id' => 'note-edit-package-multi-001',
                'work_item_id' => 'wi-edit-package-multi-001',
                'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
                'component_ref_id' => 'ssl-edit-package-multi-b',
                'component_amount_rupiah_snapshot' => 30000,
                'allocated_amount_rupiah' => 30000,
                'allocation_priority' => 2,
            ],
            [
                'id' => 'pca-edit-package-multi-old-service',
                'customer_payment_id' => 'payment-edit-package-multi-partial-001',
                'note_id' => 'note-edit-package-multi-001',
                'work_item_id' => 'wi-edit-package-multi-001',
                'component_type' => PaymentComponentType::SERVICE_FEE,
                'component_ref_id' => 'wi-edit-package-multi-001',
                'component_amount_rupiah_snapshot' => 120000,
                'allocated_amount_rupiah' => 70000,
                'allocation_priority' => 3,
            ],
        ]);

        $user = User::query()->create([
            'name' => 'Admin Package Revision Payment Characterization',
            'email' => 'admin-package-revision-payment-characterization@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        DB::table('admin_transaction_capability_states')->updateOrInsert(
            ['actor_id' => (string) $user->getAuthIdentifier()],
            ['active' => true],
        );

        $response = $this->actingAs($user)->patch(
            route('admin.notes.workspace.update', ['noteId' => 'note-edit-package-multi-001']),
            [
                'reason' => 'Package multi-product payment settlement characterization.',
                'note' => [
                    'customer_name' => 'Budi Edit Package Multi Payment Revised',
                    'customer_phone' => '08123456789',
                    'transaction_date' => '2026-06-01',
                    'operational_note' => 'Alasan revisi payment package multi.',
                ],
                'items' => [
                    [
                        'entry_mode' => 'service',
                        'description' => null,
                        'part_source' => 'store_stock',
                        'pricing_mode' => 'package_auto_split',
                        'package_total_rupiah' => 300000,
                        'service' => [
                            'name' => 'Servis Paket Multi Payment Revised',
                            'price_rupiah' => 0,
                            'notes' => null,
                        ],
                        'product_lines' => [
                            [
                                'product_id' => 'product-package-edit-a',
                                'qty' => 2,
                                'unit_price_rupiah' => 50000,
                                'price_basis' => 'revision_snapshot',
                            ],
                            [
                                'product_id' => 'product-package-edit-b',
                                'qty' => 2,
                                'unit_price_rupiah' => 30000,
                                'price_basis' => 'revision_snapshot',
                            ],
                        ],
                        'external_purchase_lines' => [],
                    ],
                ],
            ]
        );

        $response->assertRedirect(route('admin.notes.show', ['noteId' => 'note-edit-package-multi-001']));

        $workItemId = (string) DB::table('work_items')
            ->where('note_id', 'note-edit-package-multi-001')
            ->value('id');

        self::assertNotSame('', $workItemId);
        self::assertNotSame('wi-edit-package-multi-001', $workItemId);

        $replacementLineIds = DB::table('work_item_store_stock_lines')
            ->where('work_item_id', $workItemId)
            ->pluck('id', 'product_id');

        self::assertArrayHasKey('product-package-edit-a', $replacementLineIds->all());
        self::assertArrayHasKey('product-package-edit-b', $replacementLineIds->all());

        $this->assertDatabaseMissing('payment_component_allocations', [
            'note_id' => 'note-edit-package-multi-001',
            'work_item_id' => 'wi-edit-package-multi-001',
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => 'payment-edit-package-multi-partial-001',
            'note_id' => 'note-edit-package-multi-001',
            'work_item_id' => $workItemId,
            'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'component_ref_id' => (string) $replacementLineIds['product-package-edit-a'],
            'component_amount_rupiah_snapshot' => 100000,
            'allocated_amount_rupiah' => 100000,
            'allocation_priority' => 1,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => 'payment-edit-package-multi-partial-001',
            'note_id' => 'note-edit-package-multi-001',
            'work_item_id' => $workItemId,
            'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'component_ref_id' => (string) $replacementLineIds['product-package-edit-b'],
            'component_amount_rupiah_snapshot' => 60000,
            'allocated_amount_rupiah' => 60000,
            'allocation_priority' => 2,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => 'payment-edit-package-multi-partial-001',
            'note_id' => 'note-edit-package-multi-001',
            'work_item_id' => $workItemId,
            'component_type' => PaymentComponentType::SERVICE_FEE,
            'component_ref_id' => $workItemId,
            'component_amount_rupiah_snapshot' => 140000,
            'allocated_amount_rupiah' => 40000,
            'allocation_priority' => 3,
        ]);

        self::assertSame(
            200000,
            (int) DB::table('payment_component_allocations')
                ->where('note_id', 'note-edit-package-multi-001')
                ->where('customer_payment_id', 'payment-edit-package-multi-partial-001')
                ->sum('allocated_amount_rupiah'),
        );

        $this->assertDatabaseHas('customer_payments', [
            'id' => 'payment-edit-package-multi-partial-001',
            'amount_rupiah' => 200000,
        ]);

        $this->assertDatabaseHas('note_revision_settlements', [
            'id' => 'note-edit-package-multi-001-r002-settlement',
            'note_revision_id' => 'note-edit-package-multi-001-r002',
            'note_root_id' => 'note-edit-package-multi-001',
            'gross_total_rupiah' => 300000,
            'carry_forward_paid_rupiah' => 200000,
            'carry_forward_refunded_rupiah' => 0,
            'net_paid_rupiah' => 200000,
            'outstanding_rupiah' => 100000,
            'surplus_rupiah' => 0,
            'settlement_status' => 'underpaid',
        ]);
    }


    public function test_package_auto_split_multi_product_downward_revision_caps_replay_and_records_overpaid_settlement(): void
    {
        $this->seedOpenMultiProductPackageNote();

        $this->seedCustomerPaymentBase(
            'payment-edit-package-multi-overpaid-001',
            250000,
            '2026-05-31',
        );

        $this->seedPaymentAllocationBase(
            'payment-allocation-edit-package-multi-overpaid-001',
            'payment-edit-package-multi-overpaid-001',
            'note-edit-package-multi-001',
            250000,
        );

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pca-edit-package-multi-overpaid-old-a',
                'customer_payment_id' => 'payment-edit-package-multi-overpaid-001',
                'note_id' => 'note-edit-package-multi-001',
                'work_item_id' => 'wi-edit-package-multi-001',
                'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
                'component_ref_id' => 'ssl-edit-package-multi-a',
                'component_amount_rupiah_snapshot' => 100000,
                'allocated_amount_rupiah' => 100000,
                'allocation_priority' => 1,
            ],
            [
                'id' => 'pca-edit-package-multi-overpaid-old-b',
                'customer_payment_id' => 'payment-edit-package-multi-overpaid-001',
                'note_id' => 'note-edit-package-multi-001',
                'work_item_id' => 'wi-edit-package-multi-001',
                'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
                'component_ref_id' => 'ssl-edit-package-multi-b',
                'component_amount_rupiah_snapshot' => 30000,
                'allocated_amount_rupiah' => 30000,
                'allocation_priority' => 2,
            ],
            [
                'id' => 'pca-edit-package-multi-overpaid-old-service',
                'customer_payment_id' => 'payment-edit-package-multi-overpaid-001',
                'note_id' => 'note-edit-package-multi-001',
                'work_item_id' => 'wi-edit-package-multi-001',
                'component_type' => PaymentComponentType::SERVICE_FEE,
                'component_ref_id' => 'wi-edit-package-multi-001',
                'component_amount_rupiah_snapshot' => 120000,
                'allocated_amount_rupiah' => 120000,
                'allocation_priority' => 3,
            ],
        ]);

        $user = User::query()->create([
            'name' => 'Admin Package Revision Overpaid Characterization',
            'email' => 'admin-package-revision-overpaid-characterization@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        DB::table('admin_transaction_capability_states')->updateOrInsert(
            ['actor_id' => (string) $user->getAuthIdentifier()],
            ['active' => true],
        );

        $response = $this->actingAs($user)->patch(
            route('admin.notes.workspace.update', ['noteId' => 'note-edit-package-multi-001']),
            [
                'reason' => 'Package multi-product downward overpaid settlement characterization.',
                'note' => [
                    'customer_name' => 'Budi Edit Package Multi Overpaid Revised',
                    'customer_phone' => '08123456789',
                    'transaction_date' => '2026-06-01',
                    'operational_note' => 'Alasan revisi overpaid package multi.',
                ],
                'items' => [
                    [
                        'entry_mode' => 'service',
                        'description' => null,
                        'part_source' => 'store_stock',
                        'pricing_mode' => 'package_auto_split',
                        'package_total_rupiah' => 200000,
                        'service' => [
                            'name' => 'Servis Paket Multi Overpaid Revised',
                            'price_rupiah' => 0,
                            'notes' => null,
                        ],
                        'product_lines' => [
                            [
                                'product_id' => 'product-package-edit-a',
                                'qty' => 2,
                                'unit_price_rupiah' => 50000,
                                'price_basis' => 'revision_snapshot',
                            ],
                            [
                                'product_id' => 'product-package-edit-b',
                                'qty' => 1,
                                'unit_price_rupiah' => 30000,
                                'price_basis' => 'revision_snapshot',
                            ],
                        ],
                        'external_purchase_lines' => [],
                    ],
                ],
            ]
        );

        $response->assertRedirect(route('admin.notes.show', ['noteId' => 'note-edit-package-multi-001']));

        $workItemId = (string) DB::table('work_items')
            ->where('note_id', 'note-edit-package-multi-001')
            ->value('id');

        self::assertNotSame('', $workItemId);
        self::assertNotSame('wi-edit-package-multi-001', $workItemId);

        $replacementLineIds = DB::table('work_item_store_stock_lines')
            ->where('work_item_id', $workItemId)
            ->pluck('id', 'product_id');

        self::assertArrayHasKey('product-package-edit-a', $replacementLineIds->all());
        self::assertArrayHasKey('product-package-edit-b', $replacementLineIds->all());

        $this->assertDatabaseMissing('payment_component_allocations', [
            'note_id' => 'note-edit-package-multi-001',
            'work_item_id' => 'wi-edit-package-multi-001',
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => 'payment-edit-package-multi-overpaid-001',
            'note_id' => 'note-edit-package-multi-001',
            'work_item_id' => $workItemId,
            'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'component_ref_id' => (string) $replacementLineIds['product-package-edit-a'],
            'component_amount_rupiah_snapshot' => 100000,
            'allocated_amount_rupiah' => 100000,
            'allocation_priority' => 1,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => 'payment-edit-package-multi-overpaid-001',
            'note_id' => 'note-edit-package-multi-001',
            'work_item_id' => $workItemId,
            'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'component_ref_id' => (string) $replacementLineIds['product-package-edit-b'],
            'component_amount_rupiah_snapshot' => 30000,
            'allocated_amount_rupiah' => 30000,
            'allocation_priority' => 2,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => 'payment-edit-package-multi-overpaid-001',
            'note_id' => 'note-edit-package-multi-001',
            'work_item_id' => $workItemId,
            'component_type' => PaymentComponentType::SERVICE_FEE,
            'component_ref_id' => $workItemId,
            'component_amount_rupiah_snapshot' => 70000,
            'allocated_amount_rupiah' => 70000,
            'allocation_priority' => 3,
        ]);

        self::assertSame(
            200000,
            (int) DB::table('payment_component_allocations')
                ->where('note_id', 'note-edit-package-multi-001')
                ->where('customer_payment_id', 'payment-edit-package-multi-overpaid-001')
                ->sum('allocated_amount_rupiah'),
        );

        $this->assertDatabaseHas('customer_payments', [
            'id' => 'payment-edit-package-multi-overpaid-001',
            'amount_rupiah' => 250000,
        ]);

        $this->assertDatabaseHas('note_revision_settlements', [
            'id' => 'note-edit-package-multi-001-r002-settlement',
            'note_revision_id' => 'note-edit-package-multi-001-r002',
            'note_root_id' => 'note-edit-package-multi-001',
            'gross_total_rupiah' => 200000,
            'carry_forward_paid_rupiah' => 250000,
            'carry_forward_refunded_rupiah' => 0,
            'net_paid_rupiah' => 250000,
            'outstanding_rupiah' => 0,
            'surplus_rupiah' => 50000,
            'settlement_status' => 'overpaid_pending',
        ]);
    }


    public function test_package_auto_split_multi_product_refund_after_downward_revision_targets_current_replacement_components_only(): void
    {
        $this->seedOpenMultiProductPackageNote();

        $this->seedCustomerPaymentBase(
            'payment-edit-package-multi-refund-001',
            250000,
            '2026-05-31',
        );

        $this->seedPaymentAllocationBase(
            'payment-allocation-edit-package-multi-refund-001',
            'payment-edit-package-multi-refund-001',
            'note-edit-package-multi-001',
            250000,
        );

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pca-edit-package-multi-refund-old-a',
                'customer_payment_id' => 'payment-edit-package-multi-refund-001',
                'note_id' => 'note-edit-package-multi-001',
                'work_item_id' => 'wi-edit-package-multi-001',
                'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
                'component_ref_id' => 'ssl-edit-package-multi-a',
                'component_amount_rupiah_snapshot' => 100000,
                'allocated_amount_rupiah' => 100000,
                'allocation_priority' => 1,
            ],
            [
                'id' => 'pca-edit-package-multi-refund-old-b',
                'customer_payment_id' => 'payment-edit-package-multi-refund-001',
                'note_id' => 'note-edit-package-multi-001',
                'work_item_id' => 'wi-edit-package-multi-001',
                'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
                'component_ref_id' => 'ssl-edit-package-multi-b',
                'component_amount_rupiah_snapshot' => 30000,
                'allocated_amount_rupiah' => 30000,
                'allocation_priority' => 2,
            ],
            [
                'id' => 'pca-edit-package-multi-refund-old-service',
                'customer_payment_id' => 'payment-edit-package-multi-refund-001',
                'note_id' => 'note-edit-package-multi-001',
                'work_item_id' => 'wi-edit-package-multi-001',
                'component_type' => PaymentComponentType::SERVICE_FEE,
                'component_ref_id' => 'wi-edit-package-multi-001',
                'component_amount_rupiah_snapshot' => 120000,
                'allocated_amount_rupiah' => 120000,
                'allocation_priority' => 3,
            ],
        ]);

        $user = User::query()->create([
            'name' => 'Admin Package Revision Refund Characterization',
            'email' => 'admin-package-revision-refund-characterization@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        DB::table('admin_transaction_capability_states')->updateOrInsert(
            ['actor_id' => (string) $user->getAuthIdentifier()],
            ['active' => true],
        );

        $response = $this->actingAs($user)->patch(
            route('admin.notes.workspace.update', ['noteId' => 'note-edit-package-multi-001']),
            [
                'reason' => 'Package multi-product refund boundary characterization.',
                'note' => [
                    'customer_name' => 'Budi Edit Package Multi Refund Revised',
                    'customer_phone' => '08123456789',
                    'transaction_date' => '2026-06-01',
                    'operational_note' => 'Alasan revisi refund package multi.',
                ],
                'items' => [
                    [
                        'entry_mode' => 'service',
                        'description' => null,
                        'part_source' => 'store_stock',
                        'pricing_mode' => 'package_auto_split',
                        'package_total_rupiah' => 200000,
                        'service' => [
                            'name' => 'Servis Paket Multi Refund Revised',
                            'price_rupiah' => 0,
                            'notes' => null,
                        ],
                        'product_lines' => [
                            [
                                'product_id' => 'product-package-edit-a',
                                'qty' => 2,
                                'unit_price_rupiah' => 50000,
                                'price_basis' => 'revision_snapshot',
                            ],
                            [
                                'product_id' => 'product-package-edit-b',
                                'qty' => 1,
                                'unit_price_rupiah' => 30000,
                                'price_basis' => 'revision_snapshot',
                            ],
                        ],
                        'external_purchase_lines' => [],
                    ],
                ],
            ]
        );

        $response->assertRedirect(route('admin.notes.show', ['noteId' => 'note-edit-package-multi-001']));

        $workItemId = (string) DB::table('work_items')
            ->where('note_id', 'note-edit-package-multi-001')
            ->value('id');

        self::assertNotSame('', $workItemId);
        self::assertNotSame('wi-edit-package-multi-001', $workItemId);

        $replacementLineIds = DB::table('work_item_store_stock_lines')
            ->where('work_item_id', $workItemId)
            ->pluck('id', 'product_id');

        self::assertArrayHasKey('product-package-edit-a', $replacementLineIds->all());
        self::assertArrayHasKey('product-package-edit-b', $replacementLineIds->all());

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => 'payment-edit-package-multi-refund-001',
            'note_id' => 'note-edit-package-multi-001',
            'work_item_id' => $workItemId,
            'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'component_ref_id' => (string) $replacementLineIds['product-package-edit-a'],
            'allocated_amount_rupiah' => 100000,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => 'payment-edit-package-multi-refund-001',
            'note_id' => 'note-edit-package-multi-001',
            'work_item_id' => $workItemId,
            'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'component_ref_id' => (string) $replacementLineIds['product-package-edit-b'],
            'allocated_amount_rupiah' => 30000,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => 'payment-edit-package-multi-refund-001',
            'note_id' => 'note-edit-package-multi-001',
            'work_item_id' => $workItemId,
            'component_type' => PaymentComponentType::SERVICE_FEE,
            'component_ref_id' => $workItemId,
            'allocated_amount_rupiah' => 70000,
        ]);

        self::assertSame(
            200000,
            (int) DB::table('payment_component_allocations')
                ->where('note_id', 'note-edit-package-multi-001')
                ->where('customer_payment_id', 'payment-edit-package-multi-refund-001')
                ->sum('allocated_amount_rupiah'),
        );

        $this->actingAs($user)
            ->from(route('admin.notes.show', ['noteId' => 'note-edit-package-multi-001']))
            ->post(route('admin.notes.refunds.store', ['noteId' => 'note-edit-package-multi-001']), [
                'selected_row_ids' => ['wi-edit-package-multi-001'],
                'refunded_at' => '2026-06-02',
                'reason' => 'Attempt stale package multi row refund.',
            ])
            ->assertRedirect(route('admin.notes.show', ['noteId' => 'note-edit-package-multi-001']))
            ->assertSessionHasErrors(['refund']);

        $this->assertDatabaseCount('customer_refunds', 0);
        $this->assertDatabaseCount('refund_component_allocations', 0);

        $this->actingAs($user)
            ->from(route('admin.notes.show', ['noteId' => 'note-edit-package-multi-001']))
            ->post(route('admin.notes.refunds.store', ['noteId' => 'note-edit-package-multi-001']), [
                'selected_row_ids' => [$workItemId],
                'refunded_at' => '2026-06-02',
                'reason' => 'Refund current replacement package multi product components.',
            ])
            ->assertRedirect(route('admin.notes.index'))
            ->assertSessionHas('success');

        $refundId = (string) DB::table('customer_refunds')
            ->where('note_id', 'note-edit-package-multi-001')
            ->value('id');

        self::assertNotSame('', $refundId);

        $this->assertDatabaseHas('customer_refunds', [
            'id' => $refundId,
            'customer_payment_id' => 'payment-edit-package-multi-refund-001',
            'note_id' => 'note-edit-package-multi-001',
            'amount_rupiah' => 130000,
            'reason' => 'Refund current replacement package multi product components.',
        ]);

        $this->assertDatabaseHas('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'customer_payment_id' => 'payment-edit-package-multi-refund-001',
            'note_id' => 'note-edit-package-multi-001',
            'work_item_id' => $workItemId,
            'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'component_ref_id' => (string) $replacementLineIds['product-package-edit-a'],
            'refunded_amount_rupiah' => 100000,
        ]);

        $this->assertDatabaseHas('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'customer_payment_id' => 'payment-edit-package-multi-refund-001',
            'note_id' => 'note-edit-package-multi-001',
            'work_item_id' => $workItemId,
            'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'component_ref_id' => (string) $replacementLineIds['product-package-edit-b'],
            'refunded_amount_rupiah' => 30000,
        ]);

        $this->assertDatabaseMissing('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'customer_payment_id' => 'payment-edit-package-multi-refund-001',
            'note_id' => 'note-edit-package-multi-001',
            'work_item_id' => $workItemId,
            'component_type' => PaymentComponentType::SERVICE_FEE,
            'component_ref_id' => $workItemId,
        ]);

        self::assertSame(
            130000,
            (int) DB::table('refund_component_allocations')
                ->where('customer_refund_id', $refundId)
                ->sum('refunded_amount_rupiah'),
        );

        $this->assertDatabaseMissing('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'work_item_id' => 'wi-edit-package-multi-001',
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => $workItemId,
            'note_id' => 'note-edit-package-multi-001',
            'status' => WorkItem::STATUS_OPEN,
        ]);
    }


    public function test_package_auto_split_multi_product_exact_paid_revision_records_paid_settlement(): void
    {
        $this->seedOpenMultiProductPackageNote();

        $this->seedCustomerPaymentBase(
            'payment-edit-package-multi-exact-001',
            250000,
            '2026-05-31',
        );

        $this->seedPaymentAllocationBase(
            'payment-allocation-edit-package-multi-exact-001',
            'payment-edit-package-multi-exact-001',
            'note-edit-package-multi-001',
            250000,
        );

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pca-edit-package-multi-exact-old-a',
                'customer_payment_id' => 'payment-edit-package-multi-exact-001',
                'note_id' => 'note-edit-package-multi-001',
                'work_item_id' => 'wi-edit-package-multi-001',
                'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
                'component_ref_id' => 'ssl-edit-package-multi-a',
                'component_amount_rupiah_snapshot' => 100000,
                'allocated_amount_rupiah' => 100000,
                'allocation_priority' => 1,
            ],
            [
                'id' => 'pca-edit-package-multi-exact-old-b',
                'customer_payment_id' => 'payment-edit-package-multi-exact-001',
                'note_id' => 'note-edit-package-multi-001',
                'work_item_id' => 'wi-edit-package-multi-001',
                'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
                'component_ref_id' => 'ssl-edit-package-multi-b',
                'component_amount_rupiah_snapshot' => 30000,
                'allocated_amount_rupiah' => 30000,
                'allocation_priority' => 2,
            ],
            [
                'id' => 'pca-edit-package-multi-exact-old-service',
                'customer_payment_id' => 'payment-edit-package-multi-exact-001',
                'note_id' => 'note-edit-package-multi-001',
                'work_item_id' => 'wi-edit-package-multi-001',
                'component_type' => PaymentComponentType::SERVICE_FEE,
                'component_ref_id' => 'wi-edit-package-multi-001',
                'component_amount_rupiah_snapshot' => 120000,
                'allocated_amount_rupiah' => 120000,
                'allocation_priority' => 3,
            ],
        ]);

        $user = User::query()->create([
            'name' => 'Admin Package Revision Exact Paid Characterization',
            'email' => 'admin-package-revision-exact-paid-characterization@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        DB::table('admin_transaction_capability_states')->updateOrInsert(
            ['actor_id' => (string) $user->getAuthIdentifier()],
            ['active' => true],
        );

        $response = $this->actingAs($user)->patch(
            route('admin.notes.workspace.update', ['noteId' => 'note-edit-package-multi-001']),
            [
                'reason' => 'Package multi-product exact-paid settlement characterization.',
                'note' => [
                    'customer_name' => 'Budi Edit Package Multi Exact Paid Revised',
                    'customer_phone' => '08123456789',
                    'transaction_date' => '2026-06-01',
                    'operational_note' => 'Alasan revisi exact paid package multi.',
                ],
                'items' => [
                    [
                        'entry_mode' => 'service',
                        'description' => null,
                        'part_source' => 'store_stock',
                        'pricing_mode' => 'package_auto_split',
                        'package_total_rupiah' => 250000,
                        'service' => [
                            'name' => 'Servis Paket Multi Exact Paid Revised',
                            'price_rupiah' => 0,
                            'notes' => null,
                        ],
                        'product_lines' => [
                            [
                                'product_id' => 'product-package-edit-a',
                                'qty' => 2,
                                'unit_price_rupiah' => 50000,
                                'price_basis' => 'revision_snapshot',
                            ],
                            [
                                'product_id' => 'product-package-edit-b',
                                'qty' => 2,
                                'unit_price_rupiah' => 30000,
                                'price_basis' => 'revision_snapshot',
                            ],
                        ],
                        'external_purchase_lines' => [],
                    ],
                ],
            ]
        );

        $response->assertRedirect(route('admin.notes.show', ['noteId' => 'note-edit-package-multi-001']));

        $workItemId = (string) DB::table('work_items')
            ->where('note_id', 'note-edit-package-multi-001')
            ->value('id');

        self::assertNotSame('', $workItemId);
        self::assertNotSame('wi-edit-package-multi-001', $workItemId);

        $replacementLineIds = DB::table('work_item_store_stock_lines')
            ->where('work_item_id', $workItemId)
            ->pluck('id', 'product_id');

        self::assertArrayHasKey('product-package-edit-a', $replacementLineIds->all());
        self::assertArrayHasKey('product-package-edit-b', $replacementLineIds->all());

        $this->assertDatabaseMissing('payment_component_allocations', [
            'note_id' => 'note-edit-package-multi-001',
            'work_item_id' => 'wi-edit-package-multi-001',
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => 'payment-edit-package-multi-exact-001',
            'note_id' => 'note-edit-package-multi-001',
            'work_item_id' => $workItemId,
            'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'component_ref_id' => (string) $replacementLineIds['product-package-edit-a'],
            'component_amount_rupiah_snapshot' => 100000,
            'allocated_amount_rupiah' => 100000,
            'allocation_priority' => 1,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => 'payment-edit-package-multi-exact-001',
            'note_id' => 'note-edit-package-multi-001',
            'work_item_id' => $workItemId,
            'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'component_ref_id' => (string) $replacementLineIds['product-package-edit-b'],
            'component_amount_rupiah_snapshot' => 60000,
            'allocated_amount_rupiah' => 60000,
            'allocation_priority' => 2,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => 'payment-edit-package-multi-exact-001',
            'note_id' => 'note-edit-package-multi-001',
            'work_item_id' => $workItemId,
            'component_type' => PaymentComponentType::SERVICE_FEE,
            'component_ref_id' => $workItemId,
            'component_amount_rupiah_snapshot' => 90000,
            'allocated_amount_rupiah' => 90000,
            'allocation_priority' => 3,
        ]);

        self::assertSame(
            250000,
            (int) DB::table('payment_component_allocations')
                ->where('note_id', 'note-edit-package-multi-001')
                ->where('customer_payment_id', 'payment-edit-package-multi-exact-001')
                ->sum('allocated_amount_rupiah'),
        );

        $this->assertDatabaseHas('customer_payments', [
            'id' => 'payment-edit-package-multi-exact-001',
            'amount_rupiah' => 250000,
        ]);

        $this->assertDatabaseHas('note_revision_settlements', [
            'id' => 'note-edit-package-multi-001-r002-settlement',
            'note_revision_id' => 'note-edit-package-multi-001-r002',
            'note_root_id' => 'note-edit-package-multi-001',
            'gross_total_rupiah' => 250000,
            'carry_forward_paid_rupiah' => 250000,
            'carry_forward_refunded_rupiah' => 0,
            'net_paid_rupiah' => 250000,
            'outstanding_rupiah' => 0,
            'surplus_rupiah' => 0,
            'settlement_status' => 'paid',
        ]);
    }


    private function seedOpenMultiProductPackageNote(): void
    {
        $this->seedNoteBase(
            'note-edit-package-multi-001',
            'Budi Edit Package Multi',
            '2026-05-31',
            250000,
            'open',
        );

        DB::table('notes')
            ->where('id', 'note-edit-package-multi-001')
            ->update([
                'operational_note' => 'Alasan awal package multi.',
            ]);

        $this->seedNotePaymentProduct(
            'product-package-edit-a',
            'PKG-EDIT-A',
            'Oli Edit Package A',
            'Federal',
            100,
            50000,
        );

        $this->seedNotePaymentProduct(
            'product-package-edit-b',
            'PKG-EDIT-B',
            'Busi Edit Package B',
            'NGK',
            100,
            30000,
        );

        DB::table('product_inventory')->insert([
            [
                'product_id' => 'product-package-edit-a',
                'qty_on_hand' => 10,
            ],
            [
                'product_id' => 'product-package-edit-b',
                'qty_on_hand' => 10,
            ],
        ]);

        DB::table('product_inventory_costing')->insert([
            [
                'product_id' => 'product-package-edit-a',
                'avg_cost_rupiah' => 35000,
                'inventory_value_rupiah' => 350000,
            ],
            [
                'product_id' => 'product-package-edit-b',
                'avg_cost_rupiah' => 20000,
                'inventory_value_rupiah' => 200000,
            ],
        ]);

        $this->seedWorkItemBase(
            'wi-edit-package-multi-001',
            'note-edit-package-multi-001',
            1,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            WorkItem::STATUS_OPEN,
            250000,
        );

        $this->seedServiceDetailBase(
            'wi-edit-package-multi-001',
            'Servis Paket Multi Original',
            120000,
            'none',
        );

        $this->seedStoreStockLineBase(
            'ssl-edit-package-multi-a',
            'wi-edit-package-multi-001',
            'product-package-edit-a',
            2,
            100000,
        );

        $this->seedStoreStockLineBase(
            'ssl-edit-package-multi-b',
            'wi-edit-package-multi-001',
            'product-package-edit-b',
            1,
            30000,
        );

        $this->seedServiceWithStoreStockCurrentRevision(
            'note-edit-package-multi-001',
            'note-edit-package-multi-001-r001',
            'wi-edit-package-multi-001',
            'Budi Edit Package Multi',
            '2026-05-31',
            250000,
            'Servis Paket Multi Original',
            120000,
            'ssl-edit-package-multi-a',
            'product-package-edit-a',
            2,
            100000,
        );

        $payload = DB::table('note_revision_lines')
            ->where('note_revision_id', 'note-edit-package-multi-001-r001')
            ->where('work_item_root_id', 'wi-edit-package-multi-001')
            ->value('payload');

        $decoded = json_decode((string) $payload, true, 512, JSON_THROW_ON_ERROR);

        $decoded['pricing_mode'] = 'package_auto_split';
        $decoded['package_total_rupiah'] = 250000;
        $decoded['parts_total_rupiah'] = 130000;
        $decoded['service_price_rupiah'] = 120000;

        $decoded['store_stock_lines'] = [
            [
                'id' => 'ssl-edit-package-multi-a',
                'work_item_id' => 'wi-edit-package-multi-001',
                'product_id' => 'product-package-edit-a',
                'qty' => 2,
                'line_total_rupiah' => 100000,
                'selling_price_rupiah' => 50000,
                'product_name_snapshot' => 'Oli Edit Package A',
            ],
            [
                'id' => 'ssl-edit-package-multi-b',
                'work_item_id' => 'wi-edit-package-multi-001',
                'product_id' => 'product-package-edit-b',
                'qty' => 1,
                'line_total_rupiah' => 30000,
                'selling_price_rupiah' => 30000,
                'product_name_snapshot' => 'Busi Edit Package B',
            ],
        ];

        DB::table('note_revision_lines')
            ->where('note_revision_id', 'note-edit-package-multi-001-r001')
            ->where('work_item_root_id', 'wi-edit-package-multi-001')
            ->update([
                'payload' => json_encode($decoded, JSON_THROW_ON_ERROR),
            ]);
    }
}
