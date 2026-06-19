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
        $response->assertSee('proof_files[]', false);
        $response->assertSee('Kirim Bukti &amp; Tandai Lunas', false);
        $response->assertDontSee('Catat Pembayaran Nota');
        $response->assertDontSee('Nominal Pembayaran');
        $response->assertDontSee('Simpan Pembayaran');
        $response->assertSee('admin-procurement-invoices-table.js');
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
}
