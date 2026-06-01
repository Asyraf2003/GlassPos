<?php

declare(strict_types=1);

namespace Tests\Feature\ReportingExports;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

final class PayrollReportExcelExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_payroll_report_as_xlsx_with_numeric_rupiah_cells(): void
    {
        $this->seedEmployee('employee-1', 'Montir A');
        $this->seedEmployee('employee-2', 'Montir B');

        $this->seedPayroll('payroll-1', 'employee-1', 50000, '2030-01-06 08:00:00', 'daily', 'Harian A');
        $this->seedPayroll('payroll-2', 'employee-2', 40000, '2030-01-07 09:00:00', 'weekly', 'Mingguan B');
        $this->seedPayroll('payroll-3', 'employee-1', 10000, '2030-01-07 10:00:00', 'daily', 'Harian A2');
        $this->seedPayroll('payroll-outside', 'employee-1', 90000, '2030-02-01 10:00:00', 'daily', 'Outside');

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.payroll.export_excel', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertDownload('laporan-gaji-2030-01-01-sampai-2030-01-31.xlsx');

        $path = tempnam(sys_get_temp_dir(), 'payroll-report-');
        file_put_contents($path, $response->streamedContent());

        $spreadsheet = IOFactory::load($path);

        $this->assertSame(['Ringkasan', 'Detail Gaji', 'Rekap Per Tanggal', 'Rekap Per Mode'], $spreadsheet->getSheetNames());

        $summary = $spreadsheet->getSheetByName('Ringkasan');
        $detail = $spreadsheet->getSheetByName('Detail Gaji');
        $period = $spreadsheet->getSheetByName('Rekap Per Tanggal');
        $mode = $spreadsheet->getSheetByName('Rekap Per Mode');

        $this->assertNotNull($summary);
        $this->assertNotNull($detail);
        $this->assertNotNull($period);
        $this->assertNotNull($mode);

        $this->assertSame('Laporan Gaji', $summary->getCell('A1')->getValue());
        $this->assertSame('01 Januari 2030 s/d 31 Januari 2030', $summary->getCell('B2')->getValue());
        $this->assertSame(3, $summary->getCell('B6')->getValue());
        $this->assertSame(100000, $summary->getCell('B7')->getValue());
        $this->assertSame('07 Januari 2030', $summary->getCell('B8')->getValue());
        $this->assertSame('Harian', $summary->getCell('B9')->getValue());

        $this->assertSame('Tanggal', $detail->getCell('B1')->getValue());
        $this->assertSame('06 Januari 2030', $detail->getCell('B2')->getValue());
        $this->assertSame('Montir A', $detail->getCell('C2')->getValue());
        $this->assertSame('Harian', $detail->getCell('D2')->getValue());
        $this->assertSame('Harian A', $detail->getCell('E2')->getValue());
        $this->assertSame(50000, $detail->getCell('F2')->getValue());
        $this->assertNull($detail->getCell('C5')->getValue());

        $this->assertSame('06 Januari 2030', $period->getCell('A2')->getValue());
        $this->assertSame(1, $period->getCell('B2')->getValue());
        $this->assertSame(50000, $period->getCell('C2')->getValue());

        $this->assertSame('Harian', $mode->getCell('A2')->getValue());
        $this->assertSame(2, $mode->getCell('B2')->getValue());
        $this->assertSame(60000, $mode->getCell('C2')->getValue());

        unlink($path);
        $spreadsheet->disconnectWorksheets();
    }

    public function test_kasir_cannot_export_payroll_report(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(
            route('admin.reports.payroll.export_excel')
        );

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_payroll_excel_export_rejects_range_longer_than_366_days(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.payroll.export_excel', [
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
            'email' => $role . '-payroll-report-export@example.test',
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

    private function seedPayroll(
        string $id,
        string $employeeId,
        int $amount,
        string $date,
        string $mode,
        ?string $notes
    ): void {
        DB::table('payroll_disbursements')->insert([
            'id' => $id,
            'employee_id' => $employeeId,
            'amount' => $amount,
            'disbursement_date' => $date,
            'mode' => $mode,
            'notes' => $notes,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }
}
