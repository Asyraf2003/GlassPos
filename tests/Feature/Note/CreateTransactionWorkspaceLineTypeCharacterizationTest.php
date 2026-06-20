<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class CreateTransactionWorkspaceLineTypeCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_behavior_create_service_only_subtotal_is_service_price(): void
    {
        $user = $this->loginAsKasir();

        $response = $this->postWorkspace($user, 'phase1-create-service-only', [[
            'entry_mode' => 'service',
            'part_source' => 'none',
            'pay_now' => 0,
            'service' => [
                'name' => 'Servis CVT',
                'price_rupiah' => 85000,
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
            ]],
        ]], 'Phase 1 Service Only');

        $response->assertRedirect(route('cashier.notes.index'));

        $noteId = (string) DB::table('notes')->where('customer_name', 'Phase 1 Service Only')->value('id');
        $workItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'total_rupiah' => 85000,
        ]);
        $this->assertDatabaseHas('work_items', [
            'id' => $workItemId,
            'transaction_type' => 'service_only',
            'subtotal_rupiah' => 85000,
        ]);
        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => $workItemId,
            'service_name' => 'Servis CVT',
            'service_price_rupiah' => 85000,
        ]);
        $this->assertDatabaseCount('work_item_store_stock_lines', 0);
        $this->assertDatabaseCount('work_item_external_purchase_lines', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_current_behavior_create_service_with_external_purchase_subtotal_is_service_plus_external_cost(): void
    {
        $user = $this->loginAsKasir();

        $response = $this->postWorkspace($user, 'phase1-create-service-external', [[
            'entry_mode' => 'service',
            'part_source' => 'external_purchase',
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
                'qty' => 2,
                'unit_cost_rupiah' => 45000,
            ]],
        ]], 'Phase 1 External Purchase');

        $response->assertRedirect(route('cashier.notes.index'));

        $noteId = (string) DB::table('notes')->where('customer_name', 'Phase 1 External Purchase')->value('id');
        $workItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'total_rupiah' => 170000,
        ]);
        $this->assertDatabaseHas('work_items', [
            'id' => $workItemId,
            'transaction_type' => 'service_with_external_purchase',
            'subtotal_rupiah' => 170000,
        ]);
        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => $workItemId,
            'service_price_rupiah' => 80000,
        ]);
        $this->assertDatabaseHas('work_item_external_purchase_lines', [
            'work_item_id' => $workItemId,
            'cost_description' => 'Bearing NTN',
            'qty' => 2,
            'unit_cost_rupiah' => 45000,
            'line_total_rupiah' => 90000,
        ]);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_current_behavior_create_store_stock_sale_only_subtotal_is_product_total_and_issues_stock(): void
    {
        $user = $this->loginAsKasir();
        $this->seedProduct('phase1-product-only', 70000, 30000);

        $response = $this->postWorkspace($user, 'phase1-create-product-only', [[
            'entry_mode' => 'product',
            'part_source' => 'none',
            'pay_now' => 0,
            'product_lines' => [[
                'product_id' => 'phase1-product-only',
                'qty' => 3,
                'unit_price_rupiah' => 70000,
            ]],
            'external_purchase_lines' => [[
                'label' => '',
                'qty' => '',
                'unit_cost_rupiah' => '',
            ]],
        ]], 'Phase 1 Product Only');

        $response->assertRedirect(route('cashier.notes.index'));

        $noteId = (string) DB::table('notes')->where('customer_name', 'Phase 1 Product Only')->value('id');
        $workItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');
        $lineId = (string) DB::table('work_item_store_stock_lines')->where('work_item_id', $workItemId)->value('id');

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'total_rupiah' => 210000,
        ]);
        $this->assertDatabaseHas('work_items', [
            'id' => $workItemId,
            'transaction_type' => 'store_stock_sale_only',
            'subtotal_rupiah' => 210000,
        ]);
        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'id' => $lineId,
            'product_id' => 'phase1-product-only',
            'qty' => 3,
            'line_total_rupiah' => 210000,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'phase1-product-only',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => $lineId,
            'qty_delta' => -3,
            'unit_cost_rupiah' => 30000,
            'total_cost_rupiah' => -90000,
        ]);
    }

    public function test_current_behavior_create_service_store_stock_package_single_product_subtotal_is_package_total(): void
    {
        $user = $this->loginAsKasir();
        $this->seedProduct('phase1-package-single-product', 40000, 25000);

        $response = $this->postWorkspace($user, 'phase1-create-package-single', [[
            'entry_mode' => 'service',
            'part_source' => 'store_stock',
            'pricing_mode' => 'package_auto_split',
            'package_total_rupiah' => 150000,
            'pay_now' => 0,
            'service' => [
                'name' => 'Paket Rem Single',
                'price_rupiah' => 0,
                'notes' => '',
            ],
            'product_lines' => [[
                'product_id' => 'phase1-package-single-product',
                'qty' => 1,
                'unit_price_rupiah' => 40000,
            ]],
            'external_purchase_lines' => [[
                'label' => '',
                'qty' => '',
                'unit_cost_rupiah' => '',
            ]],
        ]], 'Phase 1 Package Single');

        $response->assertRedirect(route('cashier.notes.index'));

        $noteId = (string) DB::table('notes')->where('customer_name', 'Phase 1 Package Single')->value('id');
        $workItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'total_rupiah' => 150000,
        ]);
        $this->assertDatabaseHas('work_items', [
            'id' => $workItemId,
            'transaction_type' => 'service_with_store_stock_part',
            'subtotal_rupiah' => 150000,
        ]);
        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => $workItemId,
            'service_name' => 'Paket Rem Single',
            'service_price_rupiah' => 110000,
        ]);
        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'work_item_id' => $workItemId,
            'product_id' => 'phase1-package-single-product',
            'qty' => 1,
            'line_total_rupiah' => 40000,
        ]);
    }

    public function test_current_behavior_backend_direct_post_package_multi_product_non_template_is_supported(): void
    {
        $user = $this->loginAsKasir();
        $this->seedProduct('phase1-package-multi-a', 50000, 35000);
        $this->seedProduct('phase1-package-multi-b', 30000, 20000);

        $response = $this->postWorkspace($user, 'phase1-create-package-multi', [[
            'entry_mode' => 'service',
            'part_source' => 'store_stock',
            'pricing_mode' => 'package_auto_split',
            'package_total_rupiah' => 250000,
            'pay_now' => 0,
            'service' => [
                'name' => 'Paket Multi Current Backend',
                'price_rupiah' => 0,
                'notes' => '',
            ],
            'product_lines' => [
                [
                    'product_id' => 'phase1-package-multi-a',
                    'qty' => 2,
                    'unit_price_rupiah' => 50000,
                ],
                [
                    'product_id' => 'phase1-package-multi-b',
                    'qty' => 1,
                    'unit_price_rupiah' => 30000,
                ],
            ],
            'external_purchase_lines' => [[
                'label' => '',
                'qty' => '',
                'unit_cost_rupiah' => '',
            ]],
        ]], 'Phase 1 Package Multi Backend');

        $response->assertRedirect(route('cashier.notes.index'));

        $noteId = (string) DB::table('notes')->where('customer_name', 'Phase 1 Package Multi Backend')->value('id');
        $workItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'total_rupiah' => 250000,
        ]);
        $this->assertDatabaseHas('work_items', [
            'id' => $workItemId,
            'transaction_type' => 'service_with_store_stock_part',
            'subtotal_rupiah' => 250000,
        ]);
        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => $workItemId,
            'service_price_rupiah' => 120000,
        ]);
        $this->assertDatabaseCount('work_item_store_stock_lines', 2);
        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'work_item_id' => $workItemId,
            'product_id' => 'phase1-package-multi-a',
            'qty' => 2,
            'line_total_rupiah' => 100000,
        ]);
        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'work_item_id' => $workItemId,
            'product_id' => 'phase1-package-multi-b',
            'qty' => 1,
            'line_total_rupiah' => 30000,
        ]);
        $this->assertDatabaseCount('inventory_movements', 2);
    }

    public function test_current_ui_source_contract_service_store_stock_blocks_and_preloads_one_product_line_despite_owner_target_multi_part_package(): void
    {
        $blade = file_get_contents(resource_path('views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php'));
        $rowsJs = file_get_contents(public_path('assets/static/js/pages/cashier-note-workspace/rows.js'));

        self::assertIsString($blade);
        self::assertIsString($rowsJs);

        $this->assertStringContainsString('name="items[__INDEX__][pricing_mode]" value="package_auto_split"', $blade);
        $this->assertStringContainsString('name="items[__INDEX__][requires_service_product_template]" value="1"', $blade);
        $this->assertStringContainsString('data-product-line-template', $blade);

        $this->assertStringContainsString('productLineScopes(row).length >= 1', $rowsJs);
        $this->assertStringContainsString('.slice(0, 1)', $rowsJs);
    }

    public function test_phase2_template_preset_multi_product_extension_remains_blocked_until_phase4_contract(): void
    {
        $user = $this->loginAsKasir();
        $this->seedProduct('phase2-template-guard-a', 40000, 25000);
        $this->seedProduct('phase2-template-guard-b', 30000, 20000);

        $response = $this->actingAs($user)
            ->from(route('cashier.notes.workspace.create'))
            ->post(route('notes.workspace.store'), [
                'idempotency_key' => 'phase2-template-preset-multi-blocked',
                'note' => [
                    'customer_name' => 'Phase 2 Template Preset Multi Blocked',
                    'customer_phone' => '08123',
                    'transaction_date' => '2026-06-21',
                ],
                'items' => [[
                    'entry_mode' => 'service',
                    'part_source' => 'store_stock',
                    'pricing_mode' => 'package_auto_split',
                    'requires_service_product_template' => '1',
                    'package_total_rupiah' => 150000,
                    'pay_now' => 0,
                    'service' => [
                        'name' => 'Template Preset Multi Phase 2 Guard',
                        'price_rupiah' => 0,
                        'notes' => '',
                    ],
                    'product_lines' => [
                        [
                            'product_id' => 'phase2-template-guard-a',
                            'qty' => 1,
                            'unit_price_rupiah' => 40000,
                        ],
                        [
                            'product_id' => 'phase2-template-guard-b',
                            'qty' => 1,
                            'unit_price_rupiah' => 30000,
                        ],
                    ],
                    'external_purchase_lines' => [[
                        'label' => '',
                        'qty' => '',
                        'unit_cost_rupiah' => '',
                    ]],
                ]],
                'inline_payment' => [
                    'decision' => 'skip',
                    'payment_method' => null,
                    'paid_at' => '2026-06-21',
                ],
            ]);

        $response->assertRedirect(route('cashier.notes.workspace.create'));
        $response->assertSessionHasErrors([
            'workspace' => 'Paket servis + produk hanya boleh memakai 1 produk template aktif.',
        ]);

        $this->assertDatabaseMissing('notes', [
            'customer_name' => 'Phase 2 Template Preset Multi Blocked',
        ]);
        $this->assertDatabaseMissing('work_items', [
            'transaction_type' => 'service_with_store_stock_part',
            'subtotal_rupiah' => 150000,
        ]);
    }

    public function test_current_behavior_template_branch_rejects_multi_product_package_preset_extension(): void
    {
        $user = $this->loginAsKasir();
        $this->seedProduct('phase1-template-multi-a', 40000, 25000);
        $this->seedProduct('phase1-template-multi-b', 30000, 20000);

        $response = $this->actingAs($user)
            ->from(route('cashier.notes.workspace.create'))
            ->post(route('notes.workspace.store'), [
                'idempotency_key' => 'phase1-template-multi-rejected',
                'note' => [
                    'customer_name' => 'Phase 1 Template Multi Rejected',
                    'customer_phone' => '08123',
                    'transaction_date' => '2026-03-15',
                ],
                'items' => [[
                    'entry_mode' => 'service',
                    'part_source' => 'store_stock',
                    'pricing_mode' => 'package_auto_split',
                    'requires_service_product_template' => '1',
                    'package_total_rupiah' => 150000,
                    'pay_now' => 0,
                    'service' => [
                        'name' => 'Template Preset Multi Gap',
                        'price_rupiah' => 0,
                        'notes' => '',
                    ],
                    'product_lines' => [
                        [
                            'product_id' => 'phase1-template-multi-a',
                            'qty' => 1,
                            'unit_price_rupiah' => 40000,
                        ],
                        [
                            'product_id' => 'phase1-template-multi-b',
                            'qty' => 1,
                            'unit_price_rupiah' => 30000,
                        ],
                    ],
                    'external_purchase_lines' => [[
                        'label' => '',
                        'qty' => '',
                        'unit_cost_rupiah' => '',
                    ]],
                ]],
                'inline_payment' => [
                    'decision' => 'skip',
                    'payment_method' => null,
                    'paid_at' => '2026-03-15',
                ],
            ]);

        $response->assertRedirect(route('cashier.notes.workspace.create'));
        $response->assertSessionHasErrors([
            'workspace' => 'Paket servis + produk hanya boleh memakai 1 produk template aktif.',
        ]);

        $this->assertDatabaseCount('notes', 0);
        $this->assertDatabaseCount('work_items', 0);
        $this->assertDatabaseCount('work_item_store_stock_lines', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_current_external_purchase_ui_backend_gap_label_total_target_vs_package_backend_path(): void
    {
        $blade = file_get_contents(resource_path('views/cashier/notes/workspace/partials/templates/service-external.blade.php'));

        self::assertIsString($blade);

        $this->assertStringContainsString('external_purchase_lines][0][label]', $blade);
        $this->assertStringContainsString('external_purchase_lines][0][qty]', $blade);
        $this->assertStringContainsString('external_purchase_lines][0][unit_cost_rupiah]', $blade);
        $this->assertStringNotContainsString('external_purchase_lines][0][total_rupiah]', $blade);
        $this->assertStringNotContainsString('package_total_rupiah', $blade);

        $user = $this->loginAsKasir();

        $validationGap = $this->actingAs($user)
            ->from(route('cashier.notes.workspace.create'))
            ->post(route('notes.workspace.store'), [
                'idempotency_key' => 'phase1-external-label-total-rejected',
                'note' => [
                    'customer_name' => 'Phase 1 External Label Total Rejected',
                    'customer_phone' => '08123',
                    'transaction_date' => '2026-03-15',
                ],
                'items' => [[
                    'entry_mode' => 'service',
                    'part_source' => 'external_purchase',
                    'pricing_mode' => 'package_auto_split',
                    'package_total_rupiah' => 180000,
                    'pay_now' => 0,
                    'service' => [
                        'name' => 'External Label Total Target',
                        'price_rupiah' => 0,
                        'notes' => '',
                    ],
                    'product_lines' => [[
                        'product_id' => '',
                        'qty' => '',
                        'unit_price_rupiah' => '',
                    ]],
                    'external_purchase_lines' => [[
                        'label' => 'Bearing Total Only',
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

        $validationGap->assertRedirect(route('cashier.notes.workspace.create'));
        $validationGap->assertSessionHasErrors([
            'items.0.external_purchase_lines.0.qty',
            'items.0.external_purchase_lines.0.unit_cost_rupiah',
        ]);

        $response = $this->postWorkspace($user, 'phase1-external-package-gap', [[
            'entry_mode' => 'service',
            'part_source' => 'none',
            'pricing_mode' => 'package_auto_split',
            'package_total_rupiah' => 180000,
            'pay_now' => 0,
            'service' => [
                'name' => 'External Package Backend Gap',
                'price_rupiah' => 0,
                'notes' => '',
            ],
            'product_lines' => [[
                'product_id' => '',
                'qty' => '',
                'unit_price_rupiah' => '',
            ]],
            'external_purchase_lines' => [[
                'label' => 'Bearing Total Only',
                'qty' => '',
                'unit_cost_rupiah' => '',
                'total_rupiah' => 80000,
            ]],
        ]], 'Phase 1 External Package Gap');

        $response->assertRedirect(route('cashier.notes.index'));

        $noteId = (string) DB::table('notes')->where('customer_name', 'Phase 1 External Package Gap')->value('id');
        $workItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'total_rupiah' => 180000,
        ]);
        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => $workItemId,
            'service_price_rupiah' => 100000,
        ]);
        $this->assertDatabaseHas('work_item_external_purchase_lines', [
            'work_item_id' => $workItemId,
            'cost_description' => 'Bearing Total Only',
            'qty' => 1,
            'unit_cost_rupiah' => 80000,
            'line_total_rupiah' => 80000,
        ]);
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    private function postWorkspace(User $user, string $idempotencyKey, array $items, string $customerName): TestResponse
    {
        return $this->actingAs($user)->post(route('notes.workspace.store'), [
            'idempotency_key' => $idempotencyKey,
            'note' => [
                'customer_name' => $customerName,
                'customer_phone' => '08123',
                'transaction_date' => '2026-03-15',
            ],
            'items' => $items,
            'inline_payment' => [
                'decision' => 'skip',
                'payment_method' => null,
                'paid_at' => '2026-03-15',
            ],
        ]);
    }

    private function seedProduct(string $id, int $priceRupiah, int $avgCostRupiah): void
    {
        DB::table('products')->insert([
            'id' => $id,
            'kode_barang' => strtoupper(str_replace('-', '_', $id)),
            'nama_barang' => 'Produk ' . $id,
            'merek' => 'Phase 1',
            'ukuran' => null,
            'harga_jual' => $priceRupiah,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => $id,
            'qty_on_hand' => 20,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => $id,
            'avg_cost_rupiah' => $avgCostRupiah,
            'inventory_value_rupiah' => $avgCostRupiah * 20,
        ]);
    }
}
