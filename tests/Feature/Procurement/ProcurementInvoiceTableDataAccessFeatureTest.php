<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalProcurementFixture;
use Tests\TestCase;

final class ProcurementInvoiceTableDataAccessFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProcurementFixture;

    public function test_guest_is_redirected_to_login_when_accessing_procurement_invoice_table_data(): void
    {
        $this->get(route('admin.procurement.supplier-invoices.table'))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_procurement_invoice_table_data(): void
    {
        $response = $this->actingAs($this->user('kasir'))
            ->get(route('admin.procurement.supplier-invoices.table'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_get_procurement_invoice_table_json_with_action_contracts_for_locked_and_editable_rows(): void
    {
        $this->seedSupplier('supplier-1', 'PT Supplier Baru');
        $this->seedProductFixture();

        $this->seedInvoice('invoice-locked', 'supplier-1', '2026-03-15', '2026-04-15', 100000, 'PT Federal Abadi');
        $this->seedInvoiceLine('invoice-line-locked', 'invoice-locked');
        $this->seedPayment('payment-1', 'invoice-locked', 40000, '2026-03-16', 'pending');
        $this->seedReceipt('receipt-1', 'invoice-locked', '2026-03-17');
        $this->seedReceiptLine('receipt-line-1', 'receipt-1', 'invoice-line-locked', 3);

        $this->seedInvoice('invoice-editable', 'supplier-1', '2026-03-18', '2026-04-18', 120000, 'PT Federal Abadi');
        $this->seedInvoiceLine('invoice-line-editable', 'invoice-editable');

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.table'));

        $response->assertOk();
        $response->assertJsonPath('success', true);

        /** @var list<array<string, mixed>> $rows */
        $rows = $response->json('data.rows');
        $rowCollection = collect($rows);

        $lockedRow = $this->findRow($rowCollection, 'invoice-locked');
        $editableRow = $this->findRow($rowCollection, 'invoice-editable');

        self::assertNotNull($lockedRow);
        self::assertNotNull($editableRow);

        self::assertSame('PT Supplier Baru', $lockedRow['supplier_nama_pt_pengirim_current']);
        self::assertSame('PT Federal Abadi', $lockedRow['supplier_nama_pt_pengirim_snapshot']);
        self::assertSame(40000, $lockedRow['total_paid_rupiah']);
        self::assertSame(60000, $lockedRow['outstanding_rupiah']);
        self::assertSame(1, $lockedRow['receipt_count']);
        self::assertSame(3, $lockedRow['total_received_qty']);
        self::assertSame('locked', $lockedRow['policy_state']);

        self::assertSame('proof', $lockedRow['payment_action_kind']);
        self::assertSame('Bukti Bayar', $lockedRow['payment_action_label']);
        self::assertSame('link', $lockedRow['payment_action_mode']);
        self::assertSame(
            route('admin.procurement.supplier-invoices.payment-proofs.show', ['supplierInvoiceId' => 'invoice-locked']),
            $lockedRow['payment_action_url']
        );

        self::assertSame('revise', $lockedRow['edit_action_kind']);
        self::assertSame('Koreksi', $lockedRow['edit_action_label']);
        self::assertSame(
            route('admin.procurement.supplier-invoices.revise', ['supplierInvoiceId' => 'invoice-locked']),
            $lockedRow['edit_action_url']
        );

        self::assertFalse($lockedRow['void_action_enabled']);
        self::assertSame('Hapus Nota', $lockedRow['void_action_label']);
        self::assertSame(
            route('admin.procurement.supplier-invoices.void', ['supplierInvoiceId' => 'invoice-locked']),
            $lockedRow['void_action_url']
        );

        self::assertSame('editable', $editableRow['policy_state']);

        self::assertSame('payment_proof', $editableRow['payment_action_kind']);
        self::assertSame('Bayar', $editableRow['payment_action_label']);
        self::assertSame('modal', $editableRow['payment_action_mode']);
        self::assertSame(
            route('admin.procurement.supplier-invoices.payment-proofs.show', ['supplierInvoiceId' => 'invoice-editable']),
            $editableRow['payment_action_url']
        );

        self::assertSame('edit', $editableRow['edit_action_kind']);
        self::assertSame('Edit Nota', $editableRow['edit_action_label']);
        self::assertSame(
            route('admin.procurement.supplier-invoices.edit', ['supplierInvoiceId' => 'invoice-editable']),
            $editableRow['edit_action_url']
        );

        self::assertTrue($editableRow['void_action_enabled']);
        self::assertSame('Hapus Nota', $editableRow['void_action_label']);
        self::assertSame(
            route('admin.procurement.supplier-invoices.void', ['supplierInvoiceId' => 'invoice-editable']),
            $editableRow['void_action_url']
        );
    }

    private function findRow(Collection $rows, string $invoiceId): ?array
    {
        /** @var array<string, mixed>|null $row */
        $row = $rows->first(
            fn (array $candidate): bool => ($candidate['supplier_invoice_id'] ?? null) === $invoiceId
        );

        return $row;
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
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
    }

    private function seedInvoice(
        string $id,
        string $supplierId,
        string $shipmentDate,
        string $dueDate,
        int $grandTotal,
        string $supplierNamaPtPengirimSnapshot = 'PT Federal Abadi'
    ): void {
        $this->seedMinimalSupplierInvoice(
            $id,
            $supplierId,
            $shipmentDate,
            $dueDate,
            $grandTotal,
            $supplierNamaPtPengirimSnapshot
        );
    }

    private function seedInvoiceLine(string $id, string $invoiceId): void
    {
        $this->seedMinimalSupplierInvoiceLine(
            $id,
            $invoiceId,
            'product-1',
            3,
            100000,
            33333,
            'KB-001',
            'Ban Luar',
            'Federal',
            100
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
