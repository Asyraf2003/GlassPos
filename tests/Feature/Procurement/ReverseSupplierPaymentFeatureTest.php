<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Application\Reporting\UseCases\GetSupplierPayableSummaryHandler;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ReverseSupplierPaymentFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reverse_supplier_payment_and_restore_outstanding_precisely(): void
    {
        $this->seedInvoiceWithPayment();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->post(route('admin.procurement.supplier-payments.reverse.store', ['supplierPaymentId' => 'payment-1']), [
                'reason' => 'Pembayaran salah input nominal.',
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHas('success', 'Reversal pembayaran supplier berhasil dicatat.');

        $this->assertDatabaseHas('supplier_payment_reversals', [
            'supplier_payment_id' => 'payment-1',
            'reason' => 'Pembayaran salah input nominal.',
        ]);

        $rows = app(GetSupplierPayableSummaryHandler::class)
            ->handle('2026-04-19', '2026-04-19', '2026-04-19')
            ->data()['rows'];

        $this->assertCount(1, $rows);
        $this->assertSame(50000, $rows[0]['outstanding_rupiah']);
        $this->assertSame(0, $rows[0]['total_paid_rupiah']);
        $this->assertSame(50000, app(\App\Ports\Out\Reporting\CurrentSupplierOutstandingReaderPort::class)->totalOutstandingRupiah());
        $reminders = app(\App\Ports\Out\Procurement\SupplierPayableReminderReaderPort::class)->findDueReminders('2026-09-27');
        $this->assertSame('invoice-1', $reminders[0]->supplierInvoiceId);
        $this->getJson(route('admin.procurement.supplier-invoices.table', [
            'payment_status' => 'outstanding', 'sort_by' => 'due_date', 'sort_dir' => 'asc',
        ]))->assertOk()->assertSee('INV-SUP-001');
    }

    public function test_admin_cannot_reverse_same_supplier_payment_twice(): void
    {
        $this->seedInvoiceWithPayment();

        DB::table('supplier_payment_reversals')->insert([
            'id' => 'payment-reversal-1',
            'supplier_payment_id' => 'payment-1',
            'reason' => 'Sudah pernah direverse.',
            'performed_by_actor_id' => 'admin-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->post(route('admin.procurement.supplier-payments.reverse.store', ['supplierPaymentId' => 'payment-1']), [
                'reason' => 'Coba reverse lagi.',
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHasErrors(['supplier_payment_reversal']);
    }

    public function test_admin_cannot_reverse_supplier_payment_without_reason(): void
    {
        $this->seedInvoiceWithPayment();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->post(route('admin.procurement.supplier-payments.reverse.store', ['supplierPaymentId' => 'payment-1']), [
                'reason' => '   ',
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHasErrors(['reason']);
    }

    private function seedInvoiceWithPayment(): void
    {
        DB::table('suppliers')->insert([
            'id' => 'supplier-1',
            'nama_pt_pengirim' => 'PT Supplier Test',
            'nama_pt_pengirim_normalized' => 'pt supplier test',
        ]);

        DB::table('supplier_invoices')->insert([
            'id' => 'invoice-1',
            'supplier_id' => 'supplier-1',
            'supplier_nama_pt_pengirim_snapshot' => 'PT Supplier Test',
            'nomor_faktur' => 'INV-SUP-001',
            'nomor_faktur_normalized' => 'inv-sup-001',
            'document_kind' => 'invoice',
            'lifecycle_status' => 'active',
            'origin_supplier_invoice_id' => null,
            'superseded_by_supplier_invoice_id' => null,
            'tanggal_pengiriman' => '2026-04-19',
            'jatuh_tempo' => '2026-05-19',
            'grand_total_rupiah' => 50000,
            'voided_at' => null,
            'void_reason' => null,
            'last_revision_no' => 1,
        ]);

        DB::table('supplier_payments')->insert([
            'id' => 'payment-1',
            'supplier_invoice_id' => 'invoice-1',
            'amount_rupiah' => 50000,
            'paid_at' => '2026-04-19',
            'proof_status' => 'pending',
            'proof_storage_path' => null,
        ]);
    }

    private function admin(): User
    {
        $user = User::query()->create([
            'name' => 'Admin Reverse Supplier Payment',
            'email' => 'admin-reverse-supplier-payment-' . uniqid() . '@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $user;
    }
}
