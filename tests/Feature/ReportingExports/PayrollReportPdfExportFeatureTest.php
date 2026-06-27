<?php

declare(strict_types=1);

namespace Tests\Feature\ReportingExports;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PayrollReportPdfExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_payroll_report_as_pdf(): void
    {
        $this->seedEmployee('employee-1', 'Montir A');
        $this->seedEmployee('employee-2', 'Montir B');

        $this->seedPayroll('payroll-1', 'employee-1', 50000, '2030-01-06 08:00:00', 'daily', 'Harian A');
        $this->seedPayroll('payroll-2', 'employee-2', 40000, '2030-01-07 09:00:00', 'weekly', 'Mingguan B');
        $this->seedPayroll('payroll-3', 'employee-1', 10000, '2030-01-07 10:00:00', 'daily', 'Harian A2');
        $this->seedPayroll('payroll-outside', 'employee-1', 90000, '2030-02-01 10:00:00', 'daily', 'Outside');

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.payroll.export_pdf', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertDownload('laporan-gaji-2030-01-01-sampai-2030-01-31.pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_kasir_cannot_export_payroll_report_as_pdf(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(
            route('admin.reports.payroll.export_pdf')
        );

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_payroll_pdf_export_rejects_range_longer_than_one_month(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.payroll.export_pdf', [
                'period_mode' => 'custom',
                'date_from' => '2030-01-01',
                'date_to' => '2030-02-01',
            ])
        );

        $response->assertStatus(422);
        $response->assertSeeText('Export PDF maksimal 1 bulan.');
    }

    public function test_payroll_pdf_view_contains_indonesian_report_labels(): void
    {
        $html = view('admin.reporting.payroll.export_pdf', [
            'title' => 'Laporan Gaji',
            'periodLabel' => '01 Januari 2030 s/d 31 Januari 2030',
            'generatedAt' => '31 Januari 2030 10:00',
            'summaryItems' => [
                ['label' => 'Jumlah Pencairan', 'value' => 3],
                ['label' => 'Total Nominal', 'value' => 'Rp 100.000'],
                ['label' => 'Tanggal Terakhir', 'value' => '07 Januari 2030'],
                ['label' => 'Mode Terbesar', 'value' => 'Harian'],
                ['label' => 'Rata-rata Harian', 'value' => 'Rp 3.226'],
            ],
            'periodRows' => [
                ['period_label' => '06 Januari 2030', 'total_rows' => 1, 'total_amount' => 'Rp 50.000'],
            ],
            'modeRows' => [
                ['mode_label' => 'Harian', 'total_rows' => 2, 'total_amount' => 'Rp 60.000'],
            ],
            'rows' => [
                [
                    'date' => '06 Januari 2030',
                    'employee_name' => 'Montir A',
                    'mode_label' => 'Harian',
                    'notes' => 'Harian A',
                    'amount' => 'Rp 50.000',
                ],
            ],
        ])->render();

        $this->assertStringContainsString('Laporan Gaji', $html);
        $this->assertStringContainsString('Jumlah Pencairan', $html);
        $this->assertStringContainsString('Total Nominal', $html);
        $this->assertStringContainsString('Ringkasan Utama', $html);
        $this->assertStringNotContainsString('Catatan Laporan', $html);
        $this->assertStringNotContainsString('Detail lengkap tersedia di Excel', $html);
        $this->assertStringNotContainsString('Rincian Mode', $html);
        $this->assertStringNotContainsString('Montir A', $html);
    }

    public function test_payroll_pdf_view_uses_owner_readable_report_sections_not_detail_tables(): void
    {
        $html = view('admin.reporting.payroll.export_pdf', [
            'title' => 'Laporan Gaji',
            'periodLabel' => '01 Januari 2030 s/d 31 Januari 2030',
            'generatedAt' => '31 Januari 2030 10:00',
            'summaryItems' => [
                ['label' => 'Jumlah Pencairan', 'value' => 3],
                ['label' => 'Total Nominal', 'value' => 'Rp 100.000'],
                ['label' => 'Mode Terbesar', 'value' => 'Harian'],
            ],
            'periodRows' => [],
            'modeRows' => [],
            'rows' => [
                [
                    'date' => '06 Januari 2030',
                    'employee_name' => 'Montir A',
                    'mode_label' => 'Harian',
                    'notes' => 'Harian A',
                    'amount' => 'Rp 50.000',
                ],
            ],
        ])->render();

        $this->assertStringContainsString('Ringkasan Utama', $html);
        $this->assertStringNotContainsString('Catatan Laporan', $html);
        $this->assertStringNotContainsString('Detail lengkap tersedia di Excel', $html);
        $this->assertStringNotContainsString('<table class="summary">', $html);
        $this->assertStringNotContainsString('<table class="detail">', $html);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-payroll-report-pdf-export@example.test',
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
