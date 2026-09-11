<?php

declare(strict_types=1);

namespace Tests\Feature\ProductCatalog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class BulkMaintainProductsCommandFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_command_dry_runs_then_applies_four_actions_with_audit(): void
    {
        $actorId = $this->seedActor();
        $this->seedProducts();
        $path = $this->manifest();

        try {
            $this->artisan('products:bulk-maintain', ['file' => $path, '--actor' => $actorId])
                ->assertExitCode(0);
            $this->assertDatabaseHas('products', ['id' => 'p-update', 'harga_jual' => 10000]);
            $this->assertDatabaseMissing('audit_events', ['aggregate_id' => 'p-update']);

            $this->artisan('products:bulk-maintain', [
                'file' => $path,
                '--actor' => $actorId,
                '--apply' => true,
            ])->assertExitCode(0);
        } finally {
            @unlink($path);
        }

        $this->assertDatabaseHas('products', ['id' => 'p-update', 'harga_jual' => 12000]);
        $this->assertNotNull(DB::table('products')->where('id', 'p-delete')->value('deleted_at'));
        $this->assertDatabaseHas('products', ['id' => 'p-skip', 'harga_jual' => 30000]);
        $this->assertDatabaseHas('products', ['id' => 'p-same', 'harga_jual' => 40000]);

        $this->assertDatabaseHas('audit_events', [
            'aggregate_id' => 'p-update',
            'reason' => 'Penyesuaian harga jual 11 September 2026.',
            'source_channel' => 'cli_bulk_product_maintenance',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'aggregate_id' => 'p-delete',
            'reason' => 'Data bukan seharusnya berada di produk dan dipindahkan ke service.',
            'source_channel' => 'cli_bulk_product_maintenance',
        ]);
        $this->assertDatabaseMissing('audit_events', ['aggregate_id' => 'p-skip']);
        $this->assertDatabaseMissing('audit_events', ['aggregate_id' => 'p-same']);
    }

    private function seedActor(): string
    {
        $user = User::query()->create([
            'name' => 'Bulk Operator',
            'email' => 'bulk-operator@example.test',
            'password' => 'password123',
        ]);
        $id = (string) $user->getAuthIdentifier();
        DB::table('actor_accesses')->insert(['actor_id' => $id, 'role' => 'admin']);

        return $id;
    }

    private function seedProducts(): void
    {
        foreach ([10000, 20000, 30000, 40000] as $index => $price) {
            $id = ['p-update', 'p-delete', 'p-skip', 'p-same'][$index];
            DB::table('products')->insert([
                'id' => $id,
                'kode_barang' => 'KB-'.$index,
                'nama_barang' => 'Product '.$index,
                'merek' => 'Test',
                'ukuran' => 100 + $index,
                'harga_jual' => $price,
            ]);
        }
    }

    private function manifest(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'glasspos-bulk-');
        $header = 'product_id,kode_barang,nama_barang,merek,ukuran,expected_old_price,new_price,action,reason';
        $rows = [
            'p-update,KB-0,Product 0,Test,100,10000,12000,UPDATE_PRICE,Penyesuaian harga jual 11 September 2026.',
            'p-delete,KB-1,Product 1,Test,101,20000,,DELETE,Data bukan seharusnya berada di produk dan dipindahkan ke service.',
            'p-skip,KB-2,Product 2,Test,102,30000,,SKIP_UNKNOWN_PRICE,Data stang tidak diketahui harga barunya.',
            'p-same,KB-3,Product 3,Test,103,40000,,UNCHANGED,Data ini tidak mengalami perubahan.',
        ];
        file_put_contents($path, $header."\n".implode("\n", $rows)."\n");

        return $path;
    }
}
