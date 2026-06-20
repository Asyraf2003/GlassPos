<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Application\Reporting\UseCases\GetOperationalProfitSummaryHandler;
use App\Application\Reporting\UseCases\GetTransactionReportDatasetHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PackageAutoSplitCreateReportImpactFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase2_package_auto_split_store_stock_report_remains_valid_and_external_package_path_is_blocked(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Package Report',
            'email' => 'package-report@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $this->seedStoreStockProducts();

        $this->actingAs($user)->post(route('notes.workspace.store'), $this->storeStockPackagePayload())
            ->assertRedirect(route('cashier.notes.index'));

        $this->actingAs($user)
            ->from(route('cashier.notes.workspace.create'))
            ->post(route('notes.workspace.store'), $this->externalPackagePayload())
            ->assertRedirect(route('cashier.notes.workspace.create'))
            ->assertSessionHasErrors([
                'workspace' => 'Pembelian luar tidak boleh memakai jalur package auto split sebelum kontrak label + total dikunci.',
            ]);

        $transactionResult = app(GetTransactionReportDatasetHandler::class)
            ->handle('2030-01-01', '2030-01-31');

        $this->assertTrue($transactionResult->isSuccess());

        $transactionData = $transactionResult->data();
        $this->assertIsArray($transactionData);

        $this->assertSame(1, $transactionData['summary']['total_rows']);
        $this->assertSame(250000, $transactionData['summary']['gross_transaction_rupiah']);
        $this->assertSame(0, $transactionData['summary']['allocated_payment_rupiah']);
        $this->assertSame(250000, $transactionData['summary']['outstanding_rupiah']);

        $rowsByCustomer = collect($transactionData['rows'])->keyBy('customer_name');

        $this->assertSame(
            250000,
            $rowsByCustomer['Budi Package Store Report']['gross_transaction_rupiah']
        );

        $this->assertArrayNotHasKey('Budi Package External Report', $rowsByCustomer->all());

        $profitResult = app(GetOperationalProfitSummaryHandler::class)
            ->handle('2030-01-01', '2030-01-31');

        $this->assertTrue($profitResult->isSuccess());

        $profitData = $profitResult->data();
        $this->assertIsArray($profitData);

        $row = $profitData['row'];

        $this->assertSame(0, $row['cash_in_rupiah']);
        $this->assertSame(0, $row['refunded_rupiah']);
        $this->assertSame(0, $row['external_purchase_cost_rupiah']);
        $this->assertSame(90000, $row['store_stock_cogs_rupiah']);
        $this->assertSame(90000, $row['product_purchase_cost_rupiah']);
        $this->assertSame(-90000, $row['cash_operational_profit_rupiah']);
    }

    private function seedStoreStockProducts(): void
    {
        DB::table('products')->insert([
            [
                'id' => 'product-package-report-a',
                'kode_barang' => 'KB-PKG-REPORT-001',
                'nama_barang' => 'Oli Report',
                'merek' => 'Federal',
                'ukuran' => null,
                'harga_jual' => 50000,
            ],
            [
                'id' => 'product-package-report-b',
                'kode_barang' => 'KB-PKG-REPORT-002',
                'nama_barang' => 'Busi Report',
                'merek' => 'NGK',
                'ukuran' => null,
                'harga_jual' => 30000,
            ],
        ]);

        DB::table('product_inventory')->insert([
            ['product_id' => 'product-package-report-a', 'qty_on_hand' => 10],
            ['product_id' => 'product-package-report-b', 'qty_on_hand' => 10],
        ]);

        DB::table('product_inventory_costing')->insert([
            [
                'product_id' => 'product-package-report-a',
                'avg_cost_rupiah' => 35000,
                'inventory_value_rupiah' => 350000,
            ],
            [
                'product_id' => 'product-package-report-b',
                'avg_cost_rupiah' => 20000,
                'inventory_value_rupiah' => 200000,
            ],
        ]);
    }

    private function storeStockPackagePayload(): array
    {
        return [
            'idempotency_key' => 'package-auto-split-store-report-idem-001',
            'note' => [
                'customer_name' => 'Budi Package Store Report',
                'customer_phone' => '08123',
                'transaction_date' => '2030-01-15',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 250000,
                'pay_now' => 0,
                'service' => [
                    'name' => 'Servis Package Store Report',
                    'price_rupiah' => 0,
                    'notes' => '',
                ],
                'product_lines' => [
                    [
                        'product_id' => 'product-package-report-a',
                        'qty' => 2,
                        'unit_price_rupiah' => 50000,
                    ],
                    [
                        'product_id' => 'product-package-report-b',
                        'qty' => 1,
                        'unit_price_rupiah' => 30000,
                    ],
                ],
                'external_purchase_lines' => [[
                    'label' => '',
                    'qty' => '',
                    'unit_cost_rupiah' => '',
                ]],
            ]],
            'inline_payment' => [
                'decision' => 'skip',
                'payment_method' => null,
                'paid_at' => '2030-01-15',
            ],
        ];
    }

    private function externalPackagePayload(): array
    {
        return [
            'idempotency_key' => 'package-auto-split-external-report-idem-001',
            'note' => [
                'customer_name' => 'Budi Package External Report',
                'customer_phone' => '08123',
                'transaction_date' => '2030-01-15',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 180000,
                'pay_now' => 0,
                'service' => [
                    'name' => 'Servis Package External Report',
                    'price_rupiah' => 0,
                    'notes' => '',
                ],
                'product_lines' => [[
                    'product_id' => '',
                    'qty' => '',
                    'unit_price_rupiah' => '',
                ]],
                'external_purchase_lines' => [[
                    'label' => '',
                    'qty' => '',
                    'unit_cost_rupiah' => '',
                    'total_rupiah' => 80000,
                ]],
            ]],
            'inline_payment' => [
                'decision' => 'skip',
                'payment_method' => null,
                'paid_at' => '2030-01-15',
            ],
        ];
    }
}
