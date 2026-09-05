<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalProcurementFixture;
use Tests\TestCase;

final class ProcurementInvoiceTableDataQueryFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProcurementFixture;

    public function test_admin_can_search_procurement_invoice_table_by_supplier_name(): void
    {
        $this->seedSupplier('supplier-1', 'PT Federal Abadi');
        $this->seedSupplier('supplier-2', 'PT Astra Otoparts');

        $this->seedInvoice('invoice-1', 'supplier-1', '2026-03-15', '2026-04-15', 100000, 'PT Federal Abadi');
        $this->seedInvoice('invoice-2', 'supplier-2', '2026-03-16', '2026-04-16', 50000, 'PT Astra Otoparts');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.procurement.supplier-invoices.table', ['q' => 'Federal']));

        $response->assertOk();
        $response->assertJsonCount(1, 'data.rows');
        $response->assertJsonPath('data.rows.0.supplier_invoice_id', 'invoice-1');
        $response->assertJsonPath('data.meta.filters.q', 'Federal');
    }

    public function test_admin_can_search_procurement_invoice_table_by_current_or_snapshot_supplier_name(): void
    {
        $this->seedSupplier('supplier-1', 'PT Supplier Baru');
        $this->seedInvoice('invoice-1', 'supplier-1', '2026-03-15', '2026-04-15', 100000, 'PT Federal Abadi');

        $admin = $this->admin();

        $responseCurrent = $this->actingAs($admin)
            ->get(route('admin.procurement.supplier-invoices.table', ['q' => 'Supplier Baru']));

        $responseCurrent->assertOk();
        $responseCurrent->assertJsonCount(1, 'data.rows');
        $responseCurrent->assertJsonPath('data.rows.0.supplier_invoice_id', 'invoice-1');
        $responseCurrent->assertJsonPath('data.rows.0.supplier_nama_pt_pengirim_current', 'PT Supplier Baru');
        $responseCurrent->assertJsonPath('data.rows.0.supplier_nama_pt_pengirim_snapshot', 'PT Federal Abadi');

        $responseSnapshot = $this->actingAs($admin)
            ->get(route('admin.procurement.supplier-invoices.table', ['q' => 'Federal Abadi']));

        $responseSnapshot->assertOk();
        $responseSnapshot->assertJsonCount(1, 'data.rows');
        $responseSnapshot->assertJsonPath('data.rows.0.supplier_invoice_id', 'invoice-1');
    }

    public function test_default_search_prioritizes_exact_invoice_identity_over_newer_supplier_match(): void
    {
        $this->seedSupplier('supplier-exact-invoice', 'PT Lain');
        $this->seedSupplier('supplier-broad-match', 'PT INV-FOCUS Distributor');
        $this->seedInvoice('invoice-exact', 'supplier-exact-invoice', '2026-03-10', '2026-04-10', 100000, 'PT Lain', 'INV-FOCUS');
        $this->seedInvoice('invoice-broad', 'supplier-broad-match', '2026-03-20', '2026-04-20', 100000, 'PT INV-FOCUS Distributor', 'OTHER-001');

        $response = $this->actingAs($this->admin())->getJson(route('admin.procurement.supplier-invoices.table', [
            'q' => 'INV-FOCUS',
        ]));

        $response->assertOk()
            ->assertJsonPath('data.rows.0.supplier_invoice_id', 'invoice-exact')
            ->assertJsonPath('data.meta.sort_by', 'relevance');
    }

    public function test_admin_can_sort_procurement_invoice_table_by_outstanding_desc(): void
    {
        $this->seedSupplier('supplier-1', 'PT Alpha Motor');
        $this->seedSupplier('supplier-2', 'PT Zebra Parts');

        $this->seedInvoice('invoice-1', 'supplier-1', '2026-03-15', '2026-04-15', 100000, 'PT Alpha Motor');
        $this->seedInvoice('invoice-2', 'supplier-2', '2026-03-16', '2026-04-16', 100000, 'PT Zebra Parts');

        $this->seedPayment('payment-1', 'invoice-1', 70000, '2026-03-16', 'pending');
        $this->seedPayment('payment-2', 'invoice-2', 10000, '2026-03-17', 'pending');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.procurement.supplier-invoices.table', [
                'sort_by' => 'outstanding_rupiah',
                'sort_dir' => 'desc',
            ]));

        $response->assertOk();
        $response->assertJsonPath('data.rows.0.supplier_invoice_id', 'invoice-2');
        $response->assertJsonPath('data.rows.1.supplier_invoice_id', 'invoice-1');
    }

    public function test_admin_can_filter_procurement_invoice_table_by_shipment_date_range(): void
    {
        $this->seedSupplier('supplier-1', 'PT Federal Abadi');

        $this->seedInvoice('invoice-1', 'supplier-1', '2026-03-10', '2026-04-10', 100000, 'PT Federal Abadi');
        $this->seedInvoice('invoice-2', 'supplier-1', '2026-03-15', '2026-04-15', 110000, 'PT Federal Abadi');
        $this->seedInvoice('invoice-3', 'supplier-1', '2026-03-20', '2026-04-20', 120000, 'PT Federal Abadi');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.procurement.supplier-invoices.table', [
                'shipment_date_from' => '2026-03-12',
                'shipment_date_to' => '2026-03-18',
                'sort_by' => 'shipment_date',
                'sort_dir' => 'asc',
            ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data.rows');
        $response->assertJsonPath('data.rows.0.supplier_invoice_id', 'invoice-2');
        $response->assertJsonPath('data.meta.filters.shipment_date_from', '2026-03-12');
        $response->assertJsonPath('data.meta.filters.shipment_date_to', '2026-03-18');
    }

    public function test_admin_can_access_second_page_of_procurement_invoice_table(): void
    {
        $this->seedSupplier('supplier-1', 'PT Federal Abadi');

        for ($i = 1; $i <= 11; $i++) {
            $this->seedInvoice(
                'invoice-' . $i,
                'supplier-1',
                '2026-03-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                '2026-04-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                10000 + $i,
                'PT Federal Abadi'
            );
        }

        $response = $this->actingAs($this->admin())
            ->get(route('admin.procurement.supplier-invoices.table', [
                'page' => 2,
                'sort_by' => 'shipment_date',
                'sort_dir' => 'asc',
            ]));

        $response->assertOk();
        $response->assertJsonPath('data.meta.page', 2);
        $response->assertJsonPath('data.meta.last_page', 2);
        $response->assertJsonPath('data.rows.0.supplier_invoice_id', 'invoice-11');
    }

    public function test_admin_can_view_receipt_aggregations_in_procurement_invoice_table(): void
    {
        $this->seedSupplier('supplier-1', 'PT Makmur');
        $this->seedProductFixture();
        $this->seedInvoice('invoice-1', 'supplier-1', '2026-03-15', '2026-04-15', 100000, 'PT Makmur');

        $this->seedInvoiceLine('line-1', 'invoice-1', 5, 50000, 10000, 'product-1');
        $this->seedInvoiceLine('line-2', 'invoice-1', 10, 50000, 5000, 'product-2');

        $this->seedReceipt('receipt-1', 'invoice-1', '2026-03-16');
        $this->seedReceiptLine('receipt-line-1', 'receipt-1', 'line-1', 5);

        $this->seedReceipt('receipt-2', 'invoice-1', '2026-03-17');
        $this->seedReceiptLine('receipt-line-2', 'receipt-2', 'line-2', 10);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.procurement.supplier-invoices.table'));

        $response->assertOk();
        $response->assertJsonPath('data.rows.0.receipt_count', 2);
        $response->assertJsonPath('data.rows.0.total_received_qty', 15);
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
        $this->seedMinimalSupplier($id, $namaPtPengirim, mb_strtolower($namaPtPengirim));
    }

    private function seedProductFixture(): void
    {
        $this->seedMinimalProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 100, 75000);
        $this->seedMinimalProduct('product-2', 'KB-002', 'Kampas Rem', 'Federal', 90, 50000);
    }

    private function seedInvoice(
        string $id,
        string $supplierId,
        string $shipmentDate,
        string $dueDate,
        int $grandTotal,
        string $supplierNamaPtPengirimSnapshot,
        ?string $nomorFaktur = null,
    ): void {
        $this->seedMinimalSupplierInvoice(
            $id,
            $supplierId,
            $shipmentDate,
            $dueDate,
            $grandTotal,
            $supplierNamaPtPengirimSnapshot,
            $nomorFaktur,
        );
    }

    private function seedInvoiceLine(
        string $id,
        string $invoiceId,
        int $qtyPcs,
        int $lineTotalRupiah,
        int $unitCostRupiah,
        string $productId = 'product-1'
    ): void {
        $snapshots = [
            'product-1' => ['KB-001', 'Ban Luar', 'Federal', 100],
            'product-2' => ['KB-002', 'Kampas Rem', 'Federal', 90],
        ];

        [$kode, $nama, $merek, $ukuran] = $snapshots[$productId];

        $this->seedMinimalSupplierInvoiceLine(
            $id,
            $invoiceId,
            $productId,
            $qtyPcs,
            $lineTotalRupiah,
            $unitCostRupiah,
            $kode,
            $nama,
            $merek,
            $ukuran
        );
    }

    private function seedPayment(string $id, string $invoiceId, int $amount, string $paidAt, string $proofStatus): void
    {
        $this->seedMinimalSupplierPayment($id, $invoiceId, $amount, $paidAt, $proofStatus);
    }

    private function seedReceipt(string $id, string $invoiceId, string $tanggalTerima): void
    {
        $this->seedMinimalSupplierReceipt($id, $invoiceId, $tanggalTerima);
    }

    private function seedReceiptLine(string $id, string $receiptId, string $invoiceLineId, int $qtyDiterima): void
    {
        $this->seedMinimalSupplierReceiptLine($id, $receiptId, $invoiceLineId, $qtyDiterima);
    }
}
