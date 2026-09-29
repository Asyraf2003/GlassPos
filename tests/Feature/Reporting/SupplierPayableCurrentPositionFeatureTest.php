<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\Reporting\UseCases\GetAdminDashboardOverviewHandler;
use App\Application\Reporting\UseCases\GetSupplierPayableReportDatasetHandler;
use App\Ports\Out\Procurement\SupplierPayableReminderReaderPort;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\Procurement\CurrentSupplierPayableFixture;
use Tests\TestCase;

final class SupplierPayableCurrentPositionFeatureTest extends TestCase
{
    use CurrentSupplierPayableFixture;
    use RefreshDatabase;

    public function test_august_debt_remains_visible_in_september_and_current_dashboard_ignores_month(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-27'));
        $this->loginAsAuthorizedAdmin();
        $id = $this->oldInvoice();
        $this->seedSupplierPayment('partial', $id, 2000000);
        $this->syncSupplierInvoiceProjectionForTest($id);

        $page = $this->get(route('admin.reports.supplier_payable.index'));
        $page->assertOk()->assertSee('Seluruh Periode')->assertSee('NF-august')->assertSee('Rp 8.000.000');
        $page->assertViewHas('filters', fn ($filters) => $filters['period_mode'] === 'all'
            && $filters['date_from'] === null && $filters['date_to'] === null);
        $page->assertViewHas('rows', fn ($rows) => $rows->first()['due_status'] === 'overdue');
        $page->assertViewHas('summary', fn ($summary) => $summary['outstanding_rupiah'] === 8000000);

        $this->get(route('admin.reports.supplier_payable.index', [
            'period_mode' => 'monthly', 'reference_date' => '2026-09-27',
        ]))->assertOk()->assertSee('NF-august')
            ->assertViewHas('summary', fn ($summary) => $summary['outstanding_rupiah'] === 8000000);

        foreach (['2026-09', '2026-10', '2025-01'] as $month) {
            $payload = app(GetAdminDashboardOverviewHandler::class)->handle($month);
            self::assertSame(8000000, $payload['position']['supplier_outstanding_rupiah']);
            $this->get(route('admin.dashboard', ['month' => $month]))
                ->assertOk()->assertSee('Saldo seluruh faktur aktif saat ini')->assertSee('Rp 8.000.000');
        }

        $all = app(GetSupplierPayableReportDatasetHandler::class)->handle(null, null, '2026-09-27')->data();
        self::assertSame(8000000, $all['period_rows'][0]['outstanding_rupiah']);
        self::assertSame(8000000, $all['supplier_rows'][0]['outstanding_rupiah']);
        self::assertSame($id, app(SupplierPayableReminderReaderPort::class)->findDueReminders('2026-09-27')[0]->supplierInvoiceId);
        $this->seedSupplierPayment('later-payment', $id, 1000000);
        foreach (['2026-09', '2026-10'] as $month) {
            self::assertSame(7000000, app(GetAdminDashboardOverviewHandler::class)->handle($month)['position']['supplier_outstanding_rupiah']);
        }
    }

    public function test_reversed_settled_and_void_balances_are_consistent_across_read_sides(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-27'));
        $this->loginAsAuthorizedAdmin();
        $reversed = $this->oldInvoice('reversed');
        $this->seedSupplierPayment('reversed-payment', $reversed, 10000000);
        $this->reversePayment('reversed-payment');
        $settled = $this->oldInvoice('settled');
        $this->seedSupplierPayment('settled-payment', $settled, 10000000);
        $void = $this->oldInvoice('void');
        DB::table('supplier_invoices')->where('id', $void)->update([
            'voided_at' => '2026-08-21 10:00:00', 'void_reason' => 'test void',
        ]);
        foreach ([$reversed, $settled, $void] as $id) {
            $this->syncSupplierInvoiceProjectionForTest($id);
        }

        $this->get(route('admin.reports.supplier_payable.index'))->assertOk()
            ->assertSee('NF-reversed')->assertSee('NF-settled')->assertDontSee('NF-void')
            ->assertViewHas('summary', fn ($s) => $s['outstanding_rupiah'] === 10000000 && $s['settled_rows'] === 1);
        self::assertSame(10000000, app(GetAdminDashboardOverviewHandler::class)->handle('2026-10')['position']['supplier_outstanding_rupiah']);
        $rows = app(SupplierPayableReminderReaderPort::class)->findDueReminders('2026-09-27');
        self::assertSame([$reversed], array_column($rows, 'supplierInvoiceId'));
        self::assertSame(10000000, $rows[0]->outstandingRupiah);
        $response = $this->getJson(route('admin.procurement.supplier-invoices.table', [
            'payment_status' => 'outstanding', 'sort_by' => 'due_date', 'sort_dir' => 'asc',
        ]));
        $response->assertOk()->assertSee('NF-reversed')->assertDontSee('NF-settled')->assertDontSee('NF-void');
    }

    public function test_read_contract_rejects_half_defined_period(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(GetSupplierPayableReportDatasetHandler::class)->handle(null, '2026-09-30', '2026-09-27');
    }

    public function test_report_paginates_all_period_invoice_rows(): void
    {
        $this->loginAsAuthorizedAdmin();
        for ($i = 0; $i < 16; $i++) {
            $this->oldInvoice(sprintf('row-%02d', $i));
        }
        $this->get(route('admin.reports.supplier_payable.index'))
            ->assertOk()->assertSee('NF-row-00')->assertDontSee('NF-row-15')->assertSee('detail_page=2');
        $this->get(route('admin.reports.supplier_payable.index', ['detail_page' => 2]))
            ->assertOk()->assertSee('NF-row-15')->assertDontSee('NF-row-00');
    }
}
