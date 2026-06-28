<?php

declare(strict_types=1);

namespace Tests\Feature\ProductCatalog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ExtremeProductMasterMutationMatrixFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_only_kode_barang(): void
    {
        $this->seedBaseProducts();

        $response = $this->actingAs($this->admin())->put(
            route('admin.products.update', ['productId' => 'product-1']),
            $this->payload(['kode_barang' => 'KB-001-REV'])
        );

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success', 'Product master berhasil diperbarui.');

        $this->assertDatabaseHas('products', [
            'id' => 'product-1',
            'kode_barang' => 'KB-001-REV',
            'nama_barang' => 'Ban Luar',
            'merek' => 'Federal',
            'ukuran' => 90,
            'harga_jual' => 35000,
        ]);
    }

    public function test_admin_can_update_only_ukuran(): void
    {
        $this->seedBaseProducts();

        $response = $this->actingAs($this->admin())->put(
            route('admin.products.update', ['productId' => 'product-1']),
            $this->payload(['ukuran' => 100])
        );

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success', 'Product master berhasil diperbarui.');

        $this->assertDatabaseHas('products', [
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Ban Luar',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 35000,
        ]);
    }

    public function test_admin_can_update_only_merek(): void
    {
        $this->seedBaseProducts();

        $response = $this->actingAs($this->admin())->put(
            route('admin.products.update', ['productId' => 'product-1']),
            $this->payload(['merek' => 'Federal Premium'])
        );

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success', 'Product master berhasil diperbarui.');

        $this->assertDatabaseHas('products', [
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Ban Luar',
            'merek' => 'Federal Premium',
            'ukuran' => 90,
            'harga_jual' => 35000,
        ]);
    }

    public function test_admin_can_update_kode_barang_and_ukuran_together(): void
    {
        $this->seedBaseProducts();

        $response = $this->actingAs($this->admin())->put(
            route('admin.products.update', ['productId' => 'product-1']),
            $this->payload([
                'kode_barang' => 'KB-001-X',
                'ukuran' => 110,
            ])
        );

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success', 'Product master berhasil diperbarui.');

        $this->assertDatabaseHas('products', [
            'id' => 'product-1',
            'kode_barang' => 'KB-001-X',
            'nama_barang' => 'Ban Luar',
            'merek' => 'Federal',
            'ukuran' => 110,
            'harga_jual' => 35000,
        ]);
    }

    public function test_admin_can_update_three_identity_fields_together(): void
    {
        $this->seedBaseProducts();

        $response = $this->actingAs($this->admin())->put(
            route('admin.products.update', ['productId' => 'product-1']),
            $this->payload([
                'kode_barang' => 'KB-001-Z',
                'nama_barang' => 'Ban Luar Tubeless',
                'merek' => 'Federal Max',
            ])
        );

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success', 'Product master berhasil diperbarui.');

        $this->assertDatabaseHas('products', [
            'id' => 'product-1',
            'kode_barang' => 'KB-001-Z',
            'nama_barang' => 'Ban Luar Tubeless',
            'merek' => 'Federal Max',
            'ukuran' => 90,
            'harga_jual' => 35000,
        ]);
    }

    public function test_admin_cannot_update_kode_barang_to_duplicate_active_product(): void
    {
        $this->seedBaseProducts();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.products.edit', ['productId' => 'product-1']))
            ->put(route('admin.products.update', ['productId' => 'product-1']), $this->payload([
                'kode_barang' => 'KB-002',
            ]));

        $response->assertRedirect(route('admin.products.edit', ['productId' => 'product-1']))
            ->assertSessionHasErrors([
                'kode_barang' => 'Kode barang sudah dipakai product lain.',
            ]);

        $this->assertDatabaseHas('products', [
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Ban Luar',
            'merek' => 'Federal',
            'ukuran' => 90,
            'harga_jual' => 35000,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Ban Luar',
            'merek' => 'Federal',
            'ukuran' => 90,
            'harga_jual' => 35000,
            'change_reason' => 'Update product dari matrix mutation test.',
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
            'name' => 'Admin Product Matrix',
            'email' => 'admin-product-matrix@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $u->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $u;
    }
}
