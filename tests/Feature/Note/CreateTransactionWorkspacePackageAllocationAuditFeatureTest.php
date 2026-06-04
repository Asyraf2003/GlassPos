<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateTransactionWorkspacePackageAllocationAuditFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_auto_split_create_transaction_records_explicit_package_allocation_audit_payload(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Package Audit',
            'email' => 'service-store-stock-package-audit@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        DB::table('products')->insert([
            'id' => 'product-package-audit-1',
            'kode_barang' => 'KB-PKG-AUDIT-001',
            'nama_barang' => 'Kampas Rem Audit',
            'merek' => 'Federal',
            'ukuran' => null,
            'harga_jual' => 40000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-package-audit-1',
            'qty_on_hand' => 10,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-package-audit-1',
            'avg_cost_rupiah' => 25000,
            'inventory_value_rupiah' => 250000,
        ]);

        $response = $this->actingAs($user)->post(route('notes.workspace.store'), [
            'idempotency_key' => 'create-workspace-package-allocation-audit-idem-001',
            'note' => [
                'customer_name' => 'Budi Audit',
                'customer_phone' => '08123',
                'transaction_date' => '2026-03-15',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 150000,
                'pay_now' => 0,
                'service' => [
                    'name' => 'Servis Rem Audit',
                    'price_rupiah' => 0,
                    'notes' => '',
                ],
                'product_lines' => [[
                    'product_id' => 'product-package-audit-1',
                    'qty' => 1,
                    'unit_price_rupiah' => 40000,
                ]],
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

        $response->assertRedirect(route('cashier.notes.index'));

        $noteId = (string) DB::table('notes')->where('customer_name', 'Budi Audit')->value('id');
        $workItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');
        $storeStockLineId = (string) DB::table('work_item_store_stock_lines')->where('work_item_id', $workItemId)->value('id');

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
                'store_stock_line_id' => $storeStockLineId,
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 150000,
                'sparepart_total_rupiah' => 40000,
                'service_price_rupiah' => 110000,
                'product_id' => 'product-package-audit-1',
                'qty' => 1,
                'product_unit_price_rupiah' => 40000,
            ],
        ], $context['package_allocations'] ?? null);
    }
}
