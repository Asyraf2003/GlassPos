<?php

declare(strict_types=1);

namespace Tests\Feature\ServiceCatalog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AdminServiceCatalogManagementFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_edit_deactivate_and_activate_service_catalog_item(): void
    {
        $admin = $this->user('admin');

        $createPage = $this->actingAs($admin)->get(route('admin.services.create'));
        $createPage->assertOk();
        $createPage->assertSee('Tambah Master Jasa', false);

        $storeResponse = $this->actingAs($admin)->post(route('admin.services.store'), [
            'name' => 'Ganti Oli Mesin',
            'default_price_rupiah' => 75000,
        ]);

        $storeResponse->assertRedirect(route('admin.services.index'));
        $storeResponse->assertSessionHas('success', 'Master jasa berhasil dibuat.');

        $serviceId = (string) DB::table('service_catalog_items')
            ->where('normalized_name', 'ganti oli mesin')
            ->value('id');

        $this->assertDatabaseHas('service_catalog_items', [
            'id' => $serviceId,
            'name' => 'Ganti Oli Mesin',
            'normalized_name' => 'ganti oli mesin',
            'default_price_rupiah' => 75000,
            'is_active' => true,
        ]);

        $indexPage = $this->actingAs($admin)->get(route('admin.services.index'));
        $indexPage->assertOk();
        $indexPage->assertSee('Master jasa bengkel', false);
        $indexPage->assertSee('Ganti Oli Mesin', false);
        $indexPage->assertSee('Master Jasa', false);
        $indexPage->assertSee(route('admin.services.index'), false);

        $editPage = $this->actingAs($admin)->get(route('admin.services.edit', ['serviceId' => $serviceId]));
        $editPage->assertOk();
        $editPage->assertSee('Edit Master Jasa', false);

        $updateResponse = $this->actingAs($admin)->put(route('admin.services.update', ['serviceId' => $serviceId]), [
            'name' => 'Ganti Oli Mesin Matic',
            'default_price_rupiah' => 85000,
        ]);

        $updateResponse->assertRedirect(route('admin.services.index'));
        $updateResponse->assertSessionHas('success', 'Master jasa berhasil diperbarui.');

        $this->assertDatabaseHas('service_catalog_items', [
            'id' => $serviceId,
            'name' => 'Ganti Oli Mesin Matic',
            'normalized_name' => 'ganti oli mesin matic',
            'default_price_rupiah' => 85000,
        ]);

        $deactivateResponse = $this->actingAs($admin)->patch(route('admin.services.deactivate', ['serviceId' => $serviceId]));
        $deactivateResponse->assertRedirect(route('admin.services.index'));
        $deactivateResponse->assertSessionHas('success', 'Master jasa dinonaktifkan.');

        $this->assertDatabaseHas('service_catalog_items', [
            'id' => $serviceId,
            'is_active' => false,
        ]);

        $activateResponse = $this->actingAs($admin)->patch(route('admin.services.activate', ['serviceId' => $serviceId]));
        $activateResponse->assertRedirect(route('admin.services.index'));
        $activateResponse->assertSessionHas('success', 'Master jasa diaktifkan.');

        $this->assertDatabaseHas('service_catalog_items', [
            'id' => $serviceId,
            'is_active' => true,
        ]);
    }

    public function test_admin_cannot_create_duplicate_service_by_normalized_name(): void
    {
        $admin = $this->user('admin');

        DB::table('service_catalog_items')->insert([
            'id' => 'service-existing-duplicate',
            'name' => 'Ganti Oli',
            'normalized_name' => 'ganti oli',
            'default_price_rupiah' => 50000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.services.store'), [
            'name' => 'ganti---oli',
            'default_price_rupiah' => 60000,
        ]);

        $response->assertSessionHasErrors('name');

        $this->assertSame(1, DB::table('service_catalog_items')->where('normalized_name', 'ganti oli')->count());
    }

    public function test_admin_validation_rejects_missing_name_and_non_positive_default_price(): void
    {
        $admin = $this->user('admin');

        $response = $this->actingAs($admin)->post(route('admin.services.store'), [
            'name' => '',
            'default_price_rupiah' => 0,
        ]);

        $response->assertSessionHasErrors([
            'name',
            'default_price_rupiah',
        ]);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Admin Service User',
            'email' => $role . '-service-catalog-admin@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
