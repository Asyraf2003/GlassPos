<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\UseCases\CreateNoteRevisionHandler;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class TransactionCashLedgerAfterRevisionRefundFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_transaction_cash_ledger_page_excel_and_pdf_include_refund_after_active_revision(): void
    {
        $user = $this->loginAsAuthorizedAdmin();

        $this->seedClosedPaidServiceOnlyNote();
        $this->seedProduct('product-ledger-revision-refund-001', 50000, 30000, 5);

        $revision = $this->app->make(CreateNoteRevisionHandler::class)->handle(
            'note-ledger-revision-refund-001',
            $this->revisionPayload(),
            'admin-report-proof-001',
            false,
        );

        self::assertTrue($revision->isSuccess(), $revision->message());

        $currentWorkItemId = (string) DB::table('work_items')
            ->where('note_id', 'note-ledger-revision-refund-001')
            ->where('id', '<>', 'wi-ledger-revision-refund-old-001')
            ->value('id');

        self::assertNotSame('', $currentWorkItemId);

        $this->actingAs($user)
            ->from(route('admin.notes.show', ['noteId' => 'note-ledger-revision-refund-001']))
            ->post(route('admin.notes.refunds.store', ['noteId' => 'note-ledger-revision-refund-001']), [
                'selected_row_ids' => [$currentWorkItemId],
                'refunded_at' => '2026-05-22',
                'reason' => 'Report export proof after active revision refund.',
            ])
            ->assertRedirect(route('admin.notes.index'))
            ->assertSessionHas('success');

        $refundId = (string) DB::table('customer_refunds')
            ->where('note_id', 'note-ledger-revision-refund-001')
            ->value('id');

        self::assertNotSame('', $refundId);

        $pageResponse = $this->actingAs($user)->get(
            route('admin.reports.transaction_cash_ledger.index', [
                'period_mode' => 'custom',
                'date_from' => '2026-05-20',
                'date_to' => '2026-05-22',
            ])
        );

        $pageResponse->assertOk();
        $pageResponse->assertSee('Ringkasan Utama');
        $pageResponse->assertSee('Rincian Ringkas');
        $pageResponse->assertDontSee('Detail lengkap tersedia di Excel');
        $pageResponse->assertSee('Rp 100.000');
        $pageResponse->assertDontSee('note-ledger-revision-refund-001');
        $pageResponse->assertDontSee('payment_allocations');
        $pageResponse->assertDontSee('customer_refunds');
        $pageResponse->assertDontSee('payment-ledger-revision-refund-001');
        $pageResponse->assertDontSee($refundId);

        $excelResponse = $this->actingAs($user)->get(
            route('admin.reports.transaction_cash_ledger.export_excel', [
                'period_mode' => 'custom',
                'date_from' => '2026-05-20',
                'date_to' => '2026-05-22',
            ])
        );

        $excelResponse->assertOk();
        $excelResponse->assertDownload(
            'laporan-buku-kas-transaksi-2026-05-20-sampai-2026-05-22.xlsx'
        );

        $path = tempnam(sys_get_temp_dir(), 'ledger-after-revision-refund-');
        file_put_contents($path, $excelResponse->streamedContent());

        $spreadsheet = IOFactory::load($path);
        $detail = $spreadsheet->getSheetByName('Detail Event Kas');

        self::assertNotNull($detail);

        $this->assertExcelDetailContainsEvent(
            $detail,
            'note-ledger-revision-refund-001',
            'Alokasi Pembayaran',
            'Masuk',
            100000,
            'Pembayaran Nota',
            'payment-ledger-revision-refund-001',
        );

        $this->assertExcelDetailContainsEvent(
            $detail,
            'note-ledger-revision-refund-001',
            'Pengembalian Dana',
            'Keluar',
            100000,
            'Pengembalian Dana',
            $refundId,
        );

        unlink($path);
        $spreadsheet->disconnectWorksheets();

        $pdfResponse = $this->actingAs($user)->get(
            route('admin.reports.transaction_cash_ledger.export_pdf', [
                'period_mode' => 'custom',
                'date_from' => '2026-05-20',
                'date_to' => '2026-05-22',
            ])
        );

        $pdfResponse->assertOk();
        $pdfResponse->assertHeader('Content-Type', 'application/pdf');
        $pdfResponse->assertDownload(
            'laporan-buku-kas-transaksi-2026-05-20-sampai-2026-05-22.pdf'
        );

        $this->assertStringStartsWith('%PDF', $pdfResponse->getContent());
    }

    private function assertExcelDetailContainsEvent(
        object $detail,
        string $noteId,
        string $eventType,
        string $direction,
        int $amountRupiah,
        string $sourceTable,
        string $sourceId
    ): void {
        for ($row = 2; $row <= $detail->getHighestDataRow(); $row++) {
            if (
                $detail->getCell('C' . $row)->getValue() === $noteId
                && $detail->getCell('E' . $row)->getValue() === $eventType
                && $detail->getCell('F' . $row)->getValue() === $direction
                && $detail->getCell('H' . $row)->getValue() === $amountRupiah
                && $detail->getCell('K' . $row)->getValue() === $sourceTable
                && $detail->getCell('L' . $row)->getValue() === $sourceId
            ) {
                $this->addToAssertionCount(1);

                return;
            }
        }

        self::fail(sprintf(
            'Excel detail event was not found: %s / %s / %s / %s.',
            $noteId,
            $eventType,
            $sourceTable,
            $sourceId,
        ));
    }

    private function seedClosedPaidServiceOnlyNote(): void
    {
        $this->seedNoteBase(
            'note-ledger-revision-refund-001',
            'Budi Ledger Revision Original',
            '2026-05-20',
            100000,
            'closed',
        );

        $this->seedWorkItemBase(
            'wi-ledger-revision-refund-old-001',
            'note-ledger-revision-refund-001',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            WorkItem::STATUS_OPEN,
            100000,
        );

        $this->seedServiceDetailBase(
            'wi-ledger-revision-refund-old-001',
            'Servis Ledger Revision Original',
            100000,
            ServiceDetail::PART_SOURCE_NONE,
        );

        $this->seedServiceOnlyCurrentRevision(
            'note-ledger-revision-refund-001',
            'note-ledger-revision-refund-001-r001',
            'wi-ledger-revision-refund-old-001',
            'Budi Ledger Revision Original',
            '2026-05-20',
            100000,
            'Servis Ledger Revision Original',
            100000,
        );

        $this->seedCustomerPaymentBase(
            'payment-ledger-revision-refund-001',
            100000,
            '2026-05-20',
        );

        $this->seedPaymentAllocationBase(
            'payment-allocation-ledger-revision-refund-001',
            'payment-ledger-revision-refund-001',
            'note-ledger-revision-refund-001',
            100000,
        );

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-ledger-revision-refund-old-001',
            'customer_payment_id' => 'payment-ledger-revision-refund-001',
            'note_id' => 'note-ledger-revision-refund-001',
            'work_item_id' => 'wi-ledger-revision-refund-old-001',
            'component_type' => PaymentComponentType::SERVICE_FEE,
            'component_ref_id' => 'wi-ledger-revision-refund-old-001',
            'component_amount_rupiah_snapshot' => 100000,
            'allocated_amount_rupiah' => 100000,
            'allocation_priority' => 1,
        ]);
    }

    /** @return array<string, mixed> */
    private function revisionPayload(): array
    {
        return [
            'reason' => 'Report export proof after active revision refund.',
            'note' => [
                'customer_name' => 'Budi Ledger Revision Revised',
                'customer_phone' => '08123456789',
                'transaction_date' => '2026-05-21',
            ],
            'items' => [
                [
                    'entry_mode' => 'product',
                    'description' => 'Produk Ledger Revision Revised',
                    'part_source' => 'none',
                    'service' => null,
                    'product_lines' => [[
                        'product_id' => 'product-ledger-revision-refund-001',
                        'qty' => 2,
                        'unit_price_rupiah' => 50000,
                    ]],
                    'external_purchase_lines' => [],
                ],
            ],
        ];
    }

    private function seedProduct(string $id, int $priceRupiah, int $avgCostRupiah, int $qtyOnHand): void
    {
        DB::table('products')->insert([
            'id' => $id,
            'kode_barang' => strtoupper($id),
            'nama_barang' => 'Produk Ledger Revision Refund',
            'merek' => 'Ledger',
            'ukuran' => null,
            'harga_jual' => $priceRupiah,
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
