<?php

declare(strict_types=1);

namespace Tests\Feature\ProductCatalog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ExtremeProductMasterValidationMatrixFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_product_with_blank_kode_barang_and_it_is_normalized_to_null(): void
    {
        $this->seedBaseProducts();

        $response = $this->actingAs($this->admin())->put(
            route('admin.products.update', ['productId' => 'product-1']),
            $this->payload(['kode_barang' => '   '])
        );

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success', 'Product master berhasil diperbarui.');

        $this->assertDatabaseHas('products', [
            'id' => 'product-1',
            'kode_barang' => null,
            'nama_barang' => 'Ban Luar',
            'merek' => 'Federal',
            'ukuran' => 90,
            'harga_jual' => 35000,
        ]);
    }

    public function test_admin_cannot_update_product_with_zero_harga_jual(): void
    {
        $this->seedBaseProducts();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.products.edit', ['productId' => 'product-1']))
            ->put(route('admin.products.update', ['productId' => 'product-1']), $this->payload([
                'harga_jual' => 0,
            ]));

        $response->assertRedirect(route('admin.products.edit', ['productId' => 'product-1']))
            ->assertSessionHasErrors(['harga_jual']);

        $this->assertDatabaseHas('products', [
            'id' => 'product-1',
            'harga_jual' => 35000,
        ]);
    }

    public function test_admin_cannot_update_product_with_negative_reorder_point_qty(): void
    {
        $this->seedBaseProducts();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.products.edit', ['productId' => 'product-1']))
            ->put(route('admin.products.update', ['productId' => 'product-1']), $this->payload([
                'reorder_point_qty' => -1,
            ]));

        $response->assertRedirect(route('admin.products.edit', ['productId' => 'product-1']))
            ->assertSessionHasErrors(['reorder_point_qty']);

        $this->assertDatabaseHas('products', [
            'id' => 'product-1',
            'reorder_point_qty' => 2,
        ]);
    }

    public function test_admin_cannot_update_product_with_negative_critical_threshold_qty(): void
    {
        $this->seedBaseProducts();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.products.edit', ['productId' => 'product-1']))
            ->put(route('admin.products.update', ['productId' => 'product-1']), $this->payload([
                'critical_threshold_qty' => -1,
            ]));

        $response->assertRedirect(route('admin.products.edit', ['productId' => 'product-1']))
            ->assertSessionHasErrors(['critical_threshold_qty']);

        $this->assertDatabaseHas('products', [
            'id' => 'product-1',
            'critical_threshold_qty' => 1,
        ]);
    }

    public function test_admin_can_reuse_kode_barang_from_soft_deleted_product(): void
    {
        $this->seedBaseProducts();

        DB::table('products')
            ->where('id', 'product-2')
            ->update([
                'deleted_at' => now()->format('Y-m-d H:i:s'),
                'deleted_by_actor_id' => 'admin-1',
                'delete_reason' => 'Arsip product lama',
            ]);

        $response = $this->actingAs($this->admin())->put(
            route('admin.products.update', ['productId' => 'product-1']),
            $this->payload([
                'kode_barang' => 'KB-002',
            ])
        );

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success', 'Product master berhasil diperbarui.');

        $this->assertDatabaseHas('products', [
            'id' => 'product-1',
            'kode_barang' => 'KB-002',
        ]);
    }

    public function test_admin_is_redirected_to_index_when_updating_missing_product(): void
    {
        $this->seedBaseProducts();

        $response = $this->actingAs($this->admin())->put(
            route('admin.products.update', ['productId' => 'missing-product']),
            $this->payload(['kode_barang' => 'KB-999'])
        );

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('error', 'Product tidak ditemukan.');
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Ban Luar',
            'merek' => 'Federal',
            'ukuran' => 90,
            'harga_jual' => 35000,
            'change_reason' => 'Update product dari matrix validation test.',
            'reorder_point_qty' => 2,
            'critical_threshold_qty' => 1,
        ], $overrides);
    }

    private function seedBaseProducts(): void
    {
        DB::table('products')->insert([
            [
                'id' => 'product-1',
                'kode_barang' => 'KB-001',
                'nama_barang' => 'Ban Luar',
                'nama_barang_normalized' => 'ban luar',
                'merek' => 'Federal',
                'merek_normalized' => 'federal',
                'ukuran' => 90,
                'harga_jual' => 35000,
                'reorder_point_qty' => 2,
                'critical_threshold_qty' => 1,
                'deleted_at' => null,
                'deleted_by_actor_id' => null,
                'delete_reason' => null,
            ],
            [
                'id' => 'product-2',
                'kode_barang' => 'KB-002',
                'nama_barang' => 'Ban Dalam',
                'nama_barang_normalized' => 'ban dalam',
                'merek' => 'Federal',
                'merek_normalized' => 'federal',
                'ukuran' => 90,
                'harga_jual' => 25000,
                'reorder_point_qty' => 3,
                'critical_threshold_qty' => 1,
                'deleted_at' => null,
                'deleted_by_actor_id' => null,
                'delete_reason' => null,
            ],
        ]);
    }

    private function admin(): User
    {
        $u = User::query()->create([
            'name' => 'Admin Product Validation Matrix',
            'email' => 'admin-product-validation-matrix@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $u->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $u;
    }
}
