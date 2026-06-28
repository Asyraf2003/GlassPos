<?php

declare(strict_types=1);

namespace Tests\Feature\ProductCatalog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProductEditPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_edit_product_page(): void
    {
        $response = $this->get(route('admin.products.edit', ['productId' => 'product-1']));

        $response->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_edit_product_page(): void
    {
        $user = $this->createUserWithRole('kasir-product-edit@example.test', 'kasir');

        $response = $this
            ->actingAs($user)
            ->get(route('admin.products.edit', ['productId' => 'product-1']));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_is_redirected_to_index_when_product_for_edit_page_is_missing(): void
    {
        $user = $this->createUserWithRole('admin-product-missing-edit@example.test', 'admin');

        $response = $this
            ->actingAs($user)
            ->get(route('admin.products.edit', ['productId' => 'missing-product']));

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('error', 'Product tidak ditemukan.');
    }

    public function test_admin_can_access_edit_product_page(): void
    {
        $user = $this->createUserWithRole('admin-product-edit@example.test', 'admin');

        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Supra',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 15000,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('admin.products.edit', ['productId' => 'product-1']));

        $response->assertOk();
        $response->assertSee('Edit Produk');
        $response->assertSee('Supra');
        $response->assertSee('Federal');
        $response->assertSee('15000');
    }

    public function test_admin_can_update_product_from_edit_page(): void
    {
        $user = $this->createUserWithRole('admin-product-update@example.test', 'admin');

        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Supra',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 15000,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('admin.products.update', ['productId' => 'product-1']), [
                'kode_barang' => 'KB-001-REV',
                'nama_barang' => 'Supra X',
                'merek' => 'Federal',
                'ukuran' => 110,
                'harga_jual' => 18000,
                'change_reason' => 'Update product dari halaman edit.',
            ]);

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success', 'Product master berhasil diperbarui.');

        $this->assertDatabaseHas('products', [
            'id' => 'product-1',
            'kode_barang' => 'KB-001-REV',
            'nama_barang' => 'Supra X',
            'merek' => 'Federal',
            'ukuran' => 110,
            'harga_jual' => 18000,
        ]);
    }

    public function test_admin_update_product_returns_back_with_error_when_duplicate_conflict_happens(): void
    {
        $user = $this->createUserWithRole('admin-product-update-duplicate@example.test', 'admin');

        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => null,
            'nama_barang' => 'Supra',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 15000,
        ]);

        DB::table('products')->insert([
            'id' => 'product-2',
            'kode_barang' => null,
            'nama_barang' => 'Vario',
            'merek' => 'Federal',
            'ukuran' => 120,
            'harga_jual' => 16000,
        ]);

        $response = $this
            ->from(route('admin.products.edit', ['productId' => 'product-2']))
            ->actingAs($user)
            ->put(route('admin.products.update', ['productId' => 'product-2']), [
                'kode_barang' => null,
                'nama_barang' => 'Supra',
                'merek' => 'Federal',
                'ukuran' => 100,
                'harga_jual' => 17000,
                'change_reason' => 'Uji validasi duplikasi product.',
            ]);

        $response->assertRedirect(route('admin.products.edit', ['productId' => 'product-2']));
        $response->assertSessionHasErrors([
            'product' => 'Product dengan kombinasi data ini sudah ada.',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => 'product-2',
            'nama_barang' => 'Vario',
            'merek' => 'Federal',
            'ukuran' => 120,
            'harga_jual' => 16000,
        ]);
    }

    public function test_admin_update_product_redirects_to_index_when_product_is_missing(): void
    {
        $user = $this->createUserWithRole('admin-product-update-missing@example.test', 'admin');

        $response = $this
            ->actingAs($user)
            ->put(route('admin.products.update', ['productId' => 'missing-product']), [
                'kode_barang' => 'KB-404',
                'nama_barang' => 'Revo',
                'merek' => 'Federal',
                'ukuran' => 90,
                'harga_jual' => 14000,
                'change_reason' => 'Uji update product yang tidak ditemukan.',
            ]);

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('error', 'Product tidak ditemukan.');
    }

    private function createUserWithRole(string $email, string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
