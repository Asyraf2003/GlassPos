<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

final class ServicePackageProfitBreakdownHttpWorkflowFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_http_create_debt_then_late_payment_and_current_price_change_still_reports_historical_package_profit_in_ui_and_excel(): void
    {
        $today = date('Y-m-d');

        $this->loginAsKasir();

        $this->seedProduct('http-flow-product-a', 'HTTP-FLOW-A', 'Oli HTTP Flow', 50000, 10, 35000);
        $this->seedProduct('http-flow-product-b', 'HTTP-FLOW-B', 'Busi HTTP Flow', 30000, 10, 20000);

        $create = $this->post(route('notes.workspace.store'), [
            'idempotency_key' => 'http-flow-package-report-idem-001',
            'note' => [
                'customer_name' => 'HTTP Workflow Package Customer',
                'customer_phone' => '081234567890',
                'transaction_date' => $today,
            ],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 250000,
                'pay_now' => 0,
                'service' => [
                    'name' => 'Servis Paket HTTP Flow',
                    'price_rupiah' => 0,
                    'notes' => '',
                ],
                'product_lines' => [
                    [
                        'product_id' => 'http-flow-product-a',
                        'qty' => 2,
                        'unit_price_rupiah' => 50000,
                    ],
                    [
                        'product_id' => 'http-flow-product-b',
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
                'paid_at' => $today,
            ],
        ]);

        $create->assertRedirect(route('cashier.notes.index'));

        $noteId = (string) DB::table('notes')
            ->where('customer_name', 'HTTP Workflow Package Customer')
            ->value('id');

        $workItemId = (string) DB::table('work_items')
            ->where('note_id', $noteId)
            ->value('id');

        $lineIds = DB::table('work_item_store_stock_lines')
            ->where('work_item_id', $workItemId)
            ->pluck('id', 'product_id');

        $this->assertNotSame('', $noteId);
        $this->assertNotSame('', $workItemId);
        $this->assertCount(2, $lineIds);

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'total_rupiah' => 250000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => $workItemId,
            'service_name' => 'Servis Paket HTTP Flow',
            'service_price_rupiah' => 24000,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'http-flow-product-a',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => (string) $lineIds['http-flow-product-a'],
            'qty_delta' => -2,
            'unit_cost_rupiah' => 35000,
            'total_cost_rupiah' => -70000,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'http-flow-product-b',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => (string) $lineIds['http-flow-product-b'],
            'qty_delta' => -1,
            'unit_cost_rupiah' => 20000,
            'total_cost_rupiah' => -20000,
        ]);

        // Simulasi admin mengubah harga jual dan avg cost setelah transaksi dibuat.
        // Report laba paket wajib tetap membaca snapshot line + inventory movement historis.
        DB::table('products')
            ->whereIn('id', ['http-flow-product-a', 'http-flow-product-b'])
            ->update(['harga_jual' => 999999]);

        DB::table('product_inventory_costing')
            ->whereIn('product_id', ['http-flow-product-a', 'http-flow-product-b'])
            ->update([
                'avg_cost_rupiah' => 999999,
                'inventory_value_rupiah' => 9999990,
            ]);

        $payment = $this->post(route('cashier.notes.payments.store', ['noteId' => $noteId]), [
            'selected_row_ids' => [
                $workItemId . '::service_store_stock_part::' . (string) $lineIds['http-flow-product-a'],
                $workItemId . '::service_store_stock_part::' . (string) $lineIds['http-flow-product-b'],
                $workItemId . '::service_fee::' . $workItemId,
            ],
            'payment_method' => 'cash',
            'paid_at' => $today,
            'amount_paid' => '250000',
            'amount_received' => '250000',
        ]);

        $payment->assertRedirect(route('cashier.notes.show', ['noteId' => $noteId]));

        $this->assertDatabaseHas('customer_payments', [
            'amount_rupiah' => 250000,
            'payment_method' => 'cash',
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'note_id' => $noteId,
            'work_item_id' => $workItemId,
            'component_type' => 'service_store_stock_part',
            'component_ref_id' => (string) $lineIds['http-flow-product-a'],
            'allocated_amount_rupiah' => 100000,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'note_id' => $noteId,
            'work_item_id' => $workItemId,
            'component_type' => 'service_store_stock_part',
            'component_ref_id' => (string) $lineIds['http-flow-product-b'],
            'allocated_amount_rupiah' => 30000,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'note_id' => $noteId,
            'work_item_id' => $workItemId,
            'component_type' => 'service_fee',
            'component_ref_id' => $workItemId,
            'allocated_amount_rupiah' => 120000,
        ]);

        $this->loginAsAuthorizedAdmin();

        $page = $this->get(route('admin.reports.service_package_profit_breakdown.index', [
            'period_mode' => 'monthly',
            'reference_date' => $today,
        ]));

        $page->assertOk();
        $page->assertSee('HTTP Workflow Package Customer');
        $page->assertSee('Rp 250.000');
        $page->assertSee('Rp 130.000');
        $page->assertSee('Rp 90.000');
        $page->assertSee('Rp 40.000');
        $page->assertSee('Rp 120.000');
        $page->assertSee('Rp 160.000');
        $page->assertDontSee('Rp 999.999');

        $export = $this->get(route('admin.reports.service_package_profit_breakdown.export_excel', [
            'period_mode' => 'monthly',
            'reference_date' => $today,
        ]));

        $export->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'service-package-profit-breakdown-http-flow-');
        file_put_contents($path, $export->streamedContent());

        $spreadsheet = IOFactory::load($path);
        $summary = $spreadsheet->getSheetByName('Ringkasan');
        $detail = $spreadsheet->getSheetByName('Detail Paket');

        $this->assertNotNull($summary);
        $this->assertNotNull($detail);

        $this->assertSame(1, $summary->getCell('B4')->getValue());
        $this->assertSame(250000, $summary->getCell('B5')->getValue());
        $this->assertSame(130000, $summary->getCell('B6')->getValue());
        $this->assertSame(90000, $summary->getCell('B7')->getValue());
        $this->assertSame(40000, $summary->getCell('B8')->getValue());
        $this->assertSame(120000, $summary->getCell('B9')->getValue());
        $this->assertSame(160000, $summary->getCell('B12')->getValue());

        $this->assertSame($noteId, $detail->getCell('B2')->getValue());
        $this->assertSame($workItemId, $detail->getCell('C2')->getValue());
        $this->assertSame('HTTP Workflow Package Customer', $detail->getCell('E2')->getValue());
        $this->assertSame(250000, $detail->getCell('F2')->getValue());
        $this->assertSame(130000, $detail->getCell('G2')->getValue());
        $this->assertSame(90000, $detail->getCell('H2')->getValue());
        $this->assertSame(40000, $detail->getCell('I2')->getValue());
        $this->assertSame(120000, $detail->getCell('J2')->getValue());
        $this->assertSame(160000, $detail->getCell('Q2')->getValue());

        unlink($path);
        $spreadsheet->disconnectWorksheets();
    }


    public function test_http_create_debt_admin_revision_after_product_price_change_then_late_payment_reports_latest_active_package_only(): void
    {
        $today = date('Y-m-d');

        $this->loginAsKasir();

        $this->seedProduct('http-revision-product-a', 'HTTP-REV-A', 'Oli HTTP Revision', 50000, 10, 35000);
        $this->seedProduct('http-revision-product-b', 'HTTP-REV-B', 'Busi HTTP Revision', 30000, 10, 20000);

        $create = $this->post(route('notes.workspace.store'), [
            'idempotency_key' => 'http-revision-package-report-idem-001',
            'note' => [
                'customer_name' => 'HTTP Revision Package Customer',
                'customer_phone' => '081234567891',
                'transaction_date' => $today,
            ],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 250000,
                'pay_now' => 0,
                'service' => [
                    'name' => 'Servis Paket HTTP Revision Original',
                    'price_rupiah' => 0,
                    'notes' => '',
                ],
                'product_lines' => [
                    [
                        'product_id' => 'http-revision-product-a',
                        'qty' => 2,
                        'unit_price_rupiah' => 50000,
                    ],
                    [
                        'product_id' => 'http-revision-product-b',
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
                'paid_at' => $today,
            ],
        ]);

        $create->assertRedirect(route('cashier.notes.index'));

        $noteId = (string) DB::table('notes')
            ->where('customer_name', 'HTTP Revision Package Customer')
            ->value('id');

        $this->assertNotSame('', $noteId);

        // Harga jual produk berubah setelah transaksi awal. Revisi memakai snapshot line lama,
        // bukan harga produk terbaru yang sudah berubah.
        DB::table('products')
            ->whereIn('id', ['http-revision-product-a', 'http-revision-product-b'])
            ->update(['harga_jual' => 999999]);

        $this->loginAsAuthorizedAdmin();

        $revision = $this->patch(route('admin.notes.workspace.update', ['noteId' => $noteId]), [
            'reason' => 'HTTP revision package after product price changed.',
            'note' => [
                'customer_name' => 'HTTP Revision Package Customer Revised',
                'customer_phone' => '081234567891',
                'transaction_date' => $today,
                'operational_note' => 'Revisi paket setelah harga jual produk berubah.',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'description' => null,
                'part_source' => 'store_stock',
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 300000,
                'service' => [
                    'name' => 'Servis Paket HTTP Revision Revised',
                    'price_rupiah' => 0,
                    'notes' => null,
                ],
                'product_lines' => [
                    [
                        'product_id' => 'http-revision-product-a',
                        'qty' => 2,
                        'unit_price_rupiah' => 50000,
                        'price_basis' => 'revision_snapshot',
                    ],
                    [
                        'product_id' => 'http-revision-product-b',
                        'qty' => 1,
                        'unit_price_rupiah' => 30000,
                        'price_basis' => 'revision_snapshot',
                    ],
                ],
                'external_purchase_lines' => [],
            ]],
        ]);

        $revision->assertRedirect(route('admin.notes.show', ['noteId' => $noteId]));

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'customer_name' => 'HTTP Revision Package Customer Revised',
            'total_rupiah' => 300000,
            'latest_revision_number' => 2,
        ]);

        $workItemId = (string) DB::table('work_items')
            ->where('note_id', $noteId)
            ->value('id');

        $lineIds = DB::table('work_item_store_stock_lines')
            ->where('work_item_id', $workItemId)
            ->pluck('id', 'product_id');

        $this->assertNotSame('', $workItemId);
        $this->assertCount(2, $lineIds);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => $workItemId,
            'service_name' => 'Servis Paket HTTP Revision Revised',
            'service_price_rupiah' => 34000,
        ]);

        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'work_item_id' => $workItemId,
            'product_id' => 'http-revision-product-a',
            'qty' => 2,
            'line_total_rupiah' => 100000,
        ]);

        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'work_item_id' => $workItemId,
            'product_id' => 'http-revision-product-b',
            'qty' => 1,
            'line_total_rupiah' => 30000,
        ]);

        // Revisi harus meninggalkan reversal inventory untuk versi lama.
        // Production default source_type reversal workspace update adalah transaction_workspace_updated.
        $this->assertDatabaseHas('inventory_movements', [
            'source_type' => 'transaction_workspace_updated',
            'movement_type' => 'stock_in',
            'tanggal_mutasi' => $today,
        ]);

        $this->loginAsKasir();

        $payment = $this->post(route('cashier.notes.payments.store', ['noteId' => $noteId]), [
            'selected_row_ids' => [
                $workItemId . '::service_store_stock_part::' . (string) $lineIds['http-revision-product-a'],
                $workItemId . '::service_store_stock_part::' . (string) $lineIds['http-revision-product-b'],
                $workItemId . '::service_fee::' . $workItemId,
            ],
            'payment_method' => 'cash',
            'paid_at' => $today,
            'amount_paid' => '300000',
            'amount_received' => '300000',
        ]);

        $payment->assertRedirect(route('cashier.notes.show', ['noteId' => $noteId]));

        $this->assertDatabaseHas('payment_component_allocations', [
            'note_id' => $noteId,
            'work_item_id' => $workItemId,
            'component_type' => 'service_fee',
            'component_ref_id' => $workItemId,
            'allocated_amount_rupiah' => 170000,
        ]);

        $this->loginAsAuthorizedAdmin();

        $page = $this->get(route('admin.reports.service_package_profit_breakdown.index', [
            'period_mode' => 'monthly',
            'reference_date' => $today,
        ]));

        $page->assertOk();
        $page->assertSee('HTTP Revision Package Customer Revised');
        $page->assertSee('Rp 300.000');
        $page->assertSee('Rp 130.000');
        $page->assertSee('Rp 90.000');
        $page->assertSee('Rp 40.000');
        $page->assertSee('Rp 170.000');
        $page->assertSee('Rp 210.000');
        $page->assertDontSee('HTTP Revision Package Customer</td>', false);
        $page->assertDontSee('Rp 250.000');
        $page->assertDontSee('Rp 999.999');

        $export = $this->get(route('admin.reports.service_package_profit_breakdown.export_excel', [
            'period_mode' => 'monthly',
            'reference_date' => $today,
        ]));

        $export->assertOk();

        $xlsx = tempnam(sys_get_temp_dir(), 'service-package-profit-breakdown-http-revision-');
        file_put_contents($xlsx, $export->streamedContent());

        $spreadsheet = IOFactory::load($xlsx);
        $summary = $spreadsheet->getSheetByName('Ringkasan');
        $detail = $spreadsheet->getSheetByName('Detail Paket');

        $this->assertNotNull($summary);
        $this->assertNotNull($detail);

        $this->assertSame(1, $summary->getCell('B4')->getValue());
        $this->assertSame(300000, $summary->getCell('B5')->getValue());
        $this->assertSame(130000, $summary->getCell('B6')->getValue());
        $this->assertSame(90000, $summary->getCell('B7')->getValue());
        $this->assertSame(40000, $summary->getCell('B8')->getValue());
        $this->assertSame(170000, $summary->getCell('B9')->getValue());
        $this->assertSame(210000, $summary->getCell('B12')->getValue());

        $this->assertSame($noteId, $detail->getCell('B2')->getValue());
        $this->assertSame($workItemId, $detail->getCell('C2')->getValue());
        $this->assertSame('HTTP Revision Package Customer Revised', $detail->getCell('E2')->getValue());
        $this->assertSame(300000, $detail->getCell('F2')->getValue());
        $this->assertSame(130000, $detail->getCell('G2')->getValue());
        $this->assertSame(90000, $detail->getCell('H2')->getValue());
        $this->assertSame(40000, $detail->getCell('I2')->getValue());
        $this->assertSame(170000, $detail->getCell('J2')->getValue());
        $this->assertSame(210000, $detail->getCell('Q2')->getValue());
        $this->assertNull($detail->getCell('B3')->getValue());

        unlink($xlsx);
        $spreadsheet->disconnectWorksheets();
    }


    private function seedProduct(
        string $id,
        string $code,
        string $name,
        int $priceRupiah,
        int $qtyOnHand,
        int $avgCostRupiah
    ): void {
        DB::table('products')->insert([
            'id' => $id,
            'kode_barang' => $code,
            'nama_barang' => $name,
            'nama_barang_normalized' => mb_strtolower($name),
            'merek' => 'HTTP Flow',
            'merek_normalized' => 'http flow',
            'ukuran' => null,
            'harga_jual' => $priceRupiah,
            'reorder_point_qty' => 1,
            'critical_threshold_qty' => 1,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => $id,
            'qty_on_hand' => $qtyOnHand,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => $id,
            'avg_cost_rupiah' => $avgCostRupiah,
            'inventory_value_rupiah' => $avgCostRupiah * $qtyOnHand,
        ]);
    }
}
