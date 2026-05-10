<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SupplierInvoiceDuplicateProductValidationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_create_supplier_invoice_with_duplicate_product_in_multiple_lines(): void
    {
        $this->seedSupplier();
        $this->seedProducts();

        $response = $this->actingAs($this->user('admin'))
            ->from(route('admin.procurement.supplier-invoices.create'))
            ->post(route('admin.procurement.supplier-invoices.store'), [
                'nomor_faktur' => 'INV-DUP-001',
                'nama_pt_pengirim' => 'PT Sumber Makmur',
                'tanggal_pengiriman' => '2026-03-15',
                'lines' => [
                    [
                        'line_no' => 1,
                        'product_id' => 'product-1',
                        'qty_pcs' => 1,
                        'line_total_rupiah' => 10000,
                    ],
                    [
                        'line_no' => 2,
                        'product_id' => 'product-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.create'));
        $response->assertSessionHasErrors([
            'lines.1.product_id' => 'Baris 2: produk yang sama sudah dipakai di baris 1.',
        ]);

        $this->assertDatabaseMissing('supplier_invoices', [
            'nomor_faktur' => 'INV-DUP-001',
        ]);
    }

    public function test_admin_cannot_update_supplier_invoice_with_duplicate_product_in_multiple_lines(): void
    {
        $this->seedSupplier();
        $this->seedProducts();
        $this->seedEditableInvoice();

        $response = $this->actingAs($this->user('admin'))
            ->from(route('admin.procurement.supplier-invoices.edit', [
                'supplierInvoiceId' => 'invoice-1',
            ]))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-1',
            ]), [
                'expected_revision_no' => 1,
                'change_reason' => 'Perbaikan data faktur supplier.',
                'nomor_faktur' => 'INV-SUP-001',
                'nama_pt_pengirim' => 'PT Sumber Makmur',
                'tanggal_pengiriman' => '2026-03-15',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-1',
                        'qty_pcs' => 1,
                        'line_total_rupiah' => 10000,
                    ],
                    [
                        'previous_line_id' => null,
                        'line_no' => 2,
                        'product_id' => 'product-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.edit', [
            'supplierInvoiceId' => 'invoice-1',
        ]));
        $response->assertSessionHasErrors([
            'lines.1.product_id' => 'Baris 2: produk yang sama sudah dipakai di baris 1.',
        ]);

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-1',
            'nomor_faktur' => 'INV-SUP-001',
            'last_revision_no' => 1,
        ]);

        $this->assertDatabaseMissing('supplier_invoice_versions', [
            'supplier_invoice_id' => 'invoice-1',
            'revision_no' => 2,
            'event_name' => 'supplier_invoice_updated',
        ]);
    }

    private function seedSupplier(): void
    {
        DB::table('suppliers')->insert([
            'id' => 'supplier-1',
            'nama_pt_pengirim' => 'PT Sumber Makmur',
            'nama_pt_pengirim_normalized' => 'pt sumber makmur',
        ]);
    }

    private function seedProducts(): void
    {
        DB::table('products')->insert([
            [
                'id' => 'product-1',
                'kode_barang' => 'KB-001',
                'nama_barang' => 'Ban Luar',
                'merek' => 'Federal',
                'ukuran' => 90,
                'harga_jual' => 35000,
            ],
            [
                'id' => 'product-2',
                'kode_barang' => 'KB-002',
                'nama_barang' => 'Oli Mesin',
                'merek' => 'Federal Oil',
                'ukuran' => 1,
                'harga_jual' => 15000,
            ],
        ]);
    }

    private function seedEditableInvoice(): void
    {
        DB::table('supplier_invoices')->insert([
            'id' => 'invoice-1',
            'supplier_id' => 'supplier-1',
            'supplier_nama_pt_pengirim_snapshot' => 'PT Sumber Makmur',
            'nomor_faktur' => 'INV-SUP-001',
            'nomor_faktur_normalized' => 'inv-sup-001',
            'document_kind' => 'invoice',
            'lifecycle_status' => 'active',
            'origin_supplier_invoice_id' => null,
            'superseded_by_supplier_invoice_id' => null,
            'tanggal_pengiriman' => '2026-03-15',
            'jatuh_tempo' => '2026-04-14',
            'grand_total_rupiah' => 20000,
            'voided_at' => null,
            'void_reason' => null,
            'last_revision_no' => 1,
        ]);

        DB::table('supplier_invoice_lines')->insert([
            'id' => 'invoice-line-1',
            'supplier_invoice_id' => 'invoice-1',
            'revision_no' => 1,
            'is_current' => 1,
            'source_line_id' => null,
            'superseded_by_line_id' => null,
            'superseded_at' => null,
            'line_no' => 1,
            'product_id' => 'product-1',
            'product_kode_barang_snapshot' => 'KB-001',
            'product_nama_barang_snapshot' => 'Ban Luar',
            'product_merek_snapshot' => 'Federal',
            'product_ukuran_snapshot' => 90,
            'qty_pcs' => 2,
            'line_total_rupiah' => 20000,
            'unit_cost_rupiah' => 10000,
        ]);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-procurement-duplicate-product@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        if ($role === 'admin') {
            DB::table('admin_transaction_capability_states')->insert([
                'actor_id' => (string) $user->getAuthIdentifier(),
                'active' => true,
            ]);
        }

        return $user;
    }
}
