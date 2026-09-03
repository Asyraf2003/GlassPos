<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\SeedsSupplierPaymentProofMatrixFixture;
use Tests\TestCase;

final class ExtremeProcurementAdminGuardAndAttachmentFailureMatrixFeatureTest extends TestCase
{
    use RefreshDatabase, SeedsSupplierPaymentProofMatrixFixture;

    public function test_guest_is_redirected_to_login_when_recording_supplier_payment(): void
    {
        $this->seedPaymentFixture();

        $this->post(route('admin.procurement.supplier-invoices.payments.store', ['supplierInvoiceId' => 'invoice-1']), [
            'payment_date' => '2026-03-20',
            'amount' => 10000,
        ])->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_to_cashier_dashboard_when_recording_supplier_payment(): void
    {
        $this->seedPaymentFixture();

        $response = $this->actingAs($this->userWithRole('kasir'))
            ->post(route('admin.procurement.supplier-invoices.payments.store', ['supplierInvoiceId' => 'invoice-1']), [
                'payment_date' => '2026-03-20',
                'amount' => 10000,
            ]);

        $response->assertRedirect(route('cashier.dashboard'))
            ->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_guest_is_redirected_to_login_when_opening_supplier_payment_proof_attachment(): void
    {
        Storage::fake('r2_private');
        Storage::disk('r2_private')->put('supplier-payment-proofs/payment-1/proof.pdf', 'dummy-pdf');
        $this->seedPaymentFixture('payment-1', 'invoice-1', 'uploaded');
        $this->seedAttachment('attachment-1', 'payment-1', 'supplier-payment-proofs/payment-1/proof.pdf', 'proof.pdf', 'application/pdf');

        $this->get(route('admin.procurement.supplier-payment-proof-attachments.show', ['attachmentId' => 'attachment-1']))
            ->assertRedirect(route('login'));
    }

    public function test_admin_gets_404_when_supplier_payment_proof_attachment_id_is_unknown(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.procurement.supplier-payment-proof-attachments.show', ['attachmentId' => 'missing-attachment']))
            ->assertNotFound();
    }

    public function test_admin_gets_404_when_supplier_payment_proof_file_is_missing_from_storage(): void
    {
        Storage::fake('r2_private');
        $this->seedPaymentFixture('payment-1', 'invoice-1', 'uploaded');
        $this->seedAttachment('attachment-1', 'payment-1', 'supplier-payment-proofs/payment-1/missing.pdf', 'missing.pdf', 'application/pdf');

        $this->actingAs($this->admin())
            ->get(route('admin.procurement.supplier-payment-proof-attachments.show', ['attachmentId' => 'attachment-1']))
            ->assertNotFound();
    }

    private function userWithRole(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Procurement Guard ' . ucfirst($role),
            'email' => $role . '-guard-' . uniqid() . '@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
