<?php

declare(strict_types=1);

namespace Tests\Feature\ProductCatalog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AdminProductEditReasonFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_product_edit_page_requires_change_reason_field(): void
    {
        $user = $this->createUserWithRole('admin-product-edit-reason@example.test', 'admin');
        $this->seedProduct();

        $response = $this
            ->actingAs($user)
            ->get(route('admin.products.edit', ['productId' => 'product-1']));

        $response->assertOk();
        $response->assertSee('Catatan Perubahan');
        $response->assertSee('name="change_reason"', false);
    }

    public function test_admin_product_update_records_change_reason_to_version_and_audit(): void
    {
        $user = $this->createUserWithRole('admin-product-update-reason@example.test', 'admin');
        $this->seedProduct();

        $response = $this
            ->actingAs($user)
            ->put(route('admin.products.update', ['productId' => 'product-1']), [
                'kode_barang' => 'KB-001-REV',
                'nama_barang' => 'Supra X',
                'merek' => 'Federal',
                'ukuran' => 110,
                'harga_jual' => 18000,
                'reorder_point_qty' => 8,
                'critical_threshold_qty' => 2,
                'change_reason' => 'Koreksi harga jual dan batas stok.',
            ]);

        $response->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('product_versions', [
            'product_id' => 'product-1',
            'change_reason' => 'Koreksi harga jual dan batas stok.',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'bounded_context' => 'product_catalog',
            'aggregate_type' => 'product',
            'aggregate_id' => 'product-1',
            'reason' => 'Koreksi harga jual dan batas stok.',
            'source_channel' => 'web_admin',
        ]);

        $detail = $this
            ->actingAs($user)
            ->get(route('admin.products.show', ['productId' => 'product-1']));

        $detail->assertOk();
        $detail->assertSee('Koreksi harga jual dan batas stok.');
    }

    private function seedProduct(): void
    {
        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Supra',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 15000,
            'reorder_point_qty' => 10,
            'critical_threshold_qty' => 3,
        ]);
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
