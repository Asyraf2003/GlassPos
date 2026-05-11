<?php

declare(strict_types=1);

namespace Tests\Feature\MobileApi\Product;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class MobileApiProductSearchFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_search_requires_mobile_api_token(): void
    {
        $response = $this->getJson('/api/v1/products/search?q=ban');

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => 'Autentikasi diperlukan.',
            'errors' => [
                'token' => ['UNAUTHENTICATED'],
            ],
        ]);
    }

    public function test_admin_mobile_token_cannot_search_products_in_v1(): void
    {
        $token = $this->loginMobileToken(
            email: 'mobile-admin-product-search@example.test',
            role: 'admin',
        );

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/products/search?q=ban');

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => 'Akses produk mobile hanya untuk kasir.',
            'errors' => [
                'role' => ['CASHIER_ONLY'],
            ],
        ]);
    }

    public function test_cashier_product_search_returns_empty_rows_for_short_query(): void
    {
        $token = $this->loginMobileToken(
            email: 'mobile-kasir-short-query@example.test',
            role: 'kasir',
        );

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/products/search?q=b');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'rows' => [],
            ],
            'meta' => [
                'query' => 'b',
                'limit' => 20,
            ],
            'errors' => null,
        ]);
    }

    public function test_cashier_product_search_returns_stock_price_and_split_fields_including_zero_stock(): void
    {
        $token = $this->loginMobileToken(
            email: 'mobile-kasir-product-search@example.test',
            role: 'kasir',
        );

        $this->seedProduct(
            id: 'product-ban-luar',
            kodeBarang: 'KB-001',
            namaBarang: 'Ban Luar',
            merek: 'Federal',
            ukuran: 80,
            hargaJual: 15000,
            qtyOnHand: 7,
        );

        $this->seedProduct(
            id: 'product-ban-dalam-zero',
            kodeBarang: 'KB-002',
            namaBarang: 'Ban Dalam',
            merek: 'Federal',
            ukuran: 80,
            hargaJual: 12000,
            qtyOnHand: 0,
        );

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/products/search?q=ban');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'rows' => [
                    [
                        'id' => 'product-ban-dalam-zero',
                        'label' => 'Ban Dalam — Federal — 80 (KB-002)',
                        'kode_barang' => 'KB-002',
                        'nama_barang' => 'Ban Dalam',
                        'merek' => 'Federal',
                        'ukuran' => 80,
                        'available_stock' => 0,
                        'default_unit_price_rupiah' => 12000,
                        'minimum_unit_price_rupiah' => 12000,
                    ],
                    [
                        'id' => 'product-ban-luar',
                        'label' => 'Ban Luar — Federal — 80 (KB-001)',
                        'kode_barang' => 'KB-001',
                        'nama_barang' => 'Ban Luar',
                        'merek' => 'Federal',
                        'ukuran' => 80,
                        'available_stock' => 7,
                        'default_unit_price_rupiah' => 15000,
                        'minimum_unit_price_rupiah' => 15000,
                    ],
                ],
            ],
            'meta' => [
                'query' => 'ban',
                'limit' => 20,
            ],
            'errors' => null,
        ]);
    }

    private function loginMobileToken(string $email, string $role): string
    {
        $this->createUserWithRole($email, $role);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password123',
            'device_name' => 'Redmi 12',
        ]);

        $response->assertOk();

        return (string) $response->json('data.token');
    }

    private function createUserWithRole(string $email, string $role): User
    {
        $user = User::query()->create([
            'name' => 'Mobile Product User',
            'email' => $email,
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
        string $kodeBarang,
        string $namaBarang,
        string $merek,
        int $ukuran,
        int $hargaJual,
        int $qtyOnHand,
    ): void {
        DB::table('products')->insert([
            'id' => $id,
            'kode_barang' => $kodeBarang,
            'nama_barang' => $namaBarang,
            'nama_barang_normalized' => mb_strtolower($namaBarang),
            'merek' => $merek,
            'merek_normalized' => mb_strtolower($merek),
            'ukuran' => $ukuran,
            'harga_jual' => $hargaJual,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => $id,
            'qty_on_hand' => $qtyOnHand,
        ]);
    }
}
