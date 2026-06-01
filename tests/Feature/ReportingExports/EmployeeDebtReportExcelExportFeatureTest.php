<?php

declare(strict_types=1);

namespace Tests\Feature\ReportingExports;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

final class EmployeeDebtReportExcelExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_employee_debt_report_as_xlsx_with_numeric_rupiah_cells(): void
    {
        $this->seedEmployee('employee-1', 'Montir A');
        $this->seedEmployee('employee-2', 'Montir B');
        $this->seedEmployee('employee-3', 'Montir C');
        $this->seedEmployee('employee-4', 'Montir D');
        $this->seedEmployee('employee-outside', 'Montir Outside');

        $this->seedDebt('debt-1', 'employee-1', 100000, 60000, 'unpaid', 'Kasbon A', '2030-01-07 08:00:00');
        $this->seedDebt('debt-2', 'employee-2', 50000, 50000, 'unpaid', 'Kasbon B', '2030-01-07 09:00:00');
        $this->seedDebt('debt-3', 'employee-3', 70000, 0, 'paid', 'Kasbon C', '2030-01-10 10:00:00');
        $this->seedDebt('debt-4', 'employee-4', 90000, 90000, 'unpaid', 'Kasbon D', '2030-01-10 11:00:00');
        $this->seedDebt('debt-outside', 'employee-outside', 900000, 900000, 'unpaid', 'Outside', '2030-02-01 10:00:00');

        $this->seedDebtPayment('payment-1', 'debt-1', 40000, '2030-01-08 08:00:00');
        $this->seedDebtPayment('payment-2', 'debt-3', 70000, '2030-01-11 08:00:00');

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.employee_debt.export_excel', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertDownload('laporan-hutang-karyawan-2030-01-01-sampai-2030-01-31.xlsx');

        $path = tempnam(sys_get_temp_dir(), 'employee-debt-report-');
        file_put_contents($path, $response->streamedContent());

        $spreadsheet = IOFactory::load($path);

        $this->assertSame(['Ringkasan', 'Detail Hutang', 'Rekap Per Tanggal', 'Rekap Per Status'], $spreadsheet->getSheetNames());

        $summary = $spreadsheet->getSheetByName('Ringkasan');
        $detail = $spreadsheet->getSheetByName('Detail Hutang');
        $period = $spreadsheet->getSheetByName('Rekap Per Tanggal');
        $status = $spreadsheet->getSheetByName('Rekap Per Status');

        $this->assertNotNull($summary);
        $this->assertNotNull($detail);
        $this->assertNotNull($period);
        $this->assertNotNull($status);

        $this->assertSame('Laporan Hutang Karyawan', $summary->getCell('A1')->getValue());
        $this->assertSame('01 Januari 2030 s/d 31 Januari 2030', $summary->getCell('B2')->getValue());
        $this->assertSame(310000, $summary->getCell('B6')->getValue());
        $this->assertSame(110000, $summary->getCell('B7')->getValue());
        $this->assertSame(200000, $summary->getCell('B8')->getValue());
        $this->assertSame(4, $summary->getCell('B9')->getValue());

        $this->assertSame('Tanggal Catat', $detail->getCell('B1')->getValue());
        $this->assertSame('07 Januari 2030', $detail->getCell('B2')->getValue());
        $this->assertSame('debt-1', $detail->getCell('C2')->getValue());
        $this->assertSame('employee-1', $detail->getCell('D2')->getValue());
        $this->assertSame('unpaid', $detail->getCell('E2')->getValue());
        $this->assertSame(100000, $detail->getCell('F2')->getValue());
        $this->assertSame(40000, $detail->getCell('G2')->getValue());
        $this->assertSame(60000, $detail->getCell('H2')->getValue());
        $this->assertNull($detail->getCell('C6')->getValue());

        $this->assertSame('07 Januari 2030', $period->getCell('A2')->getValue());
        $this->assertSame(2, $period->getCell('B2')->getValue());
        $this->assertSame(150000, $period->getCell('C2')->getValue());
        $this->assertSame(40000, $period->getCell('D2')->getValue());
        $this->assertSame(110000, $period->getCell('E2')->getValue());

        $this->assertSame('paid', $status->getCell('A2')->getValue());
        $this->assertSame(1, $status->getCell('B2')->getValue());
        $this->assertSame(70000, $status->getCell('C2')->getValue());
        $this->assertSame(70000, $status->getCell('D2')->getValue());
        $this->assertSame(0, $status->getCell('E2')->getValue());

        unlink($path);
        $spreadsheet->disconnectWorksheets();
    }

    public function test_kasir_cannot_export_employee_debt_report(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(
            route('admin.reports.employee_debt.export_excel')
        );

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_employee_debt_excel_export_rejects_range_longer_than_366_days(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.employee_debt.export_excel', [
                'period_mode' => 'custom',
                'date_from' => '2030-01-01',
                'date_to' => '2031-01-02',
            ])
        );

        $response->assertStatus(422);
        $response->assertSeeText('Export Excel maksimal 366 hari.');
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-employee-debt-report-export@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }

    private function seedEmployee(string $id, string $name): void
    {
        DB::table('employees')->insert([
            'id' => $id,
            'employee_name' => $name,
            'phone' => null,
            'default_salary_amount' => 5000000,
            'salary_basis_type' => 'monthly',
            'employment_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedDebt(
        string $id,
        string $employeeId,
        int $totalDebt,
        int $remainingBalance,
        string $status,
        ?string $notes,
        string $createdAt,
    ): void {
        DB::table('employee_debts')->insert([
            'id' => $id,
            'employee_id' => $employeeId,
            'total_debt' => $totalDebt,
            'remaining_balance' => $remainingBalance,
            'status' => $status,
            'notes' => $notes,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function seedDebtPayment(
        string $id,
        string $debtId,
        int $amount,
        string $paymentDate,
    ): void {
        DB::table('employee_debt_payments')->insert([
            'id' => $id,
            'employee_debt_id' => $debtId,
            'amount' => $amount,
            'payment_date' => $paymentDate,
            'notes' => null,
            'created_at' => $paymentDate,
            'updated_at' => $paymentDate,
        ]);
    }
}
