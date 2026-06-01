<?php

declare(strict_types=1);

namespace Tests\Feature\ReportingExports;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class OperationalExpenseReportPdfExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_operational_expense_report_as_pdf(): void
    {
        $this->seedExpenseCategory('expense-category-1', 'LISTRIK', 'Listrik');
        $this->seedExpenseCategory('expense-category-2', 'MAKAN', 'Makan');

        $this->seedOperationalExpense('expense-1', 'expense-category-1', 100000, '2030-01-06', 'Bayar listrik', 'cash', 'INV-001', null);
        $this->seedOperationalExpense('expense-2', 'expense-category-2', 25000, '2030-01-07', 'Makan tim', 'tf', null, null);
        $this->seedOperationalExpense('expense-outside', 'expense-category-2', 90000, '2030-02-01', 'Outside row', 'cash', null, null);

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.operational_expense.export_pdf', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertDownload('laporan-biaya-operasional-2030-01-01-sampai-2030-01-31.pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_kasir_cannot_export_operational_expense_report_as_pdf(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(
            route('admin.reports.operational_expense.export_pdf')
        );

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_operational_expense_pdf_export_rejects_range_longer_than_one_month(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.operational_expense.export_pdf', [
                'period_mode' => 'custom',
                'date_from' => '2030-01-01',
                'date_to' => '2030-02-01',
            ])
        );

        $response->assertStatus(422);
        $response->assertSeeText('Export PDF maksimal 1 bulan.');
    }

    public function test_operational_expense_pdf_view_contains_indonesian_report_labels(): void
    {
        $html = view('admin.reporting.operational_expense.export_pdf', [
            'title' => 'Laporan Biaya Operasional',
            'periodLabel' => '01 Januari 2030 s/d 31 Januari 2030',
            'generatedAt' => '31 Januari 2030 10:00',
            'summaryItems' => [
                ['label' => 'Jumlah Catatan', 'value' => 2],
                ['label' => 'Total Biaya', 'value' => 'Rp 125.000'],
                ['label' => 'Kategori Terbesar', 'value' => 'Listrik'],
                ['label' => 'Nilai Kategori', 'value' => 'Rp 100.000'],
                ['label' => 'Rata-rata Harian', 'value' => 'Rp 4.032'],
            ],
            'rows' => [
                [
                    'date' => '06 Januari 2030',
                    'expense_id' => 'expense-1',
                    'category_name' => 'Listrik',
                    'description' => 'Bayar listrik',
                    'payment_method' => 'Tunai',
                    'reference_no' => 'INV-001',
                    'amount' => 'Rp 100.000',
                ],
            ],
        ])->render();

        $this->assertStringContainsString('Laporan Biaya Operasional', $html);
        $this->assertStringContainsString('Jumlah Catatan', $html);
        $this->assertStringContainsString('Total Biaya', $html);
        $this->assertStringContainsString('Kategori Terbesar', $html);
        $this->assertStringContainsString('Bayar listrik', $html);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-operational-expense-report-pdf-export@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }

    private function seedExpenseCategory(string $id, string $code, string $name): void
    {
        DB::table('expense_categories')->insert([
            'id' => $id,
            'code' => $code,
            'name' => $name,
            'description' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedOperationalExpense(
        string $id,
        string $categoryId,
        int $amountRupiah,
        string $expenseDate,
        string $description,
        string $paymentMethod,
        ?string $referenceNo,
        ?string $deletedAt,
    ): void {
        DB::table('operational_expenses')->insert([
            'id' => $id,
            'category_id' => $categoryId,
            'category_code_snapshot' => 'SNAP',
            'category_name_snapshot' => 'Snapshot',
            'amount_rupiah' => $amountRupiah,
            'expense_date' => $expenseDate,
            'description' => $description,
            'payment_method' => $paymentMethod,
            'reference_no' => $referenceNo,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => $deletedAt,
        ]);
    }
}
