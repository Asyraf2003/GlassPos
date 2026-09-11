<?php

declare(strict_types=1);

namespace Tests\Feature\ProductCatalog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Application\ProductCatalog\Context\ProductChangeContext;
use App\Application\ProductCatalog\UseCases\SoftDeleteProductHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProductSoftDeleteAuditContextFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_delete_records_context_reason_source_and_actor_to_version_and_audit(): void
    {
        $user = User::query()->create([
            'name' => 'Bulk Product Operator',
            'email' => 'bulk-product-operator@example.test',
            'password' => 'password123',
        ]);

        $actorId = (string) $user->getAuthIdentifier();

        DB::table('actor_accesses')->insert([
            'actor_id' => $actorId,
            'role' => 'admin',
        ]);

        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Data Service Legacy',
            'merek' => 'Legacy',
            'ukuran' => null,
            'harga_jual' => 15000,
            'reorder_point_qty' => null,
            'critical_threshold_qty' => null,
        ]);

        $context = $this->app->make(ProductChangeContext::class);
        $context->set(
            $actorId,
            'admin',
            'cli_bulk_product_update',
            'Data bukan seharusnya berada di produk dan dipindahkan ke service.',
        );

        $result = $this->app
            ->make(SoftDeleteProductHandler::class)
            ->handle('product-1', $actorId);

        $this->assertTrue($result->isSuccess());

        $this->assertDatabaseHas('products', [
            'id' => 'product-1',
            'deleted_by_actor_id' => $actorId,
        ]);

        $this->assertDatabaseHas('product_versions', [
            'product_id' => 'product-1',
            'event_name' => 'product_soft_deleted',
            'changed_by_actor_id' => $actorId,
            'change_reason' => 'Data bukan seharusnya berada di produk dan dipindahkan ke service.',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'bounded_context' => 'product_catalog',
            'aggregate_type' => 'product',
            'aggregate_id' => 'product-1',
            'event_name' => 'product_soft_deleted',
            'actor_id' => $actorId,
            'actor_role' => 'admin',
            'reason' => 'Data bukan seharusnya berada di produk dan dipindahkan ke service.',
            'source_channel' => 'cli_bulk_product_update',
        ]);
    }
}
