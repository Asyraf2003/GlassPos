<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Application\Note\UseCases\CreateNoteRevisionHandler;
use App\Application\Reporting\UseCases\GetTransactionReportDatasetHandler;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class PackageAutoSplitRevisionReportImpactFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_transaction_report_reads_current_total_after_package_multi_product_downward_revision(): void
    {
        $this->seedPackageMultiProductNoteWithFullPayment();

        $result = $this->app->make(CreateNoteRevisionHandler::class)->handle(
            'note-report-package-revision-001',
            [
                'reason' => 'Report impact package multi-product downward revision.',
                'note' => [
                    'customer_name' => 'Budi Package Revision Report',
                    'customer_phone' => '08123456789',
                    'transaction_date' => '2030-01-16',
                    'operational_note' => 'Alasan report revision package multi.',
                ],
                'items' => [
                    [
                        'entry_mode' => 'service',
                        'description' => null,
                        'part_source' => 'store_stock',
                        'pricing_mode' => 'package_auto_split',
                        'package_total_rupiah' => 200000,
                        'service' => [
                            'name' => 'Servis Package Revision Report Revised',
                            'price_rupiah' => 0,
                            'notes' => null,
                        ],
                        'product_lines' => [
                            [
                                'product_id' => 'product-report-package-a',
                                'qty' => 2,
                                'unit_price_rupiah' => 50000,
                                'price_basis' => 'revision_snapshot',
                            ],
                            [
                                'product_id' => 'product-report-package-b',
                                'qty' => 1,
                                'unit_price_rupiah' => 30000,
                                'price_basis' => 'revision_snapshot',
                            ],
                        ],
                        'external_purchase_lines' => [],
                    ],
                ],
            ],
            'admin-report-package-revision-001',
            false,
        );

        self::assertTrue($result->isSuccess(), $result->message());

        $report = app(GetTransactionReportDatasetHandler::class)
            ->handle('2030-01-01', '2030-01-31');

        self::assertTrue($report->isSuccess());

        $data = $report->data();
        self::assertIsArray($data);

        self::assertSame(1, $data['summary']['total_rows']);
        self::assertSame(200000, $data['summary']['gross_transaction_rupiah']);
        self::assertSame(250000, $data['summary']['allocated_payment_rupiah']);
        self::assertSame(0, $data['summary']['outstanding_rupiah']);

        $rowsByCustomer = collect($data['rows'])->keyBy('customer_name');

        self::assertArrayHasKey('Budi Package Revision Report', $rowsByCustomer->all());
        self::assertSame(
            200000,
            $rowsByCustomer['Budi Package Revision Report']['gross_transaction_rupiah'],
        );
        self::assertSame(
            250000,
            $rowsByCustomer['Budi Package Revision Report']['allocated_payment_rupiah'],
        );
        self::assertSame(
            0,
            $rowsByCustomer['Budi Package Revision Report']['outstanding_rupiah'],
        );

        $this->assertDatabaseHas('note_revision_settlements', [
            'note_revision_id' => 'note-report-package-revision-001-r002',
            'gross_total_rupiah' => 200000,
            'carry_forward_paid_rupiah' => 250000,
            'surplus_rupiah' => 50000,
            'settlement_status' => 'overpaid_pending',
        ]);
    }

    public function test_transaction_report_exports_after_package_multi_product_downward_revision_return_files(): void
    {
        $this->seedPackageMultiProductNoteWithFullPayment();

        $result = $this->app->make(CreateNoteRevisionHandler::class)->handle(
            'note-report-package-revision-001',
            [
                'reason' => 'Report export package multi-product downward revision.',
                'note' => [
                    'customer_name' => 'Budi Package Revision Report',
                    'customer_phone' => '08123456789',
                    'transaction_date' => '2030-01-16',
                    'operational_note' => 'Alasan export revision package multi.',
                ],
                'items' => [
                    [
                        'entry_mode' => 'service',
                        'description' => null,
                        'part_source' => 'store_stock',
                        'pricing_mode' => 'package_auto_split',
                        'package_total_rupiah' => 200000,
                        'service' => [
                            'name' => 'Servis Package Revision Report Revised',
                            'price_rupiah' => 0,
                            'notes' => null,
                        ],
                        'product_lines' => [
                            [
                                'product_id' => 'product-report-package-a',
                                'qty' => 2,
                                'unit_price_rupiah' => 50000,
                                'price_basis' => 'revision_snapshot',
                            ],
                            [
                                'product_id' => 'product-report-package-b',
                                'qty' => 1,
                                'unit_price_rupiah' => 30000,
                                'price_basis' => 'revision_snapshot',
                            ],
                        ],
                        'external_purchase_lines' => [],
                    ],
                ],
            ],
            'admin-report-package-revision-001',
            false,
        );

        self::assertTrue($result->isSuccess(), $result->message());

        $admin = User::query()->create([
            'name' => 'Admin Package Revision Export',
            'email' => 'admin-package-revision-export@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $admin->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        $query = [
            'period_mode' => 'custom',
            'date_from' => '2030-01-01',
            'date_to' => '2030-01-31',
        ];

        $excelResponse = $this->actingAs($admin)->get(
            route('admin.reports.transaction_summary.export_excel', $query),
        );

        $excelResponse->assertOk();
        self::assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $excelResponse->headers->get('Content-Type'),
        );
        self::assertStringContainsString(
            'laporan-transaksi-2030-01-01-sampai-2030-01-31.xlsx',
            (string) $excelResponse->headers->get('Content-Disposition'),
        );

        $pdfResponse = $this->actingAs($admin)->get(
            route('admin.reports.transaction_summary.export_pdf', $query),
        );

        $pdfResponse->assertOk();
        self::assertStringContainsString(
            'application/pdf',
            (string) $pdfResponse->headers->get('Content-Type'),
        );
        self::assertStringContainsString(
            'laporan-transaksi-2030-01-01-sampai-2030-01-31.pdf',
            (string) $pdfResponse->headers->get('Content-Disposition'),
        );
        self::assertStringStartsWith('%PDF', (string) $pdfResponse->getContent());

        $cashLedgerQuery = [
            'period_mode' => 'custom',
            'date_from' => '2030-01-01',
            'date_to' => '2030-01-31',
        ];

        $cashLedgerExcelResponse = $this->actingAs($admin)->get(
            route('admin.reports.transaction_cash_ledger.export_excel', $cashLedgerQuery),
        );

        $cashLedgerExcelResponse->assertOk();
        self::assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $cashLedgerExcelResponse->headers->get('Content-Type'),
        );
        self::assertStringContainsString(
            'laporan-buku-kas-transaksi-2030-01-01-sampai-2030-01-31.xlsx',
            (string) $cashLedgerExcelResponse->headers->get('Content-Disposition'),
        );

        $cashLedgerPdfResponse = $this->actingAs($admin)->get(
            route('admin.reports.transaction_cash_ledger.export_pdf', $cashLedgerQuery),
        );

        $cashLedgerPdfResponse->assertOk();
        self::assertStringContainsString(
            'application/pdf',
            (string) $cashLedgerPdfResponse->headers->get('Content-Type'),
        );
        self::assertStringContainsString(
            'laporan-buku-kas-transaksi-2030-01-01-sampai-2030-01-31.pdf',
            (string) $cashLedgerPdfResponse->headers->get('Content-Disposition'),
        );
        self::assertStringStartsWith('%PDF', (string) $cashLedgerPdfResponse->getContent());
    }

    private function seedPackageMultiProductNoteWithFullPayment(): void
    {
        $this->seedNoteBase(
            'note-report-package-revision-001',
            'Budi Package Revision Report',
            '2030-01-15',
            250000,
            'open',
        );

        $this->seedNotePaymentProduct(
            'product-report-package-a',
            'PKG-REPORT-A',
            'Oli Report Package A',
            'Federal',
            100,
            50000,
        );

        $this->seedNotePaymentProduct(
            'product-report-package-b',
            'PKG-REPORT-B',
            'Busi Report Package B',
            'NGK',
            100,
            30000,
        );

        DB::table('product_inventory')->insert([
            ['product_id' => 'product-report-package-a', 'qty_on_hand' => 10],
            ['product_id' => 'product-report-package-b', 'qty_on_hand' => 10],
        ]);

        DB::table('product_inventory_costing')->insert([
            [
                'product_id' => 'product-report-package-a',
                'avg_cost_rupiah' => 35000,
                'inventory_value_rupiah' => 350000,
            ],
            [
                'product_id' => 'product-report-package-b',
                'avg_cost_rupiah' => 20000,
                'inventory_value_rupiah' => 200000,
            ],
        ]);

        $this->seedWorkItemBase(
            'wi-report-package-revision-001',
            'note-report-package-revision-001',
            1,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            WorkItem::STATUS_OPEN,
            250000,
        );

        $this->seedServiceDetailBase(
            'wi-report-package-revision-001',
            'Servis Package Revision Report Original',
            120000,
            'none',
        );

        $this->seedStoreStockLineBase(
            'ssl-report-package-revision-a',
            'wi-report-package-revision-001',
            'product-report-package-a',
            2,
            100000,
        );

        $this->seedStoreStockLineBase(
            'ssl-report-package-revision-b',
            'wi-report-package-revision-001',
            'product-report-package-b',
            1,
            30000,
        );

        $this->seedServiceWithStoreStockCurrentRevision(
            'note-report-package-revision-001',
            'note-report-package-revision-001-r001',
            'wi-report-package-revision-001',
            'Budi Package Revision Report',
            '2030-01-15',
            250000,
            'Servis Package Revision Report Original',
            120000,
            'ssl-report-package-revision-a',
            'product-report-package-a',
            2,
            100000,
        );

        $payload = DB::table('note_revision_lines')
            ->where('note_revision_id', 'note-report-package-revision-001-r001')
            ->where('work_item_root_id', 'wi-report-package-revision-001')
            ->value('payload');

        $decoded = json_decode((string) $payload, true, 512, JSON_THROW_ON_ERROR);
        $decoded['pricing_mode'] = 'package_auto_split';
        $decoded['package_total_rupiah'] = 250000;
        $decoded['parts_total_rupiah'] = 130000;
        $decoded['service_price_rupiah'] = 120000;
        $decoded['store_stock_lines'] = [
            [
                'id' => 'ssl-report-package-revision-a',
                'work_item_id' => 'wi-report-package-revision-001',
                'product_id' => 'product-report-package-a',
                'qty' => 2,
                'line_total_rupiah' => 100000,
                'selling_price_rupiah' => 50000,
                'product_name_snapshot' => 'Oli Report Package A',
            ],
            [
                'id' => 'ssl-report-package-revision-b',
                'work_item_id' => 'wi-report-package-revision-001',
                'product_id' => 'product-report-package-b',
                'qty' => 1,
                'line_total_rupiah' => 30000,
                'selling_price_rupiah' => 30000,
                'product_name_snapshot' => 'Busi Report Package B',
            ],
        ];

        DB::table('note_revision_lines')
            ->where('note_revision_id', 'note-report-package-revision-001-r001')
            ->where('work_item_root_id', 'wi-report-package-revision-001')
            ->update(['payload' => json_encode($decoded, JSON_THROW_ON_ERROR)]);

        $this->seedCustomerPaymentBase(
            'payment-report-package-revision-001',
            250000,
            '2030-01-15',
        );

        $this->seedPaymentAllocationBase(
            'payment-allocation-report-package-revision-001',
            'payment-report-package-revision-001',
            'note-report-package-revision-001',
            250000,
        );

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pca-report-package-revision-old-a',
                'customer_payment_id' => 'payment-report-package-revision-001',
                'note_id' => 'note-report-package-revision-001',
                'work_item_id' => 'wi-report-package-revision-001',
                'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
                'component_ref_id' => 'ssl-report-package-revision-a',
                'component_amount_rupiah_snapshot' => 100000,
                'allocated_amount_rupiah' => 100000,
                'allocation_priority' => 1,
            ],
            [
                'id' => 'pca-report-package-revision-old-b',
                'customer_payment_id' => 'payment-report-package-revision-001',
                'note_id' => 'note-report-package-revision-001',
                'work_item_id' => 'wi-report-package-revision-001',
                'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
                'component_ref_id' => 'ssl-report-package-revision-b',
                'component_amount_rupiah_snapshot' => 30000,
                'allocated_amount_rupiah' => 30000,
                'allocation_priority' => 2,
            ],
            [
                'id' => 'pca-report-package-revision-old-service',
                'customer_payment_id' => 'payment-report-package-revision-001',
                'note_id' => 'note-report-package-revision-001',
                'work_item_id' => 'wi-report-package-revision-001',
                'component_type' => PaymentComponentType::SERVICE_FEE,
                'component_ref_id' => 'wi-report-package-revision-001',
                'component_amount_rupiah_snapshot' => 120000,
                'allocated_amount_rupiah' => 120000,
                'allocation_priority' => 3,
            ],
        ]);
    }
}
