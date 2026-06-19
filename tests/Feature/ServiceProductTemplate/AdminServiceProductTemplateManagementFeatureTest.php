<?php

declare(strict_types=1);

namespace Tests\Feature\ServiceProductTemplate;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AdminServiceProductTemplateManagementFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_edit_deactivate_and_reactivate_service_product_template(): void
    {
        $admin = $this->user('admin');
        $this->seedProduct('product-admin-template-1', 'SPT-ADM-001', 'Ban Admin Template', 125000);
        $this->seedService('service-admin-template-1', 'Jasa Pasang Ban Admin', true);
        $this->seedService('service-admin-template-2', 'Jasa Pasang Ban Admin Update', true);

        $createPage = $this->actingAs($admin)->get(route('admin.service-product-templates.create'));
        $createPage->assertOk();
        $createPage->assertSee('Tambah Service', false);
        $createPage->assertSee('Ban Admin Template', false);
        $createPage->assertSee('Jasa Pasang Ban Admin', false);

        $storeResponse = $this->actingAs($admin)->post(route('admin.service-product-templates.store'), [
            'product_id' => 'product-admin-template-1',
            'service_catalog_item_id' => 'service-admin-template-1',
            'default_service_price_rupiah' => 75000,
            'default_package_total_rupiah' => 200000,
            'sort_order' => 3,
        ]);

        $storeResponse->assertRedirect(route('admin.service-product-templates.index'));
        $storeResponse->assertSessionHas('success', 'Service berhasil dibuat.');

        $templateId = (string) DB::table('service_product_templates')
            ->where('product_id', 'product-admin-template-1')
            ->value('id');

        $this->assertDatabaseHas('service_product_templates', [
            'id' => $templateId,
            'product_id' => 'product-admin-template-1',
            'service_catalog_item_id' => 'service-admin-template-1',
            'default_service_price_rupiah' => 75000,
            'default_package_total_rupiah' => 200000,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $indexPage = $this->actingAs($admin)->get(route('admin.service-product-templates.index'));
        $indexPage->assertOk();
        $indexPage->assertSee('Produk memakai harga jual katalog. Harga jasa mengikuti master jasa. Total paket wajib minimal produk + jasa', false);
        $indexPage->assertSee('Ban Admin Template', false);
        $indexPage->assertSee('Jasa Pasang Ban Admin', false);

        $editPage = $this->actingAs($admin)->get(route('admin.service-product-templates.edit', ['templateId' => $templateId]));
        $editPage->assertOk();
        $editPage->assertSee('Edit Service', false);
        $editPage->assertSee('Jasa Pasang Ban Admin Update', false);

        $updateResponse = $this->actingAs($admin)->put(route('admin.service-product-templates.update', ['templateId' => $templateId]), [
            'product_id' => 'product-admin-template-1',
            'service_catalog_item_id' => 'service-admin-template-2',
            'default_service_price_rupiah' => 80000,
            'default_package_total_rupiah' => '',
            'sort_order' => 5,
        ]);

        $updateResponse->assertRedirect(route('admin.service-product-templates.index'));
        $updateResponse->assertSessionHas('success', 'Service berhasil diperbarui.');

        $this->assertDatabaseHas('service_product_templates', [
            'id' => $templateId,
            'service_catalog_item_id' => 'service-admin-template-2',
            'default_service_price_rupiah' => 80000,
            'default_package_total_rupiah' => null,
            'sort_order' => 5,
        ]);

        $deactivateResponse = $this->actingAs($admin)->patch(route('admin.service-product-templates.deactivate', ['templateId' => $templateId]));
        $deactivateResponse->assertRedirect(route('admin.service-product-templates.index'));
        $deactivateResponse->assertSessionHas('success', 'Service dinonaktifkan.');

        $this->assertDatabaseHas('service_product_templates', [
            'id' => $templateId,
            'is_active' => false,
        ]);

        $reactivateResponse = $this->actingAs($admin)->patch(route('admin.service-product-templates.reactivate', ['templateId' => $templateId]));
        $reactivateResponse->assertRedirect(route('admin.service-product-templates.index'));
        $reactivateResponse->assertSessionHas('success', 'Service diaktifkan.');

        $this->assertDatabaseHas('service_product_templates', [
            'id' => $templateId,
            'is_active' => true,
        ]);
    }

    public function test_admin_cannot_create_or_reactivate_second_active_template_for_same_product(): void
    {
        $admin = $this->user('admin');
        $this->seedProduct('product-admin-template-duplicate', 'SPT-ADM-DUP', 'Produk Duplicate Template', 100000);
        $this->seedService('service-admin-template-active', 'Jasa Active Template', true);
        $this->seedService('service-admin-template-inactive-row', 'Jasa Inactive Row Template', true);

        $this->insertTemplate(
            id: 'template-admin-active-existing',
            productId: 'product-admin-template-duplicate',
            serviceId: 'service-admin-template-active',
            isActive: true,
        );

        $createDuplicate = $this->actingAs($admin)->post(route('admin.service-product-templates.store'), [
            'product_id' => 'product-admin-template-duplicate',
            'service_catalog_item_id' => 'service-admin-template-active',
            'default_service_price_rupiah' => 50000,
            'default_package_total_rupiah' => 150000,
            'sort_order' => 1,
        ]);

        $createDuplicate->assertSessionHasErrors('product_id');

        $this->insertTemplate(
            id: 'template-admin-inactive-duplicate',
            productId: 'product-admin-template-duplicate',
            serviceId: 'service-admin-template-inactive-row',
            isActive: false,
        );

        $reactivateDuplicate = $this->actingAs($admin)->from(route('admin.service-product-templates.index'))
            ->patch(route('admin.service-product-templates.reactivate', ['templateId' => 'template-admin-inactive-duplicate']));

        $reactivateDuplicate->assertRedirect(route('admin.service-product-templates.index'));
        $reactivateDuplicate->assertSessionHasErrors('product_id');

        $this->assertDatabaseHas('service_product_templates', [
            'id' => 'template-admin-inactive-duplicate',
            'is_active' => false,
        ]);
    }

    public function test_admin_validation_rejects_missing_product_service_and_non_positive_prices(): void
    {
        $admin = $this->user('admin');

        $response = $this->actingAs($admin)->post(route('admin.service-product-templates.store'), [
            'product_id' => 'missing-product',
            'service_catalog_item_id' => 'missing-service',
            'default_service_price_rupiah' => 0,
            'default_package_total_rupiah' => 0,
            'sort_order' => -1,
        ]);

        $response->assertSessionHasErrors([
            'product_id',
            'service_catalog_item_id',
            'default_service_price_rupiah',
            'default_package_total_rupiah',
            'sort_order',
        ]);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Admin Template User',
            'email' => $role.'-service-product-template@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }

    private function seedProduct(string $id, string $kodeBarang, string $name, int $hargaJual): void
    {
        DB::table('products')->insert([
            'id' => $id,
            'kode_barang' => $kodeBarang,
            'nama_barang' => $name,
            'nama_barang_normalized' => mb_strtolower($name),
            'merek' => 'Federal',
            'merek_normalized' => 'federal',
            'ukuran' => 80,
            'harga_jual' => $hargaJual,
            'deleted_at' => null,
        ]);
    }

    private function seedService(string $id, string $name, bool $isActive): void
    {
        DB::table('service_catalog_items')->insert([
            'id' => $id,
            'name' => $name,
            'normalized_name' => mb_strtolower($name),
            'default_price_rupiah' => 75000,
            'is_active' => $isActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertTemplate(string $id, string $productId, string $serviceId, bool $isActive): void
    {
        DB::table('service_product_templates')->insert([
            'id' => $id,
            'product_id' => $productId,
            'service_catalog_item_id' => $serviceId,
            'default_service_price_rupiah' => 50000,
            'default_package_total_rupiah' => 150000,
            'is_active' => $isActive,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
