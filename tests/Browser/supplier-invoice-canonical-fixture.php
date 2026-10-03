<?php

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser;
use App\Application\Procurement\Services\SupplierInvoiceListProjectionService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsReceivedSupplierInvoiceRevisionMatrixFixture;

// Run from repo root. Creates only the dedicated glasspos_adr0047_canonical_browser database.
// Existing fixture is reused; never resets business data.
require getcwd().'/vendor/autoload.php';
$env = Dotenv\Dotenv::parse(file_get_contents(getcwd().'/.env.testing'));
$env['DB_DATABASE'] = 'glasspos_adr0047_canonical_browser';
$env['APP_ENV'] = 'adr0047-canonical-browser';
$env['APP_URL'] = 'http://127.0.0.1:8175';
$env['SESSION_DRIVER'] = 'file';
$env['CACHE_STORE'] = 'array';
$env['ASSET_URL'] = $env['APP_URL'];
$pdo = new PDO('mysql:host='.$env['DB_HOST'].';port='.($env['DB_PORT'] ?? '3306'), $env['DB_USERNAME'], $env['DB_PASSWORD']);
$pdo->exec('CREATE DATABASE IF NOT EXISTS glasspos_adr0047_canonical_browser CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
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
file_put_contents('.env.adr0047-canonical-browser', implode("\n", $lines)."\n");
chmod('.env.adr0047-canonical-browser', 0600);
$fixture = new class
{
    use SeedsReceivedSupplierInvoiceRevisionMatrixFixture;

    public function seed(): void
    {
        $this->seedReceivedInvoiceBase();
        $this->seedPayment();
        $this->seedReplacementProduct();
    }
};
if (DB::table('supplier_invoices')->where('id', 'invoice-1')->exists()) {
    echo "Canonical browser fixture already exists; reuse without resetting history.\n";
    exit(0);
}
$fixture->seed();
$user = EloquentUser::query()->create(['name' => 'Legacy QA', 'email' => 'adr0047@example.test', 'password' => 'browser-test-only']);
DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => 'admin']);
DB::table('admin_transaction_capability_states')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'active' => true]);
$reader = $app->make(\App\Ports\Out\Procurement\SupplierInvoiceReaderPort::class);
$serializer = new class {
    use \App\Adapters\Out\Procurement\Concerns\SupplierInvoiceVersionSnapshotPayloads;
    public function snapshot($invoice): array { return $this->toVersionSnapshot($invoice); }
};
DB::table('supplier_invoice_versions')->insert([
    'id' => 'browser-original-version', 'supplier_invoice_id' => 'invoice-1', 'revision_no' => 1,
    'event_name' => 'supplier_invoice_created', 'changed_at' => now(),
    'snapshot_json' => json_encode($serializer->snapshot($reader->getById('invoice-1')), JSON_THROW_ON_ERROR),
]);
DB::table('products')->where('id', 'product-1')->update(['deleted_at' => now()]);
DB::table('products')->where('id', 'product-2')->update(['nama_barang' => 'Ban Luar Canonical']);
foreach (['product-1' => -2, 'product-2' => 2] as $product => $qty) {
    DB::table('inventory_movements')->insert([
        'id' => 'browser-merge-'.$product, 'product_id' => $product,
        'movement_type' => $qty < 0 ? 'stock_out' : 'stock_in',
        'source_type' => 'product_master_merge', 'source_id' => 'browser-prior-transfer',
        'tanggal_mutasi' => '2026-03-17', 'qty_delta' => $qty,
        'unit_cost_rupiah' => 10000, 'total_cost_rupiah' => $qty * 10000,
    ]);
}
DB::table('product_inventory')->where('product_id', 'product-1')->update(['qty_on_hand' => 0]);
DB::table('product_inventory_costing')->where('product_id', 'product-1')->update(['inventory_value_rupiah' => 0]);
DB::table('product_inventory')->insert(['product_id' => 'product-2', 'qty_on_hand' => 2]);
DB::table('product_inventory_costing')->insert(['product_id' => 'product-2', 'avg_cost_rupiah' => 10000, 'inventory_value_rupiah' => 20000]);
$app->make(\App\Application\ProductCatalog\UseCases\AdoptTransferredProductMergeHandler::class)->handle(
    'browser-explicit-merge', 'product-1', 'product-2', (string) $user->getAuthIdentifier(),
    'Explicit same physical product canonical identity correction', 'browser-prior-transfer',
);
$app->make(SupplierInvoiceListProjectionService::class)->syncInvoice('invoice-1');
echo "Browser fixture ready in glasspos_adr0047_canonical_browser\n";
