<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Ports\Out\Procurement\ProcurementInvoiceDetailReaderPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalProcurementFixture;
use Tests\TestCase;

final class SupplierInvoiceCurrentReceivedQuantityFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProcurementFixture;

    public function test_four_line_received_invoice_revision_projects_80_and_preserves_42_history(): void
    {
        $this->assertReceivedRevision([20, 20, 1, 1], [20, 20, 20, 20]);
    }

    public function test_simple_received_invoice_revision_projects_20_and_preserves_2_history(): void
    {
        $this->assertReceivedRevision([2], [20]);
    }

    public function test_successive_increase_and_decrease_include_deltas_from_superseded_lines(): void
    {
        $id = $this->createInvoice([2]);
        $this->revise($id, [20], 1);
        $this->revise($id, [7], 2);
        $this->assertCurrentQuantity($id, 7);
        $this->assertSame(2, $this->snapshotQuantity($id, 1));
        $this->assertSame(20, $this->snapshotQuantity($id, 2));
        $this->assertSame(7, $this->snapshotQuantity($id, 3));
    }

    public function test_partial_receipt_is_not_inflated_to_full_invoice_quantity(): void
    {
        $id = $this->createInvoice([20], false);
        $this->assertCurrentQuantity($id, 0);
        $lineId = DB::table('supplier_invoice_lines')->where('supplier_invoice_id', $id)->value('id');
        $this->postJson('/procurement/supplier-invoices/'.$id.'/receive', [
            'tanggal_terima' => '2026-03-13',
            'lines' => [['supplier_invoice_line_id' => $lineId, 'qty_diterima' => 2]],
        ])->assertOk();
        $this->assertCurrentQuantity($id, 2);
        $this->revise($id, [25], 1);
        $this->assertCurrentQuantity($id, 7);
    }

    public function test_existing_stale_list_projection_is_repaired_without_rewriting_history_or_payments(): void
    {
        $id = $this->createInvoice([20, 20, 1, 1]);
        $this->revise($id, [20, 20, 20, 20], 1);
        DB::table('supplier_invoice_list_projection')->where('supplier_invoice_id', $id)->update(['total_received_qty' => 42]);
        $before = (array) DB::table('supplier_invoice_list_projection')->where('supplier_invoice_id', $id)->first();
        $versions = DB::table('supplier_invoice_versions')->orderBy('id')->get()->toJson();
        $migration = require database_path('migrations/2026_10_01_000003_refresh_supplier_invoice_received_quantity_projection.php');
        $migration->up();
        $migration->up(); // Idempotent rebuild.
        $this->assertCurrentQuantity($id, 80);
        $after = (array) DB::table('supplier_invoice_list_projection')->where('supplier_invoice_id', $id)->first();
        unset($before['total_received_qty'], $after['total_received_qty']);
        $this->assertSame($before, $after);
        $this->assertSame($versions, DB::table('supplier_invoice_versions')->orderBy('id')->get()->toJson());
    }

    private function assertReceivedRevision(array $initial, array $updated): void
    {
        $id = $this->createInvoice($initial);
        $beforeVersion = DB::table('supplier_invoice_versions')->where('supplier_invoice_id', $id)->first();
        $beforeReceipts = DB::table('supplier_receipt_lines')->orderBy('id')->get()->toJson();
        $beforeAudit = DB::table('audit_event_snapshots')->orderBy('id')->get()->toJson();
        $this->assertCurrentQuantity($id, array_sum($initial));

        $this->revise($id, $updated, 1);
        $this->assertCurrentQuantity($id, array_sum($updated));
        $detail = app(ProcurementInvoiceDetailReaderPort::class)->getById($id);
        $this->assertSame(array_sum($updated), array_sum(array_column($detail['lines'], 'qty_pcs')));
        $this->assertSame(array_sum($initial), $this->snapshotQuantity($id, 1));
        $this->assertSame(array_sum($updated), $this->snapshotQuantity($id, 2));
        $this->assertEquals($beforeVersion, DB::table('supplier_invoice_versions')->where('id', $beforeVersion->id)->first());
        $this->assertSame($beforeReceipts, DB::table('supplier_receipt_lines')->orderBy('id')->get()->toJson());
        $oldAuditIds = json_decode($beforeAudit, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($beforeAudit, DB::table('audit_event_snapshots')->whereIn('id', array_column($oldAuditIds, 'id'))->orderBy('id')->get()->toJson());
        $this->assertSame(array_sum($initial), (int) DB::table('supplier_invoice_lines')->where('supplier_invoice_id', $id)->where('revision_no', 1)->sum('qty_pcs'));
        $this->assertSame(0, DB::table('supplier_invoice_lines')->where('supplier_invoice_id', $id)->where('revision_no', 1)->where('is_current', true)->count());
    }

    private function createInvoice(array $quantities, bool $autoReceive = true): string
    {
        $this->loginAsAuthorizedAdmin();
        foreach ($quantities as $index => $qty) {
            $this->seedMinimalProduct('product-'.$index, 'KB-'.$index, 'Barang '.$index, 'Federal', 100, 15000);
        }
        $response = $this->postJson('/procurement/supplier-invoices/create', [
            'nomor_faktur' => 'INV-CURRENT',
            'nama_pt_pengirim' => 'PT Current',
            'tanggal_pengiriman' => '2026-03-12',
            'tanggal_terima' => '2026-03-13',
            'auto_receive' => $autoReceive,
            'lines' => $this->lines($quantities),
        ])->assertOk();

        return (string) $response->json('data.id');
    }

    private function lines(array $quantities): array
    {
        return array_map(static fn (int $qty, int $index): array => [
            'line_no' => $index + 1,
            'product_id' => 'product-'.$index,
            'qty_pcs' => $qty,
            'line_total_rupiah' => $qty * 10000,
        ], $quantities, array_keys($quantities));
    }

    private function revise(string $id, array $quantities, int $revision): void
    {
        $oldIds = DB::table('supplier_invoice_lines')->where('supplier_invoice_id', $id)->where('is_current', true)->orderBy('line_no')->pluck('id')->all();
        $lines = $this->lines($quantities);
        foreach ($lines as $index => &$line) {
            $line['previous_line_id'] = $oldIds[$index];
        }
        unset($line);
        $this->put(route('admin.procurement.supplier-invoices.update', ['supplierInvoiceId' => $id]), [
            'expected_revision_no' => $revision,
            'change_reason' => 'Koreksi kuantitas diterima',
            'nomor_faktur' => 'INV-CURRENT',
            'nama_pt_pengirim' => 'PT Current',
            'tanggal_pengiriman' => '2026-03-12',
            'lines' => $lines,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
    }

    private function assertCurrentQuantity(string $id, int $expected): void
    {
        $detail = app(ProcurementInvoiceDetailReaderPort::class)->getById($id);
        $this->assertSame($expected, $detail['summary']['total_received_qty']);
        $this->assertSame($expected, (int) DB::table('supplier_invoice_list_projection')->where('supplier_invoice_id', $id)->value('total_received_qty'));
        $this->getJson(route('admin.procurement.supplier-invoices.table'))->assertOk()->assertJsonPath('data.rows.0.total_received_qty', $expected);
        $this->get(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => $id]))
            ->assertOk()->assertViewHas('summaryView', static fn (array $summary): bool => $summary['total_received_qty'] === $expected)
            ->assertSee('<strong>'.$expected.'</strong>', false);
    }

    private function snapshotQuantity(string $id, int $revision): int
    {
        $snapshot = json_decode((string) DB::table('supplier_invoice_versions')->where('supplier_invoice_id', $id)->where('revision_no', $revision)->value('snapshot_json'), true, 512, JSON_THROW_ON_ERROR);
        $detail = app(ProcurementInvoiceDetailReaderPort::class)->getById($id);
        $timelineRows = data_get($detail, 'version_timeline');
        $this->assertIsArray($timelineRows);
        $timeline = array_column($timelineRows, 'snapshot', 'revision_no');
        $this->assertSame($snapshot, $timeline[$revision]);

        return array_sum(array_column($snapshot['lines'], 'qty_pcs'));
    }
}
