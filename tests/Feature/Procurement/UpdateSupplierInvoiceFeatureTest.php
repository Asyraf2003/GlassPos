<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class UpdateSupplierInvoiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_editable_supplier_invoice(): void
    {
        $this->seedEditableInvoice();

        $response = $this->actingAs($this->user('admin'))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-1',
            ]), $this->updatePayload([
                'nomor_faktur' => 'INV-SUP-001-REV',
                'nama_pt_pengirim' => 'PT Sumber Makmur',
                'tanggal_pengiriman' => '2026-03-20',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-1',
                        'qty_pcs' => 3,
                        'line_total_rupiah' => 30000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', [
            'supplierInvoiceId' => 'invoice-1',
        ]));
        $response->assertSessionHas('success', 'Nota supplier berhasil diperbarui.');

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-1',
            'nomor_faktur' => 'INV-SUP-001-REV',
            'nomor_faktur_normalized' => 'inv-sup-001-rev',
            'tanggal_pengiriman' => '2026-03-20',
            'grand_total_rupiah' => 30000,
            'last_revision_no' => 2,
        ]);

        $this->assertDatabaseHas('supplier_invoice_versions', [
            'supplier_invoice_id' => 'invoice-1',
            'revision_no' => 2,
            'event_name' => 'supplier_invoice_updated',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'aggregate_type' => 'supplier_invoice',
            'aggregate_id' => 'invoice-1',
            'event_name' => 'supplier_invoice_updated',
        ]);
    }

    public function test_admin_can_add_new_line_when_updating_editable_supplier_invoice(): void
    {
        $this->seedEditableInvoice();
        $this->seedSecondProduct();

        $response = $this->actingAs($this->user('admin'))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-1',
            ]), $this->updatePayload([
                'nomor_faktur' => 'INV-SUP-001-REV',
                'nama_pt_pengirim' => 'PT Sumber Makmur',
                'tanggal_pengiriman' => '2026-03-20',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                    ],
                    [
                        'previous_line_id' => null,
                        'line_no' => 2,
                        'product_id' => 'product-2',
                        'qty_pcs' => 1,
                        'line_total_rupiah' => 15000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', [
            'supplierInvoiceId' => 'invoice-1',
        ]));
        $response->assertSessionHas('success', 'Nota supplier berhasil diperbarui.');

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-1',
            'grand_total_rupiah' => 35000,
            'last_revision_no' => 2,
        ]);

        $this->assertDatabaseHas('supplier_invoice_versions', [
            'supplier_invoice_id' => 'invoice-1',
            'revision_no' => 2,
            'event_name' => 'supplier_invoice_updated',
        ]);
    }

    public function test_admin_can_remove_existing_line_when_updating_editable_supplier_invoice(): void
    {
        $this->seedEditableInvoice();
        $this->seedSecondProduct();

        DB::table('supplier_invoice_lines')->insert([
            'id' => 'invoice-line-2',
            'supplier_invoice_id' => 'invoice-1',
            'revision_no' => 1,
            'is_current' => 1,
            'source_line_id' => null,
            'superseded_by_line_id' => null,
            'superseded_at' => null,
            'line_no' => 2,
            'product_id' => 'product-2',
            'product_kode_barang_snapshot' => 'KB-002',
            'product_nama_barang_snapshot' => 'Oli Mesin',
            'product_merek_snapshot' => 'Federal Oil',
            'product_ukuran_snapshot' => 1,
            'qty_pcs' => 1,
            'line_total_rupiah' => 15000,
            'unit_cost_rupiah' => 15000,
        ]);

        DB::table('supplier_invoices')
            ->where('id', 'invoice-1')
            ->update([
                'subtotal_before_tax_rupiah' => 35000,
                'tax_input' => null,
                'tax_mode' => 'none',
                'tax_rate_basis_points' => null,
                'tax_amount_rupiah' => 0,
                'grand_total_rupiah' => 35000,
            ]);

        $response = $this->actingAs($this->user('admin'))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-1',
            ]), $this->updatePayload([
                'nomor_faktur' => 'INV-SUP-001',
                'nama_pt_pengirim' => 'PT Sumber Makmur',
                'tanggal_pengiriman' => '2026-03-15',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', [
            'supplierInvoiceId' => 'invoice-1',
        ]));
        $response->assertSessionHas('success', 'Nota supplier berhasil diperbarui.');

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-1',
            'grand_total_rupiah' => 20000,
            'last_revision_no' => 2,
        ]);

        $this->assertDatabaseHas('supplier_invoice_versions', [
            'supplier_invoice_id' => 'invoice-1',
            'revision_no' => 2,
            'event_name' => 'supplier_invoice_updated',
        ]);
    }

    public function test_admin_can_update_supplier_invoice_with_tax_landed_cost(): void
    {
        $this->seedEditableInvoice();
        $this->seedSecondProduct();

        $response = $this->actingAs($this->user('admin'))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-1',
            ]), $this->updatePayload([
                'nomor_faktur' => 'INV-SUP-001-TAX',
                'nama_pt_pengirim' => 'PT Sumber Makmur',
                'tanggal_pengiriman' => '2026-03-20',
                'tax_input' => '10%',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                    ],
                    [
                        'previous_line_id' => null,
                        'line_no' => 2,
                        'product_id' => 'product-2',
                        'qty_pcs' => 3,
                        'line_total_rupiah' => 30000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', [
            'supplierInvoiceId' => 'invoice-1',
        ]));
        $response->assertSessionHas('success', 'Nota supplier berhasil diperbarui.');

        $invoice = DB::table('supplier_invoices')
            ->where('id', 'invoice-1')
            ->first();

        $this->assertNotNull($invoice);

        $this->assertSame('INV-SUP-001-TAX', (string) $invoice->nomor_faktur);
        $this->assertSame(50000, (int) $invoice->subtotal_before_tax_rupiah);
        $this->assertSame('10%', (string) $invoice->tax_input);
        $this->assertSame('percent', (string) $invoice->tax_mode);
        $this->assertSame(1000, (int) $invoice->tax_rate_basis_points);
        $this->assertSame(5000, (int) $invoice->tax_amount_rupiah);
        $this->assertSame(55000, (int) $invoice->grand_total_rupiah);
        $this->assertSame(2, (int) $invoice->last_revision_no);

        $line1 = DB::table('supplier_invoice_lines')
            ->where('supplier_invoice_id', 'invoice-1')
            ->where('product_id', 'product-1')
            ->where('is_current', 1)
            ->first();

        $line2 = DB::table('supplier_invoice_lines')
            ->where('supplier_invoice_id', 'invoice-1')
            ->where('product_id', 'product-2')
            ->where('is_current', 1)
            ->first();

        $this->assertNotNull($line1);
        $this->assertNotNull($line2);

        $this->assertSame(22000, (int) $line1->line_total_rupiah);
        $this->assertSame(11000, (int) $line1->unit_cost_rupiah);
        $this->assertSame(33000, (int) $line2->line_total_rupiah);
        $this->assertSame(11000, (int) $line2->unit_cost_rupiah);

        $this->assertDatabaseHas('supplier_invoice_versions', [
            'supplier_invoice_id' => 'invoice-1',
            'revision_no' => 2,
            'event_name' => 'supplier_invoice_updated',
        ]);
    }

    public function test_admin_update_supplier_invoice_redirects_to_index_when_invoice_is_missing(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'missing-invoice',
            ]), $this->updatePayload([
                'nomor_faktur' => 'INV-404',
                'nama_pt_pengirim' => 'PT Tidak Ada',
                'tanggal_pengiriman' => '2026-03-20',
                'lines' => [
                    [
                        'previous_line_id' => null,
                        'line_no' => 1,
                        'product_id' => 'product-1',
                        'qty_pcs' => 1,
                        'line_total_rupiah' => 1000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.index'));
        $response->assertSessionHas('error', 'Nota supplier tidak ditemukan.');
    }

    public function test_admin_can_still_update_supplier_invoice_when_payment_exists_under_new_edit_policy(): void
    {
        $this->seedEditableInvoice();

        DB::table('supplier_payments')->insert([
            'id' => 'payment-1',
            'supplier_invoice_id' => 'invoice-1',
            'amount_rupiah' => 5000,
            'paid_at' => '2026-03-16',
            'proof_status' => 'pending',
            'proof_storage_path' => null,
        ]);

        $response = $this->actingAs($this->user('admin'))
            ->from(route('admin.procurement.supplier-invoices.edit', [
                'supplierInvoiceId' => 'invoice-1',
            ]))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-1',
            ]), $this->updatePayload([
                'nomor_faktur' => 'INV-SUP-LOCKED',
                'nama_pt_pengirim' => 'PT Sumber Makmur',
                'tanggal_pengiriman' => '2026-03-20',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', [
            'supplierInvoiceId' => 'invoice-1',
        ]));
        $response->assertSessionHas('success', 'Nota supplier berhasil diperbarui.');

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-1',
            'nomor_faktur' => 'INV-SUP-LOCKED',
            'last_revision_no' => 2,
        ]);
    }

    private function updatePayload(array $overrides): array
    {
        return array_replace_recursive([
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
                    'qty_pcs' => 2,
                    'line_total_rupiah' => 20000,
                ],
            ],
        ], $overrides);
    }


    public function test_admin_update_supplier_invoice_rejects_mixed_header_and_line_tax_input(): void
    {
        $this->seedEditableInvoice();

        $payload = [
            'expected_revision_no' => 1,
            'change_reason' => 'Validasi pajak dobel',
            'nomor_faktur' => 'INV-SUP-001',
            'nama_pt_pengirim' => 'PT Sumber Makmur',
            'tanggal_pengiriman' => '2026-03-15',
            'tax_input' => '10%',
            'lines' => [
                [
                    'previous_line_id' => 'invoice-line-1',
                    'line_no' => 1,
                    'product_id' => 'product-1',
                    'qty_pcs' => 1,
                    'line_total_rupiah' => 100000,
                    'tax_input' => '11%',
                ],
            ],
        ];

        $response = $this->actingAs($this->user('admin'))
            ->putJson(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-1',
            ]), $payload);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['tax_input']);
    }

    public function test_admin_can_update_supplier_invoice_with_line_tax_input(): void
    {
        $this->seedEditableInvoice();

        $response = $this->actingAs($this->user('admin'))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-1',
            ]), [
                'nomor_faktur' => 'INV-SUP-LINE-TAX-UPDATE',
                'nama_pt_pengirim' => 'PT Supplier Line Tax Update',
                'tanggal_pengiriman' => '2026-03-13',
                'expected_revision_no' => 1,
                'change_reason' => 'Menambahkan pajak per rincian supplier.',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-1',
                        'qty_pcs' => 1,
                        'line_total_rupiah' => 100000,
                        'tax_input' => ' 11% ',
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', [
            'supplierInvoiceId' => 'invoice-1',
        ]));

        $line = DB::table('supplier_invoice_lines')
            ->where('supplier_invoice_id', 'invoice-1')
            ->where('product_id', 'product-1')
            ->where('is_current', true)
            ->first();

        $this->assertSame(111000, (int) $line->line_total_rupiah);
        $this->assertSame(111000, (int) $line->unit_cost_rupiah);
        $this->assertSame(100000, (int) $line->line_subtotal_before_tax_rupiah);
        $this->assertSame('11%', (string) $line->tax_input);
        $this->assertSame('percent', (string) $line->tax_mode);
        $this->assertSame(1100, (int) $line->tax_rate_basis_points);
        $this->assertSame(11000, (int) $line->tax_amount_rupiah);
    }


    private function seedEditableInvoice(): void
    {
        DB::table('suppliers')->insert([
            'id' => 'supplier-1',
            'nama_pt_pengirim' => 'PT Sumber Makmur',
            'nama_pt_pengirim_normalized' => 'pt sumber makmur',
        ]);

        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Ban Luar',
            'merek' => 'Federal',
            'ukuran' => 90,
            'harga_jual' => 35000,
        ]);

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
            'subtotal_before_tax_rupiah' => 20000,
            'tax_input' => null,
            'tax_mode' => 'none',
            'tax_rate_basis_points' => null,
            'tax_amount_rupiah' => 0,
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

    private function seedSecondProduct(): void
    {
        DB::table('products')->insert([
            'id' => 'product-2',
            'kode_barang' => 'KB-002',
            'nama_barang' => 'Oli Mesin',
            'merek' => 'Federal Oil',
            'ukuran' => 1,
            'harga_jual' => 15000,
        ]);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-procurement-update@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
