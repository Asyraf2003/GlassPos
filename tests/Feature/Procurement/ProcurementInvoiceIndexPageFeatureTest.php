<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProcurementInvoiceIndexPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_procurement_invoice_index_page(): void
    {
        $this->get(route('admin.procurement.supplier-invoices.index'))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_procurement_invoice_index_page(): void
    {
        $response = $this->actingAs($this->user('kasir'))
            ->get(route('admin.procurement.supplier-invoices.index'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_procurement_invoice_index_shell_page(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.index'));

        $response->assertOk();
        $response->assertSee('Tabel nota pemasok interaktif untuk admin');
        $response->assertSee('procurement-search-form', false);
        $response->assertSee('procurement-filter-form', false);
        $response->assertSee('procurement-invoice-table-body', false);
        $response->assertSee('Kirim Bukti Pembayaran');
        $response->assertSee('data-supplier-proof-direct-upload', false);
        $response->assertSee('data-scope-type="supplier_invoice"', false);
        $response->assertSee(route('admin.procurement.supplier-payment-proofs.direct-upload.prepare'), false);
        $response->assertSee('supplier-payment-proof-uploads/__INTENT__/finalize', false);
        $response->assertDontSee('multipart/form-data', false);
        $response->assertSee('image/webp', false);
        $response->assertSee('image/heic', false);
        $response->assertSee('image/heif', false);
        $response->assertSee('.jpg,.jpeg,.png,.webp,.heic,.heif,.pdf,image/jpeg,image/png,image/webp,image/heic,image/heif,application/pdf', false);
        $response->assertSee('Maksimal 3 file. Format: JPG, JPEG, PNG, WEBP, HEIC, HEIF, atau PDF. Maksimal 10 MB per file.');
        $response->assertSee('Kirim Bukti & Tandai Lunas', false);
        $response->assertSee('procurement-payment-submit', false);
        $response->assertSee('data-submitting-label="Mengirim..."', false);
        $response->assertDontSee('Catat Pembayaran Nota');
        $response->assertDontSee('Nominal Pembayaran');
        $response->assertDontSee('Simpan Pembayaran');
        $response->assertSee('admin-procurement-invoices-table.js');
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role.'@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
