<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateSupplierInvoicePageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_create_supplier_invoice_page(): void
    {
        $this->get(route('admin.procurement.supplier-invoices.create'))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_create_supplier_invoice_page(): void
    {
        $response = $this->actingAs($this->user('kasir'))
            ->get(route('admin.procurement.supplier-invoices.create'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_create_supplier_invoice_page_with_supplier_lookup_contract(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 90, 35000);

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.create'));

        $response->assertOk();
        $response->assertSee('Rincian Nota');
        $response->assertSee('Informasi Nota');
        $response->assertSee('Nomor Faktur');
        $response->assertSee('name="nomor_faktur"', false);
        $response->assertSee('name="lines[0][line_no]"', false);
        $response->assertSee('data-line-no', false);
        $response->assertSee('Ketik minimal 2 huruf untuk mencari produk');
        $response->assertDontSee('add-procurement-line', false);
        $response->assertDontSee('data-product-search', false);
        $response->assertSee('data-product-selected', false);
        $this->assertSame(1, substr_count($response->getContent(), 'data-procurement-product-search'));
        $response->assertDontSee('onclick="window.localStorage', false);
        $response->assertDontSee('data-product-remove', false);
        $response->assertSee('data-remove-line', false);
        $response->assertSee('admin-lookup.css', false);
        $response->assertSee('data-supplier-search', false);
        $response->assertSee('data-supplier-results', false);
        $response->assertSee('admin-procurement-create.js');
        $response->assertSee(json_encode(route('admin.procurement.products.lookup')), false);
        $response->assertSee(json_encode(route('admin.procurement.suppliers.lookup')), false);
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

    private function seedProduct(
        string $id,
        ?string $kodeBarang,
        string $namaBarang,
        string $merek,
        ?int $ukuran,
        int $hargaJual
    ): void {
        DB::table('products')->insert([
            'id' => $id,
            'kode_barang' => $kodeBarang,
            'nama_barang' => $namaBarang,
            'merek' => $merek,
            'ukuran' => $ukuran,
            'harga_jual' => $hargaJual,
        ]);
    }
}
