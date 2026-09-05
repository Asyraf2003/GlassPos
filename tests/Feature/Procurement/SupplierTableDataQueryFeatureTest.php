<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SupplierTableDataQueryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_supplier_table(): void
    {
        $this->seedSupplier('supplier-1', 'PT Federal Abadi');
        $this->seedSupplier('supplier-2', 'PT Astra Otoparts');
        $this->seedSupplier('supplier-3', 'CV Toko Lokal');

        $this->syncSupplierListProjectionForTest('supplier-1');
        $this->syncSupplierListProjectionForTest('supplier-2');
        $this->syncSupplierListProjectionForTest('supplier-3');

        $response = $this->actingAs($this->admin())->get(route('admin.suppliers.table', ['q' => 'PT']));

        $response->assertOk();
        $response->assertJsonCount(2, 'data.rows');
        $response->assertJsonPath('data.meta.filters.q', 'PT');
    }

    public function test_admin_can_sort_supplier_table_by_outstanding_rupiah_desc(): void
    {
        $this->seedSupplier('supplier-1', 'PT Alpha Motor');
        $this->seedSupplier('supplier-2', 'PT Zebra Parts');

        $this->seedInvoice('invoice-1', 'supplier-1', '2026-03-15', '2026-04-15', 100000);
        $this->seedInvoice('invoice-2', 'supplier-2', '2026-03-16', '2026-04-16', 100000);

        $this->seedPayment('payment-1', 'invoice-1', 70000, '2026-03-16', 'pending');
        $this->seedPayment('payment-2', 'invoice-2', 10000, '2026-03-17', 'pending');

        $this->syncSupplierListProjectionForTest('supplier-1');
        $this->syncSupplierListProjectionForTest('supplier-2');

        $response = $this->actingAs($this->admin())->get(route('admin.suppliers.table', [
            'sort_by' => 'outstanding_rupiah',
            'sort_dir' => 'desc',
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.rows.0.nama_pt_pengirim', 'PT Zebra Parts');
        $response->assertJsonPath('data.rows.0.outstanding_rupiah', 90000);
        $response->assertJsonPath('data.rows.1.nama_pt_pengirim', 'PT Alpha Motor');
        $response->assertJsonPath('data.rows.1.outstanding_rupiah', 30000);
    }

    public function test_admin_can_filter_suppliers_by_outstanding_status(): void
    {
        $this->seedSupplier('supplier-outstanding', 'PT Masih Berhutang');
        $this->seedSupplier('supplier-settled', 'PT Sudah Lunas');
        $this->seedInvoice('invoice-outstanding', 'supplier-outstanding', '2026-03-15', '2026-04-15', 100000);
        $this->seedInvoice('invoice-settled', 'supplier-settled', '2026-03-16', '2026-04-16', 100000);
        $this->seedPayment('payment-settled', 'invoice-settled', 100000, '2026-03-17', 'uploaded');
        $this->syncSupplierListProjectionForTest('supplier-outstanding');
        $this->syncSupplierListProjectionForTest('supplier-settled');

        $response = $this->actingAs($this->admin())->getJson(route('admin.suppliers.table', [
            'status' => 'outstanding',
        ]));

        $response->assertOk()
            ->assertJsonPath('data.meta.filters.status', 'outstanding')
            ->assertJsonCount(1, 'data.rows')
            ->assertJsonPath('data.rows.0.id', 'supplier-outstanding');
    }

    public function test_admin_can_get_supplier_summary_aggregation_from_multiple_invoices(): void
    {
        $this->seedSupplier('supplier-1', 'PT Sumber Makmur');

        $this->seedInvoice('invoice-1', 'supplier-1', '2026-03-15', '2026-04-15', 100000);
        $this->seedInvoice('invoice-2', 'supplier-1', '2026-03-18', '2026-04-18', 50000);

        $this->seedPayment('payment-1', 'invoice-1', 25000, '2026-03-16', 'pending');
        $this->seedPayment('payment-2', 'invoice-2', 50000, '2026-03-18', 'uploaded');

        $this->syncSupplierListProjectionForTest('supplier-1');

        $response = $this->actingAs($this->admin())->get(route('admin.suppliers.table', [
            'q' => 'Sumber',
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data.rows');
        $response->assertJsonPath('data.rows.0.nama_pt_pengirim', 'PT Sumber Makmur');
        $response->assertJsonPath('data.rows.0.invoice_count', 2);
        $response->assertJsonPath('data.rows.0.outstanding_rupiah', 75000);
        $response->assertJsonPath('data.rows.0.invoice_unpaid_count', 1);
        $response->assertJsonPath('data.rows.0.last_shipment_date', '2026-03-18');
    }

    public function test_admin_can_sort_supplier_table_by_last_shipment_date_desc_and_keep_supplier_without_invoice_at_bottom(): void
    {
        $this->seedSupplier('supplier-1', 'PT Alpha Motor');
        $this->seedSupplier('supplier-2', 'PT Beta Parts');
        $this->seedSupplier('supplier-3', 'PT Gamma Abadi');

        $this->seedInvoice('invoice-1', 'supplier-1', '2026-03-15', '2026-04-15', 100000);
        $this->seedInvoice('invoice-2', 'supplier-2', '2026-03-18', '2026-04-18', 50000);

        $this->syncSupplierListProjectionForTest('supplier-1');
        $this->syncSupplierListProjectionForTest('supplier-2');
        $this->syncSupplierListProjectionForTest('supplier-3');

        $response = $this->actingAs($this->admin())->get(route('admin.suppliers.table', [
            'sort_by' => 'last_shipment_date',
            'sort_dir' => 'desc',
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.rows.0.nama_pt_pengirim', 'PT Beta Parts');
        $response->assertJsonPath('data.rows.0.last_shipment_date', '2026-03-18');
        $response->assertJsonPath('data.rows.1.nama_pt_pengirim', 'PT Alpha Motor');
        $response->assertJsonPath('data.rows.1.last_shipment_date', '2026-03-15');
        $response->assertJsonPath('data.rows.2.nama_pt_pengirim', 'PT Gamma Abadi');
        $response->assertJsonPath('data.rows.2.last_shipment_date', null);
    }

    public function test_admin_can_access_second_page_of_supplier_table(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            $supplierId = 'supplier-' . $i;

            $this->seedSupplier(
                $supplierId,
                'Supplier ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            );

            $this->syncSupplierListProjectionForTest($supplierId);
        }

        $response = $this->actingAs($this->admin())->get(route('admin.suppliers.table', ['page' => 2]));

        $response->assertOk();
        $response->assertJsonPath('data.meta.page', 2);
        $response->assertJsonPath('data.meta.last_page', 2);
        $response->assertJsonPath('data.rows.0.nama_pt_pengirim', 'Supplier 11');
        $response->assertJsonPath('data.rows.0.invoice_count', 0);
        $response->assertJsonPath('data.rows.0.outstanding_rupiah', 0);
        $response->assertJsonPath('data.rows.0.invoice_unpaid_count', 0);
        $response->assertJsonPath('data.rows.0.last_shipment_date', null);
    }

    private function admin(): User
    {
        $user = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $user;
    }

    private function seedSupplier(string $id, string $namaPtPengirim): void
    {
        DB::table('suppliers')->insert([
            'id' => $id,
            'nama_pt_pengirim' => $namaPtPengirim,
            'nama_pt_pengirim_normalized' => mb_strtolower($namaPtPengirim),
        ]);
    }

    private function seedInvoice(
        string $id,
        string $supplierId,
        string $shipmentDate,
        string $dueDate,
        int $grandTotal,
    ): void {
        DB::table('supplier_invoices')->insert([
            'id' => $id,
            'supplier_id' => $supplierId,
            'tanggal_pengiriman' => $shipmentDate,
            'jatuh_tempo' => $dueDate,
            'grand_total_rupiah' => $grandTotal,
        ]);
    }

    private function seedPayment(
        string $id,
        string $invoiceId,
        int $amount,
        string $paidAt,
        string $proofStatus,
    ): void {
        DB::table('supplier_payments')->insert([
            'id' => $id,
            'supplier_invoice_id' => $invoiceId,
            'amount_rupiah' => $amount,
            'paid_at' => $paidAt,
            'proof_status' => $proofStatus,
            'proof_storage_path' => null,
        ]);
    }
}
