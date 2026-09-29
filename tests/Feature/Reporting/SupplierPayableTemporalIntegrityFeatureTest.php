<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\Reporting\Exports\SupplierPayableReportPdfViewDataBuilder;
use App\Application\Reporting\UseCases\GetSupplierPayableReportDatasetHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\Procurement\CurrentSupplierPayableFixture;
use Tests\TestCase;

final class SupplierPayableTemporalIntegrityFeatureTest extends TestCase
{
    use CurrentSupplierPayableFixture;
    use RefreshDatabase;

    public function test_empty_period_carries_old_unpaid_invoice(): void
    {
        $this->oldInvoice('carry', 1000001);
        $data = $this->september();
        self::assertSame(1000001, $data['summary']['outstanding_rupiah']);
        self::assertSame(1000001, $data['summary']['opening_outstanding_rupiah']);
        self::assertSame(0, $data['summary']['new_invoices_rupiah']);
        self::assertSame(0, $data['summary']['payments_in_period_rupiah']);
    }

    public function test_period_payments_carry_forward_and_exclude_future_payment(): void
    {
        $invoice = $this->oldInvoice('paid', 1000001);
        foreach ([['prior', 200000, '2026-08-31'], ['first', 100000, '2026-09-01'], ['last', 200000, '2026-09-30'], ['future', 500001, '2026-10-01']] as [$id, $amount, $date]) {
            $this->seedSupplierPayment($id, $invoice, $amount);
            DB::table('supplier_payments')->where('id', $id)->update(['paid_at' => $date]);
        }
        $data = $this->september();
        self::assertSame(800001, $data['summary']['opening_outstanding_rupiah']);
        self::assertSame(300000, $data['summary']['payments_in_period_rupiah']);
        self::assertSame(500001, $data['summary']['outstanding_rupiah']);
        self::assertSame(500001, $data['rows'][0]['outstanding_rupiah']);
    }

    public function test_invoice_settled_before_cutoff_has_zero_outstanding(): void
    {
        $invoice = $this->oldInvoice('settled', 1);
        $this->seedSupplierPayment('settled', $invoice, 1);
        DB::table('supplier_payments')->where('id', 'settled')->update(['paid_at' => '2026-08-31']);
        $data = $this->september();
        self::assertSame(0, $data['summary']['outstanding_rupiah']);
        self::assertSame(0, $data['summary']['open_rows']);
        self::assertCount(1, $data['rows']);
    }

    public function test_reversal_after_cutoff_does_not_restate_earlier_payment(): void
    {
        $invoice = $this->oldInvoice('reversal', 1000001);
        $this->seedSupplierPayment('reverse', $invoice, 500000);
        DB::table('supplier_payments')->where('id', 'reverse')->update(['paid_at' => '2026-08-31']);
        $this->reversePayment('reverse');
        DB::table('supplier_payment_reversals')->where('supplier_payment_id', 'reverse')->update(['created_at' => '2026-10-01 00:00:00']);
        self::assertSame(500001, $this->september()['summary']['outstanding_rupiah']);
        DB::table('supplier_payment_reversals')->where('supplier_payment_id', 'reverse')->update(['created_at' => '2026-09-30 23:59:59']);
        $summary = $this->september()['summary'];
        self::assertSame(500001, $summary['opening_outstanding_rupiah']);
        self::assertSame(500000, $summary['reversals_in_period_rupiah']);
        self::assertSame(1000001, $summary['outstanding_rupiah']);
    }

    public function test_void_obeys_its_recorded_date_and_reconciles_as_adjustment(): void
    {
        foreach (['before' => '2026-08-31 23:59:59', 'inside' => '2026-09-30 23:59:59', 'after' => '2026-10-01 00:00:00'] as $key => $date) {
            $id = $this->oldInvoice($key, 1);
            DB::table('supplier_invoices')->where('id', $id)->update(['voided_at' => $date, 'void_reason' => 'Test']);
        }
        $summary = $this->september()['summary'];
        self::assertSame(2, $summary['opening_outstanding_rupiah']);
        self::assertSame(-1, $summary['adjustments_in_period_rupiah']);
        self::assertSame(1, $summary['outstanding_rupiah']);
    }

    public function test_versioned_adjustments_use_recorded_cutoff_and_preserve_one_rupiah(): void
    {
        $id = $this->oldInvoice('versioned', 120);
        DB::table('supplier_invoices')->where('id', $id)->update(['last_revision_no' => 3]);
        foreach ([[1, '2026-08-10 10:00:00', 100], [2, '2026-09-30 23:59:59', 101], [3, '2026-10-01 00:00:00', 120]] as [$revision, $date, $total]) {
            DB::table('supplier_invoice_versions')->insert([
                'id' => 'version-'.$revision, 'supplier_invoice_id' => $id, 'revision_no' => $revision,
                'event_name' => $revision === 1 ? 'supplier_invoice_created' : 'supplier_invoice_updated',
                'changed_at' => $date, 'snapshot_json' => json_encode([
                    'nomor_faktur' => 'VERSION', 'supplier' => ['id' => 'supplier-versioned', 'nama_pt_pengirim_snapshot' => 'Versioned'],
                    'tanggal_pengiriman' => '2026-08-10', 'jatuh_tempo' => '2026-09-20', 'grand_total_rupiah' => $total,
                ], JSON_THROW_ON_ERROR),
            ]);
        }
        $summary = $this->september()['summary'];
        self::assertSame(100, $summary['opening_outstanding_rupiah']);
        self::assertSame(1, $summary['adjustments_in_period_rupiah']);
        self::assertSame(101, $summary['outstanding_rupiah']);
        self::assertSame(0, $summary['new_invoices_rupiah']);
    }

    public function test_new_invoice_boundaries_use_inclusive_period(): void
    {
        foreach (['first' => '2026-09-01', 'last' => '2026-09-30', 'future' => '2026-10-01'] as $key => $date) {
            $id = $this->oldInvoice($key, 1);
            DB::table('supplier_invoices')->where('id', $id)->update(['tanggal_pengiriman' => $date]);
        }
        $summary = $this->september()['summary'];
        self::assertSame(0, $summary['opening_outstanding_rupiah']);
        self::assertSame(2, $summary['new_invoices_rupiah']);
        self::assertSame(2, $summary['outstanding_rupiah']);
    }

    public function test_screen_pdf_excel_share_carry_forward_detail_and_period_summary_exactly(): void
    {
        $invoice = $this->oldInvoice('parity', 1000001);
        $this->seedSupplierPayment('period', $invoice, 300000);
        DB::table('supplier_payments')->where('id', 'period')->update(['paid_at' => '2026-09-30']);
        $this->loginAsAuthorizedAdmin();
        $filters = ['period_mode' => 'custom', 'date_from' => '2026-09-01', 'date_to' => '2026-09-30', 'reference_date' => '2026-09-15'];
        $data = $this->september();
        $screen = $this->get(route('admin.reports.supplier_payable.index', $filters))->assertOk();
        $screen->assertViewHas('summary', $data['summary'])->assertViewHas('temporalSummaryRows', $data['temporal_summary_rows']);
        $screen->assertSee('NF-parity')->assertSee('700.001')->assertSee('Sisa per 2026-09-30');
        $pdf = app(SupplierPayableReportPdfViewDataBuilder::class)->build($data, $filters);
        self::assertSame('Rp 700.001', $pdf['rows'][0]['outstanding']);
        self::assertStringContainsString('NF-parity', view('admin.reporting.supplier_payable.export_pdf', $pdf)->render());
        $this->get(route('admin.reports.supplier_payable.export_pdf', $filters))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $response = $this->get(route('admin.reports.supplier_payable.export_excel', $filters))->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'supplier-parity-');
        try {
            file_put_contents($path, $response->streamedContent());
            $book = IOFactory::load($path);
            self::assertSame(700001, $book->getSheetByName('Detail Hutang Pemasok')->getCell('I2')->getValue());
            self::assertSame('n', $book->getSheetByName('Detail Hutang Pemasok')->getCell('I2')->getDataType());
            foreach ($data['temporal_summary_rows'] as $index => $metric) {
                self::assertSame($metric['value'], $book->getSheetByName('Ringkasan')->getCell('B'.($index + 22))->getValue());
                self::assertSame('n', $book->getSheetByName('Ringkasan')->getCell('B'.($index + 22))->getDataType());
            }
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
    }

    private function september(): array
    {
        return app(GetSupplierPayableReportDatasetHandler::class)->handle('2026-09-01', '2026-09-30', '2026-09-15')->data();
    }
}
