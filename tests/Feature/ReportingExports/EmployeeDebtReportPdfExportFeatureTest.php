<?php

declare(strict_types=1);

namespace Tests\Feature\ReportingExports;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class EmployeeDebtReportPdfExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_employee_debt_report_as_pdf(): void
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
            route('admin.reports.employee_debt.export_pdf', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertDownload('laporan-hutang-karyawan-2030-01-01-sampai-2030-01-31.pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_kasir_cannot_export_employee_debt_report_as_pdf(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(
            route('admin.reports.employee_debt.export_pdf')
        );

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_employee_debt_pdf_export_rejects_range_longer_than_one_month(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.employee_debt.export_pdf', [
                'period_mode' => 'custom',
                'date_from' => '2030-01-01',
                'date_to' => '2030-02-01',
            ])
        );

        $response->assertStatus(422);
        $response->assertSeeText('Export PDF maksimal 1 bulan.');
    }

    public function test_employee_debt_pdf_view_contains_indonesian_report_labels(): void
    {
        $html = view('admin.reporting.employee_debt.export_pdf', [
            'title' => 'Laporan Hutang Karyawan',
            'periodLabel' => '01 Januari 2030 s/d 31 Januari 2030',
            'generatedAt' => '31 Januari 2030 10:00',
            'summaryItems' => [
                ['label' => 'Total Hutang', 'value' => 'Rp 310.000'],
                ['label' => 'Sudah Dibayar', 'value' => 'Rp 110.000'],
                ['label' => 'Sisa Hutang', 'value' => 'Rp 200.000'],
                ['label' => 'Jumlah Data', 'value' => 4],
                ['label' => 'Status Lunas', 'value' => 1],
                ['label' => 'Status Belum Lunas', 'value' => 3],
            ],
            'periodRows' => [
                [
                    'period_label' => '07 Januari 2030',
                    'total_rows' => 2,
                    'total_debt' => 'Rp 150.000',
                    'total_paid_amount' => 'Rp 40.000',
                    'total_remaining_balance' => 'Rp 110.000',
                ],
            ],
            'statusRows' => [
                [
                    'status' => 'unpaid',
                    'total_rows' => 3,
                    'total_debt' => 'Rp 240.000',
                    'total_paid_amount' => 'Rp 40.000',
                    'total_remaining_balance' => 'Rp 200.000',
                ],
            ],
            'rows' => [
                [
                    'recorded_at' => '07 Januari 2030',
                    'debt_id' => 'debt-1',
                    'employee_id' => 'employee-1',
                    'status' => 'unpaid',
                    'total_debt' => 'Rp 100.000',
                    'total_paid_amount' => 'Rp 40.000',
                    'remaining_balance' => 'Rp 60.000',
                    'notes' => 'Kasbon A',
                ],
            ],
        ])->render();

        $this->assertStringContainsString('Laporan Hutang Karyawan', $html);
        $this->assertStringContainsString('Total Hutang', $html);
        $this->assertStringContainsString('Sudah Dibayar', $html);
        $this->assertStringContainsString('Ringkasan Utama', $html);
        $this->assertStringNotContainsString('Catatan Laporan', $html);
        $this->assertStringNotContainsString('Detail lengkap tersedia di Excel', $html);
        $this->assertStringNotContainsString('Rincian Status', $html);
        $this->assertStringNotContainsString('Kasbon A', $html);
    }

    public function test_employee_debt_pdf_view_uses_owner_readable_report_sections_not_detail_tables(): void
    {
        $html = view('admin.reporting.employee_debt.export_pdf', [
            'title' => 'Laporan Hutang Karyawan',
            'periodLabel' => '01 Januari 2030 s/d 31 Januari 2030',
            'generatedAt' => '31 Januari 2030 10:00',
            'summaryItems' => [
                ['label' => 'Total Hutang', 'value' => 'Rp 310.000'],
                ['label' => 'Sudah Dibayar', 'value' => 'Rp 110.000'],
                ['label' => 'Sisa Hutang', 'value' => 'Rp 200.000'],
            ],
            'periodRows' => [],
            'statusRows' => [],
            'rows' => [
                [
                    'recorded_at' => '07 Januari 2030',
                    'debt_id' => 'debt-1',
                    'employee_id' => 'employee-1',
                    'status' => 'unpaid',
                    'total_debt' => 'Rp 100.000',
                    'total_paid_amount' => 'Rp 40.000',
                    'remaining_balance' => 'Rp 60.000',
                    'notes' => 'Kasbon A',
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
            'email' => $role . '-employee-debt-report-pdf-export@example.test',
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
