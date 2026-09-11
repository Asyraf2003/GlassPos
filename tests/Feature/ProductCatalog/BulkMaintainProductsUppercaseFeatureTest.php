<?php

declare(strict_types=1);

namespace Tests\Feature\ProductCatalog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class BulkMaintainProductsUppercaseFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_uppercase_option_normalizes_master_without_changing_skip_price(): void
    {
        $actorId = $this->seedActor();
        $this->seedProduct('p-update', 'kb-up', 'piston mio', 'yamaha', 10000);
        $this->seedProduct('p-skip', 'kb-skip', 'stang mio', 'npp', 20000);
        $this->seedProduct('p-delete', 'bor-mio', 'boring mio', 'bioli', 30000);
        $path = $this->manifest();

        try {
            $this->artisan('products:bulk-maintain', [
                'file' => $path,
                '--actor' => $actorId,
                '--uppercase-master' => true,
                '--apply' => true,
            ])->assertExitCode(0);
        } finally {
            @unlink($path);
        }

        $this->assertDatabaseHas('products', [
            'id' => 'p-update',
            'kode_barang' => 'KB-UP',
            'nama_barang' => 'PISTON MIO',
            'merek' => 'YAMAHA',
            'harga_jual' => 12000,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => 'p-skip',
            'kode_barang' => 'KB-SKIP',
            'nama_barang' => 'STANG MIO',
            'merek' => 'NPP',
            'harga_jual' => 20000,
        ]);
        $deleted = DB::table('products')->where('id', 'p-delete')->first();
        $this->assertSame('BOR-MIO', $deleted->kode_barang);
        $this->assertSame('BORING MIO', $deleted->nama_barang);
        $this->assertSame('BIOLI', $deleted->merek);
        $this->assertNotNull($deleted->deleted_at);
    }

    private function seedActor(): string
    {
        $user = User::query()->create([
            'name' => 'Bulk Uppercase Operator',
            'email' => 'bulk-uppercase@example.test',
            'password' => 'password123',
        ]);
        $id = (string) $user->getAuthIdentifier();
        DB::table('actor_accesses')->insert(['actor_id' => $id, 'role' => 'admin']);

        return $id;
    }

    private function seedProduct(string $id, string $code, string $name, string $brand, int $price): void
    {
        DB::table('products')->insert([
            'id' => $id,
            'kode_barang' => $code,
            'nama_barang' => $name,
            'merek' => $brand,
            'ukuran' => 100,
            'harga_jual' => $price,
        ]);
    }

    private function manifest(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'glasspos-uppercase-');
        $header = 'product_id,kode_barang,nama_barang,merek,ukuran,expected_old_price,new_price,action,reason';
        $rows = [
            'p-update,kb-up,piston mio,yamaha,100,10000,12000,UPDATE_PRICE,Penyesuaian harga.',
            'p-skip,kb-skip,stang mio,npp,100,20000,,SKIP_UNKNOWN_PRICE,Harga belum diketahui.',
            'p-delete,bor-mio,boring mio,bioli,100,30000,,DELETE,Dipindahkan ke service.',
        ];
        file_put_contents($path, $header."\n".implode("\n", $rows)."\n");

        return $path;
    }
}
