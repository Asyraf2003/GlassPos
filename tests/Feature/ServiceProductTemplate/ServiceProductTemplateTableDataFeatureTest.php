<?php

declare(strict_types=1);

namespace Tests\Feature\ServiceProductTemplate;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ServiceProductTemplateTableDataFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_package_identity_with_relevance_and_filter_status(): void
    {
        $admin = $this->user('admin', 'package-table-search@example.test');
        $this->package('package-exact', 'product-exact', 'PKG-OLI', 'Paket Oli', 'service-exact', 'Ganti Oli', true, 150_000);
        $this->package('package-contains', 'product-contains', 'OTHER', 'Bundel PKG-OLI Premium', 'service-contains', 'Servis Mesin', true, 200_000);
        $this->package('package-inactive', 'product-inactive', 'PKG-OLI-X', 'Paket Nonaktif', 'service-inactive', 'Jasa Nonaktif', false, 250_000);

        $response = $this->actingAs($admin)->getJson(route('admin.service-product-templates.table', [
            'q' => 'PKG-OLI',
            'status' => 'active',
            'per_page' => 10,
        ]));

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 2)
            ->assertJsonPath('data.rows.0.id', 'package-exact')
            ->assertJsonPath('data.rows.1.id', 'package-contains');
    }

    public function test_package_total_sort_is_applied_before_pagination(): void
    {
        $admin = $this->user('admin', 'package-table-sort@example.test');

        for ($index = 1; $index <= 11; $index++) {
            $this->package(
                sprintf('package-sort-%02d', $index),
                sprintf('product-sort-%02d', $index),
                sprintf('SORT-%02d', $index),
                sprintf('Produk Sort %02d', $index),
                sprintf('service-sort-%02d', $index),
                sprintf('Jasa Sort %02d', $index),
                true,
                $index * 10_000,
            );
        }

        $response = $this->actingAs($admin)->getJson(route('admin.service-product-templates.table', [
            'q' => 'Produk Sort',
            'sort_by' => 'package_total',
            'sort_dir' => 'desc',
            'page' => 2,
            'per_page' => 10,
        ]));

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 11)
            ->assertJsonPath('data.rows.0.package_total', 10_000);
    }

    public function test_default_order_and_action_urls_are_deterministic(): void
    {
        $admin = $this->user('admin', 'package-table-order@example.test');
        $this->package('package-z', 'product-z', 'Z-1', 'Zeta', 'service-z', 'Jasa Zeta', true, 30_000, 0);
        $this->package('package-a-2', 'product-a-2', 'A-2', 'Alpha', 'service-a-2', 'Jasa Alpha Dua', true, 20_000, 2);
        $this->package('package-a-1', 'product-a-1', 'A-1', 'Alpha', 'service-a-1', 'Jasa Alpha Satu', true, 10_000, 1);

        $response = $this->actingAs($admin)->getJson(route('admin.service-product-templates.table', [
            'q' => 'Alpha',
            'sort_by' => 'product_name',
            'sort_dir' => 'asc',
        ]));

        $response->assertOk();
        $this->assertSame(['package-a-1', 'package-a-2'], collect($response->json('data.rows'))->pluck('id')->all());
        $response->assertJsonPath('data.rows.0.actions.detail_url', route('admin.service-product-templates.show', ['templateId' => 'package-a-1']))
            ->assertJsonPath('data.rows.0.actions.product_url', route('admin.products.show', ['productId' => 'product-a-1']));
    }

    public function test_table_request_rejects_unknown_sort_and_noncanonical_page_size(): void
    {
        $admin = $this->user('admin', 'package-table-validation@example.test');

        $this->actingAs($admin)->getJson(route('admin.service-product-templates.table', [
            'sort_by' => 'unknown',
            'per_page' => 20,
        ]))->assertUnprocessable()->assertJsonValidationErrors(['sort_by', 'per_page']);
    }

    public function test_guest_and_cashier_cannot_access_package_table(): void
    {
        $this->getJson(route('admin.service-product-templates.table'))->assertUnauthorized();
        $cashier = $this->user('kasir', 'package-table-cashier@example.test');
        $this->actingAs($cashier)->getJson(route('admin.service-product-templates.table'))
            ->assertRedirect(route('cashier.dashboard'));
    }

    private function package(
        string $templateId,
        string $productId,
        string $code,
        string $productName,
        string $serviceId,
        string $serviceName,
        bool $active,
        int $packageTotal,
        int $sortOrder = 0,
    ): void {
        DB::table('products')->insert([
            'id' => $productId,
            'kode_barang' => $code,
            'nama_barang' => $productName,
            'nama_barang_normalized' => mb_strtolower($productName),
            'merek' => 'Test',
            'merek_normalized' => 'test',
            'ukuran' => null,
            'harga_jual' => 5_000,
            'deleted_at' => null,
        ]);
        DB::table('service_catalog_items')->insert([
            'id' => $serviceId,
            'name' => $serviceName,
            'normalized_name' => mb_strtolower($serviceName),
            'default_price_rupiah' => 5_000,
            'is_active' => true,
            'created_at' => '2026-01-01 09:00:00',
            'updated_at' => '2026-01-01 09:00:00',
        ]);
        DB::table('service_product_templates')->insert([
            'id' => $templateId,
            'product_id' => $productId,
            'service_catalog_item_id' => $serviceId,
            'default_service_price_rupiah' => 5_000,
            'default_package_total_rupiah' => $packageTotal,
            'is_active' => $active,
            'sort_order' => $sortOrder,
            'created_at' => '2026-01-01 09:00:00',
            'updated_at' => '2026-01-01 09:00:00',
        ]);
    }

    private function user(string $role, string $email): User
    {
        $user = User::query()->create(['name' => 'Package Table User', 'email' => $email, 'password' => 'password123']);
        DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => $role]);

        return $user;
    }
}
