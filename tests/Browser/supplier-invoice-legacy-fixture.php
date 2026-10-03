<?php

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser;
use App\Application\Procurement\Services\SupplierInvoiceListProjectionService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsReceivedSupplierInvoiceRevisionMatrixFixture;

// Run from repo root. Creates only the dedicated glasspos_adr0047_browser database.
// Existing fixture is reused; never resets business data.
require getcwd().'/vendor/autoload.php';
$env = Dotenv\Dotenv::parse(file_get_contents(getcwd().'/.env.testing'));
$env['DB_DATABASE'] = 'glasspos_adr0047_browser';
$env['APP_ENV'] = 'adr0047-browser';
$env['APP_URL'] = 'http://127.0.0.1:8174';
$env['SESSION_DRIVER'] = 'file';
$env['CACHE_STORE'] = 'array';
$env['ASSET_URL'] = $env['APP_URL'];
$pdo = new PDO('mysql:host='.$env['DB_HOST'].';port='.($env['DB_PORT'] ?? '3306'), $env['DB_USERNAME'], $env['DB_PASSWORD']);
$pdo->exec('CREATE DATABASE IF NOT EXISTS glasspos_adr0047_browser CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
foreach ($env as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
Artisan::call('migrate', ['--force' => true]);
$lines = [];
foreach ($env as $key => $value) {
    $lines[] = $key.'="'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
}
file_put_contents('.env.adr0047-browser', implode("\n", $lines)."\n");
chmod('.env.adr0047-browser', 0600);
$fixture = new class
{
    use SeedsReceivedSupplierInvoiceRevisionMatrixFixture;

    public function seed(): void
    {
        $this->seedReceivedInvoiceBase();
        $this->seedPayment();
    }
};
if (DB::table('supplier_invoices')->where('id', 'invoice-1')->exists()) {
    echo "Browser fixture already exists; reuse without resetting history.\n";
    exit(0);
}
$fixture->seed();
$user = EloquentUser::query()->create(['name' => 'Legacy QA', 'email' => 'adr0047@example.test', 'password' => 'browser-test-only']);
DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => 'admin']);
DB::table('admin_transaction_capability_states')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'active' => true]);
DB::table('products')->where('id', 'product-1')->update(['deleted_at' => now(), 'nama_barang' => 'CURRENT MASTER NAME']);
$app->make(SupplierInvoiceListProjectionService::class)->syncInvoice('invoice-1');
echo "Browser fixture ready in glasspos_adr0047_browser\n";
