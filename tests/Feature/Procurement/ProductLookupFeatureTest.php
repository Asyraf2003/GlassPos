<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProductLookupFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_product_lookup(): void
    {
        $this->get(route('admin.procurement.products.lookup', ['q' => 'Ban']))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_product_lookup(): void
    {
        $response = $this->actingAs($this->user('kasir'))
            ->get(route('admin.procurement.products.lookup', ['q' => 'Ban']));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_gets_empty_rows_when_query_is_shorter_than_two_characters(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->getJson(route('admin.procurement.products.lookup', ['q' => 'B']));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(0, 'data.rows');
    }

    public function test_admin_can_lookup_product_and_get_structured_label(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 90, 35000);
        $this->seedProduct('product-2', null, 'Aki Kering', 'GS Astra', null, 120000);

        $response = $this->actingAs($this->user('admin'))
            ->getJson(route('admin.procurement.products.lookup', ['q' => 'Ban']));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.rows.0.id', 'product-1');
        $response->assertJsonPath('data.rows.0.nama_barang', 'Ban Luar');
        $response->assertJsonPath('data.rows.0.merek', 'Federal');
        $response->assertJsonPath('data.rows.0.ukuran', 90);
        $response->assertJsonPath('data.rows.0.kode_barang', 'KB-001');
        $response->assertJsonPath('data.rows.0.label', 'Ban Luar — Federal — 90 (KB-001)');
    }

    public function test_admin_can_lookup_product_by_kode_barang(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 90, 35000);
        $this->seedProduct('product-2', 'AK-002', 'Aki Kering', 'GS Astra', null, 120000);

        $response = $this->actingAs($this->user('admin'))
            ->getJson(route('admin.procurement.products.lookup', ['q' => 'AK-002']));

        $response->assertOk();
        $response->assertJsonPath('data.rows.0.id', 'product-2');
        $response->assertJsonPath('data.rows.0.nama_barang', 'Aki Kering');
    }

    public function test_admin_product_lookup_caps_rows_for_large_catalog(): void
    {
        for ($index = 1; $index <= 25; $index++) {
            $this->seedProduct(
                sprintf('product-%03d', $index),
                sprintf('KB-%03d', $index),
                sprintf('Ban Procurement %03d', $index),
                'Federal',
                90,
                35000,
            );
        }

        $response = $this->actingAs($this->user('admin'))
            ->getJson(route('admin.procurement.products.lookup', ['q' => 'Ban']));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(20, 'data.rows');
        $response->assertJsonPath('data.rows.0.id', 'product-001');
        $response->assertJsonPath('data.rows.19.id', 'product-020');
    }

    public function test_lookup_and_selected_ids_expose_current_stock_and_selling_price_including_zero_stock(): void
    {
        $this->seedProduct('live-product', 'LIVE', 'Live Product', 'NPP', 0, 170000);
        $this->actingAs($this->user('admin'));
        $this->getJson(route('admin.procurement.products.lookup', ['q' => 'LIVE']))
            ->assertOk()->assertJsonPath('data.rows.0.available_stock', 0)
            ->assertJsonPath('data.rows.0.default_unit_price_rupiah', 170000);
        DB::table('product_inventory')->insert(['product_id' => 'live-product', 'qty_on_hand' => 10]);
        DB::table('products')->where('id', 'live-product')->update(['harga_jual' => 180000]);
        $this->getJson(route('admin.procurement.products.lookup', ['ids' => ['live-product']]))
            ->assertOk()->assertJsonPath('data.rows.0.available_stock', 10)
            ->assertJsonPath('data.rows.0.default_unit_price_rupiah', 180000)
            ->assertJsonPath('data.rows.0.ukuran', 0);
        $this->getJson(route('admin.procurement.products.lookup', ['ids' => array_fill(0, 51, 'live-product')]))
            ->assertUnprocessable();
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
