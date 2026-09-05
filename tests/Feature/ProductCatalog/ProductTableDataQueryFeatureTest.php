<?php

declare(strict_types=1);

namespace Tests\Feature\ProductCatalog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProductTableDataQueryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_and_filter_product_table(): void
    {
        $this->seedProductRow('product-1', 'KB-001', 'Ban Luar', 'Federal', 90, 35000, 6);
        $this->seedProductRow('product-2', 'KB-002', 'Aki Kering', 'GS Astra', null, 120000, 3);
        $this->seedProductRow('product-3', 'KB-003', 'Ban Dalam', 'Federal', 80, 18000, 5);

        $response = $this->actingAs($this->admin())->get(route('admin.products.table', ['q' => 'Ban', 'merek' => 'Federal']));

        $response->assertOk();
        $response->assertJsonCount(2, 'data.rows');
        $response->assertJsonPath('data.meta.filters.q', 'Ban');
        $response->assertJsonPath('data.meta.filters.merek', 'Federal');
    }

    public function test_search_defaults_to_relevance_order(): void
    {
        $this->seedProductRow('code-exact', 'OL', 'ZZ Code Exact', 'Federal', null, 90000, 1);
        $this->seedProductRow('code-prefix', 'OL-001', 'AA Code Prefix', 'Federal', null, 80000, 1);
        $this->seedProductRow('code-contains', 'X-OL-9', 'BB Code Contains', 'Federal', null, 70000, 1);
        $this->seedProductRow('name-exact', 'ZZ-001', 'Ol', 'Federal', null, 60000, 1);
        $this->seedProductRow('name-prefix', 'ZZ-002', 'Oli Federal', 'Federal', null, 50000, 1);
        $this->seedProductRow('brand-exact', 'ZZ-003', 'CC Brand Exact', 'Ol', null, 40000, 1);
        $this->seedProductRow('brand-prefix', 'ZZ-004', 'DD Brand Prefix', 'Oli Brand', null, 30000, 1);
        $this->seedProductRow('name-contains', 'ZZ-005', 'Filter Oli Mesin', 'Federal', null, 20000, 1);
        $this->seedProductRow('brand-contains', 'ZZ-006', 'EE Brand Contains', 'Federal Oli Group', null, 10000, 1);

        $response = $this->actingAs($this->admin())->get(route('admin.products.table', ['q' => 'ol']));

        $response->assertOk();
        $response->assertJsonPath('data.meta.sort_by', 'relevance');
        self::assertSame(
            [
                'code-exact',
                'code-prefix',
                'code-contains',
                'name-exact',
                'name-prefix',
                'brand-exact',
                'brand-prefix',
                'name-contains',
                'brand-contains',
            ],
            array_column($response->json('data.rows'), 'id'),
        );
    }

    public function test_explicit_sort_overrides_search_relevance(): void
    {
        $this->seedProductRow('expensive', 'OL-EXPENSIVE', 'Oli Mahal', 'Federal', null, 90000, 1);
        $this->seedProductRow('cheap', 'ZZ-CHEAP', 'Filter Oli Murah', 'Federal', null, 10000, 1);

        $response = $this->actingAs($this->admin())->get(route('admin.products.table', [
            'q' => 'ol',
            'sort_by' => 'harga_jual',
            'sort_dir' => 'asc',
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.meta.sort_by', 'harga_jual');
        self::assertSame(['cheap', 'expensive'], array_column($response->json('data.rows'), 'id'));
    }

    public function test_admin_can_sort_product_table_by_stok_saat_ini_desc(): void
    {
        $this->seedProductRow('product-1', 'KB-001', 'Ban Luar', 'Federal', 90, 35000, 2);
        $this->seedProductRow('product-2', 'KB-002', 'Aki Kering', 'GS Astra', null, 120000, 8);

        $response = $this->actingAs($this->admin())->get(route('admin.products.table', ['sort_by' => 'stok_saat_ini', 'sort_dir' => 'desc']));

        $response->assertOk();
        $response->assertJsonPath('data.rows.0.nama_barang', 'Aki Kering');
        $response->assertJsonPath('data.rows.1.nama_barang', 'Ban Luar');
    }

    public function test_admin_can_access_second_page_of_product_table(): void
    {
        for ($i = 1; $i <= 11; $i++) $this->seedProductRow('product-'.$i, 'KB-'.$i, 'Produk '.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'Federal', null, 10000 + $i, 0);

        $response = $this->actingAs($this->admin())->get(route('admin.products.table', ['page' => 2]));

        $response->assertOk();
        $response->assertJsonPath('data.meta.page', 2);
        $response->assertJsonPath('data.meta.last_page', 2);
        $response->assertJsonPath('data.rows.0.nama_barang', 'Produk 11');
    }

    private function admin(): User
    {
        $user = User::query()->create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password123']);
        DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => 'admin']);
        return $user;
    }

    private function seedProductRow(string $id, ?string $kode, string $nama, string $merek, ?int $ukuran, int $harga, int $stok): void
    {
        DB::table('products')->insert(['id' => $id, 'kode_barang' => $kode, 'nama_barang' => $nama, 'merek' => $merek, 'ukuran' => $ukuran, 'harga_jual' => $harga]);
        DB::table('product_inventory')->insert(['product_id' => $id, 'qty_on_hand' => $stok]);
    }
}
