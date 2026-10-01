<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\Reporting\Exports\EmployeeDebtReportPdfViewDataBuilder;
use App\Application\Reporting\UseCases\GetAdminDashboardOverviewHandler;
use App\Application\Reporting\UseCases\GetEmployeeDebtReportDatasetHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

final class EmployeeDebtTemporalIntegrityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_september_carries_july_balance_and_excludes_future_payment(): void
    {
        $this->seedDebt();
        $this->payment('july', 200000, '2026-07-31 23:59:59');
        $this->payment('future', 300000, '2026-10-01 00:00:00');
        $data = $this->dataset();
        self::assertSame(800000, $data['summary']['total_remaining_balance']);
        self::assertSame(800000, $data['summary']['opening_outstanding_rupiah']);
        self::assertSame(0, $data['summary']['new_debt_rupiah']);
        self::assertSame(0, $data['summary']['payments_in_period_rupiah']);
    }

    public function test_september_payment_reconciles_carry_forward_to_closing_exactly(): void
    {
        $this->seedDebt();
        $this->payment('july', 200000, '2026-07-31 23:59:59');
        $this->payment('september', 300000, '2026-09-30 23:59:59');
        $data = $this->dataset();
        self::assertSame(500000, $data['summary']['total_remaining_balance']);
        self::assertSame(0, $data['summary']['new_debt_rupiah']);
        self::assertSame(300000, $data['summary']['payments_in_period_rupiah']);
        self::assertSame(800000, $data['summary']['opening_outstanding_rupiah']);
    }

    public function test_paid_before_period_stays_zero_and_one_rupiah_boundary_is_exact(): void
    {
        $this->seedDebt();
        $this->payment('july', 999999, '2026-07-31 23:59:59');
        $this->payment('boundary', 1, '2026-09-01 00:00:00');
        $data = $this->dataset();
        self::assertSame(0, $data['summary']['total_remaining_balance']);
        self::assertSame(1, $data['summary']['opening_outstanding_rupiah']);
        self::assertSame(1, $data['summary']['payments_in_period_rupiah']);
        self::assertSame(0, $this->dataset('2026-10-01', '2026-10-31')['summary']['opening_outstanding_rupiah']);
    }

    public function test_screen_pdf_and_excel_share_exact_temporal_summary(): void
    {
        $this->seedDebt();
        $this->payment('july', 200000, '2026-07-31 23:59:59');
        $this->payment('september', 300000, '2026-09-30 23:59:59');
        $this->loginAsAuthorizedAdmin();
        $filters = ['period_mode' => 'custom', 'date_from' => '2026-09-01', 'date_to' => '2026-09-30'];
        $data = $this->dataset();
        $screen = $this->get(route('admin.reports.employee_debt.index', $filters));
        $screen->assertOk()->assertViewHas('temporalSummaryRows', $data['temporal_summary_rows']);
        $screen->assertSee('Sisa per 30-09-2026')->assertSee('500.000');
        $pdf = app(EmployeeDebtReportPdfViewDataBuilder::class)->build($data, $filters);
        self::assertSame('Sisa per 30-09-2026', $pdf['summaryItems'][5]['label']);
        self::assertSame('Rp 500.000', $pdf['summaryItems'][5]['value']);
        $screenTables = $screen->viewData('detailTables');
        foreach ($screenTables as $index => $table) {
            self::assertSame(10, $table['rows']->perPage());
            self::assertSame(count($pdf['detailTables'][$index]['rows']), $table['rows']->total());
            $screenTables[$index]['rows'] = $table['rows']->items();
        }
        self::assertSame($pdf['detailTables'], $screenTables);
        self::assertSame('Rp 500.000', $pdf['detailTables'][0]['rows'][0]['remaining_balance']);
        $renderedPdf = view('admin.reporting.employee_debt.export_pdf', $pdf)->render();
        self::assertStringContainsString('Employee A', $renderedPdf);
        self::assertStringNotContainsString('temporal-debt', $renderedPdf);
        $this->get(route('admin.reports.employee_debt.export_pdf', $filters))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $response = $this->get(route('admin.reports.employee_debt.export_excel', $filters))->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'debt-parity-');
        file_put_contents($path, $response->streamedContent());
        try {
            $book = IOFactory::load($path);
            $sheet = $book->getSheetByName('Ringkasan');
            self::assertNotNull($sheet);
            foreach ($data['temporal_summary_rows'] as $index => $metric) {
                $line = $index + 6;
                self::assertSame($metric['label'], $sheet->getCell('A'.$line)->getValue());
                self::assertSame($metric['value'], $sheet->getCell('B'.$line)->getValue());
                self::assertSame('n', $sheet->getCell('B'.$line)->getDataType());
            }
            self::assertSame(500000, $book->getSheetByName('Detail Hutang')->getCell('H2')->getValue());
            self::assertSame('n', $book->getSheetByName('Detail Hutang')->getCell('H2')->getDataType());
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
    }

    public function test_dashboard_current_debt_ignores_selected_month_and_cached_period(): void
    {
        $this->seedDebt();
        $this->payment('july', 200000, '2026-07-31 23:59:59');
        $dashboard = app(GetAdminDashboardOverviewHandler::class);
        foreach (['2026-06', '2026-09'] as $month) {
            self::assertSame(800000, $dashboard->handle($month)['position']['employee_debt_remaining_rupiah']);
        }
        $this->payment('september', 300000, '2026-09-01 00:00:00');
        self::assertSame(500000, $dashboard->handle('2026-09')['position']['employee_debt_remaining_rupiah']);
    }

    private function dataset(string $from = '2026-09-01', string $to = '2026-09-30'): array
    {
        $result = app(GetEmployeeDebtReportDatasetHandler::class)->handle($from, $to);
        self::assertTrue($result->isSuccess());

        return $result->data();
    }

    private function seedDebt(): void
    {
        DB::table('employees')->insert([
            'id' => 'temporal-employee', 'employee_name' => 'Employee A',
            'default_salary_amount' => 1000000, 'salary_basis_type' => 'monthly',
            'employment_status' => 'active', 'created_at' => '2026-07-01 00:00:00', 'updated_at' => now(),
        ]);
        DB::table('employee_debts')->insert([
            'id' => 'temporal-debt', 'employee_id' => 'temporal-employee',
            'total_debt' => 1000000, 'remaining_balance' => 1000000, 'status' => 'unpaid',
            'created_at' => '2026-07-01 00:00:00', 'updated_at' => now(),
        ]);
    }

    private function payment(string $id, int $amount, string $date): void
    {
        DB::table('employee_debt_payments')->insert([
            'id' => $id, 'employee_debt_id' => 'temporal-debt', 'amount' => $amount,
            'payment_date' => $date, 'created_at' => $date, 'updated_at' => $date,
        ]);
        DB::table('employee_debts')->where('id', 'temporal-debt')->decrement('remaining_balance', $amount);
    }
}
