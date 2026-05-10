<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsProcurementDuplicateIsolationMatrixFixture;
use Tests\TestCase;

final class ExtremeProcurementDuplicateAndIsolationMatrixFeatureTest extends TestCase
{
    use RefreshDatabase, SeedsProcurementDuplicateIsolationMatrixFixture;

    public function test_admin_cannot_create_invoice_with_duplicate_product_two_lines(): void
    {
        $this->seedSupplierAndProducts();

        $r = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.create'))
            ->post(route('admin.procurement.supplier-invoices.store'), $this->createPayload([
                'lines' => [
                    ['line_no' => 1, 'product_id' => 'product-1', 'qty_pcs' => 1, 'line_total_rupiah' => 10000],
                    ['line_no' => 2, 'product_id' => 'product-1', 'qty_pcs' => 2, 'line_total_rupiah' => 20000],
                ],
            ]));

        $r->assertRedirect(route('admin.procurement.supplier-invoices.create'))
            ->assertSessionHasErrors(['lines.1.product_id']);
    }

    public function test_admin_cannot_update_invoice_with_duplicate_existing_and_new_line(): void
    {
        $this->seedSupplierAndProducts();
        $this->seedInvoice('invoice-1', 'INV-SUP-001', 20000);

        $r = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.edit', ['supplierInvoiceId' => 'invoice-1']))
            ->put(route('admin.procurement.supplier-invoices.update', ['supplierInvoiceId' => 'invoice-1']), $this->updatePayload([
                'lines' => [
                    ['previous_line_id' => 'invoice-1-line-1', 'line_no' => 1, 'product_id' => 'product-1', 'qty_pcs' => 1, 'line_total_rupiah' => 10000],
                    ['previous_line_id' => null, 'line_no' => 2, 'product_id' => 'product-1', 'qty_pcs' => 2, 'line_total_rupiah' => 20000],
                ],
            ]));

        $r->assertRedirect(route('admin.procurement.supplier-invoices.edit', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHasErrors(['lines.1.product_id']);

        $this->assertDatabaseHas('supplier_invoices', ['id' => 'invoice-1', 'last_revision_no' => 1]);
    }

    public function test_admin_cannot_update_invoice_with_duplicate_after_copy_paste_reverse_order(): void
    {
        $this->seedSupplierAndProducts();
        $this->seedInvoice('invoice-1', 'INV-SUP-001', 35000, 'product-1', 2, 20000, 10000);

        DB::table('supplier_invoice_lines')->insert([
            'id' => 'invoice-1-line-2',
            'supplier_invoice_id' => 'invoice-1',
            'revision_no' => 1,
            'is_current' => 1,
            'source_line_id' => null,
            'superseded_by_line_id' => null,
            'superseded_at' => null,
            'line_no' => 2,
            'product_id' => 'product-2',
            'product_kode_barang_snapshot' => 'KB-002',
            'product_nama_barang_snapshot' => 'Ban Dalam',
            'product_merek_snapshot' => 'Federal',
            'product_ukuran_snapshot' => 90,
            'qty_pcs' => 1,
            'line_total_rupiah' => 15000,
            'unit_cost_rupiah' => 15000,
        ]);

        DB::table('supplier_invoices')->where('id', 'invoice-1')->update(['grand_total_rupiah' => 35000]);

        $r = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.edit', ['supplierInvoiceId' => 'invoice-1']))
            ->put(route('admin.procurement.supplier-invoices.update', ['supplierInvoiceId' => 'invoice-1']), $this->updatePayload([
                'lines' => [
                    ['previous_line_id' => 'invoice-1-line-2', 'line_no' => 1, 'product_id' => 'product-2', 'qty_pcs' => 1, 'line_total_rupiah' => 15000],
                    ['previous_line_id' => 'invoice-1-line-1', 'line_no' => 2, 'product_id' => 'product-2', 'qty_pcs' => 2, 'line_total_rupiah' => 20000],
                ],
            ]));

        $r->assertRedirect(route('admin.procurement.supplier-invoices.edit', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHasErrors(['lines.1.product_id']);
    }

    public function test_same_product_is_allowed_across_two_different_invoices(): void
    {
        $this->seedSupplierAndProducts();
        $this->seedInvoice('invoice-1', 'INV-SUP-001', 20000, 'product-1');
        $this->seedInvoice('invoice-2', 'INV-SUP-002', 30000, 'product-1', 3, 30000, 10000);

        $this->assertDatabaseHas('supplier_invoice_lines', ['supplier_invoice_id' => 'invoice-1', 'product_id' => 'product-1']);
        $this->assertDatabaseHas('supplier_invoice_lines', ['supplier_invoice_id' => 'invoice-2', 'product_id' => 'product-1']);
    }

    public function test_editing_invoice_one_does_not_change_invoice_two(): void
    {
        $this->seedSupplierAndProducts();
        $this->seedInvoice('invoice-1', 'INV-SUP-001', 20000, 'product-1');
        $this->seedInvoice('invoice-2', 'INV-SUP-002', 30000, 'product-1', 3, 30000, 10000);

        $r = $this->actingAs($this->admin())
            ->put(route('admin.procurement.supplier-invoices.update', ['supplierInvoiceId' => 'invoice-1']), $this->updatePayload([
                'nomor_faktur' => 'INV-SUP-001-REV',
                'lines' => [
                    ['previous_line_id' => 'invoice-1-line-1', 'line_no' => 1, 'product_id' => 'product-1', 'qty_pcs' => 1, 'line_total_rupiah' => 10000],
                ],
            ]));

        $r->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHas('success', 'Nota supplier berhasil diperbarui.');

        $this->assertDatabaseHas('supplier_invoices', ['id' => 'invoice-1', 'grand_total_rupiah' => 10000, 'last_revision_no' => 2]);
        $this->assertDatabaseHas('supplier_invoices', ['id' => 'invoice-2', 'grand_total_rupiah' => 30000, 'last_revision_no' => 1]);
    }

    public function test_same_product_different_prices_across_two_invoices_remain_separate(): void
    {
        $this->seedSupplierAndProducts();
        $this->seedInvoice('invoice-1', 'INV-SUP-001', 20000, 'product-1', 2, 20000, 10000);
        $this->seedInvoice('invoice-2', 'INV-SUP-002', 24000, 'product-1', 2, 24000, 12000);

        $this->assertDatabaseHas('supplier_invoice_lines', ['supplier_invoice_id' => 'invoice-1', 'product_id' => 'product-1', 'unit_cost_rupiah' => 10000]);
        $this->assertDatabaseHas('supplier_invoice_lines', ['supplier_invoice_id' => 'invoice-2', 'product_id' => 'product-1', 'unit_cost_rupiah' => 12000]);
    }

    private function createPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'nomor_faktur' => 'INV-SUP-NEW',
            'nama_pt_pengirim' => 'PT Sumber Makmur',
            'tanggal_pengiriman' => '2026-03-15',
            'lines' => [
                ['line_no' => 1, 'product_id' => 'product-1', 'qty_pcs' => 1, 'line_total_rupiah' => 10000],
            ],
        ], $overrides);
    }

    private function updatePayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'expected_revision_no' => 1,
            'change_reason' => 'Koreksi matrix duplicate isolation.',
            'nomor_faktur' => 'INV-SUP-001',
            'nama_pt_pengirim' => 'PT Sumber Makmur',
            'tanggal_pengiriman' => '2026-03-15',
            'lines' => [
                ['previous_line_id' => 'invoice-1-line-1', 'line_no' => 1, 'product_id' => 'product-1', 'qty_pcs' => 2, 'line_total_rupiah' => 20000],
            ],
        ], $overrides);
    }

    private function admin(): User
    {
        $u = User::query()->create([
            'name' => 'Admin Duplicate Matrix',
            'email' => 'admin-duplicate-matrix@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $u->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        DB::table('admin_transaction_capability_states')->insert([
            'actor_id' => (string) $u->getAuthIdentifier(),
            'active' => true,
        ]);

        return $u;
    }
}
