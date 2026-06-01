<?php

declare(strict_types=1);

namespace Tests\Feature\ReportingExports;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

final class OperationalExpenseReportExcelExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_operational_expense_report_as_xlsx_with_numeric_rupiah_cells(): void
    {
        $this->seedExpenseCategory('expense-category-1', 'LISTRIK', 'Listrik');
        $this->seedExpenseCategory('expense-category-2', 'MAKAN', 'Makan');

        $this->seedOperationalExpense('expense-1', 'expense-category-1', 100000, '2030-01-06', 'Bayar listrik', 'cash', 'INV-001', null);
        $this->seedOperationalExpense('expense-2', 'expense-category-2', 25000, '2030-01-07', 'Makan tim', 'tf', null, null);
        $this->seedOperationalExpense('expense-3', 'expense-category-2', 15000, '2030-01-07', 'Snack tim', 'cash', null, null);
        $this->seedOperationalExpense('expense-4', 'expense-category-1', 75000, '2030-01-31', 'Listrik akhir bulan', 'cash', null, null);
        $this->seedOperationalExpense('expense-5', 'expense-category-1', 50000, '2030-01-07', 'Deleted row', 'cash', null, '2030-01-07 10:00:00');
        $this->seedOperationalExpense('expense-6', 'expense-category-2', 90000, '2030-02-01', 'Outside row', 'cash', null, null);

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.operational_expense.export_excel', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertDownload('laporan-biaya-operasional-2030-01-01-sampai-2030-01-31.xlsx');

        $path = tempnam(sys_get_temp_dir(), 'operational-expense-report-');
        file_put_contents($path, $response->streamedContent());

        $spreadsheet = IOFactory::load($path);

        $this->assertSame(['Ringkasan', 'Detail Biaya', 'Rekap Per Tanggal', 'Rekap Per Kategori'], $spreadsheet->getSheetNames());

        $summary = $spreadsheet->getSheetByName('Ringkasan');
        $detail = $spreadsheet->getSheetByName('Detail Biaya');
        $period = $spreadsheet->getSheetByName('Rekap Per Tanggal');
        $category = $spreadsheet->getSheetByName('Rekap Per Kategori');

        $this->assertNotNull($summary);
        $this->assertNotNull($detail);
        $this->assertNotNull($period);
        $this->assertNotNull($category);

        $this->assertSame('Laporan Biaya Operasional', $summary->getCell('A1')->getValue());
        $this->assertSame('01 Januari 2030 s/d 31 Januari 2030', $summary->getCell('B2')->getValue());
        $this->assertSame(4, $summary->getCell('B6')->getValue());
        $this->assertSame(215000, $summary->getCell('B7')->getValue());
        $this->assertSame('Listrik', $summary->getCell('B8')->getValue());
        $this->assertSame(175000, $summary->getCell('B9')->getValue());
        $this->assertSame(6935, $summary->getCell('B10')->getValue());

        $this->assertSame('ID Biaya', $detail->getCell('B1')->getValue());
        $this->assertSame('expense-1', $detail->getCell('B2')->getValue());
        $this->assertSame('06 Januari 2030', $detail->getCell('C2')->getValue());
        $this->assertSame('LISTRIK', $detail->getCell('D2')->getValue());
        $this->assertSame('Listrik', $detail->getCell('E2')->getValue());
        $this->assertSame('Bayar listrik', $detail->getCell('F2')->getValue());
        $this->assertSame('Tunai', $detail->getCell('G2')->getValue());
        $this->assertSame('INV-001', $detail->getCell('H2')->getValue());
        $this->assertSame(100000, $detail->getCell('I2')->getValue());
        $this->assertNull($detail->getCell('B6')->getValue());

        $this->assertSame(100000, $period->getCell('C2')->getValue());
        $this->assertSame('LISTRIK', $category->getCell('A2')->getValue());
        $this->assertSame(175000, $category->getCell('D2')->getValue());

        unlink($path);
        $spreadsheet->disconnectWorksheets();
    }

    public function test_kasir_cannot_export_operational_expense_report(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(
            route('admin.reports.operational_expense.export_excel')
        );

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_operational_expense_excel_export_rejects_range_longer_than_366_days(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.operational_expense.export_excel', [
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
            'email' => $role . '-operational-expense-report-export@example.test',
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
