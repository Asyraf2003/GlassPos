<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class OperationalExpenseReportPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_operational_expense_report_page(): void
    {
        $this->get(route('admin.reports.operational_expense.index'))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_operational_expense_report_page(): void
    {
        $response = $this->actingAs($this->user('kasir'))
            ->get(route('admin.reports.operational_expense.index'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_operational_expense_report_page_and_see_report_data(): void
    {
        $this->seedExpenseCategory('expense-category-1', 'LISTRIK', 'Listrik');
        $this->seedExpenseCategory('expense-category-2', 'MAKAN', 'Makan');

        $this->seedOperationalExpense('expense-1', 'expense-category-1', 100000, '2030-01-06', 'Bayar listrik', 'cash', null);
        $this->seedOperationalExpense('expense-2', 'expense-category-2', 25000, '2030-01-07', 'Makan tim', 'tf', null);
        $this->seedOperationalExpense('expense-3', 'expense-category-2', 15000, '2030-01-07', 'Snack tim', 'cash', null);
        $this->seedOperationalExpense('expense-4', 'expense-category-1', 75000, '2030-01-31', 'Listrik akhir bulan', 'cash', null);
        $this->seedOperationalExpense('expense-5', 'expense-category-1', 50000, '2030-01-07', 'Deleted row', 'cash', '2030-01-07 10:00:00');

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.operational_expense.index', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-01',
            ])
        );

        $response->assertOk();
        $response->assertSee('Biaya Operasional');
        $response->assertSee('value="custom"', false);
        $response->assertSee('name="date_from"', false);
        $response->assertSee('name="date_to"', false);
        $response->assertSee('operational-expense-report-filter-form', false);
        $response->assertSee('01 Januari 2030 s/d 31 Januari 2030');
        $response->assertSee('Rincian Ringkas');
        $response->assertSee('Jumlah Catatan');
        $response->assertSee('Total Biaya');
        $response->assertSee('Listrik');
        $response->assertSee('Makan');
        $response->assertSee('Rp 215.000');
        $response->assertSee('Rp 175.000');
        $response->assertSee('Rp 6.935');
        $response->assertDontSee('Detail Biaya Operasional');
        $response->assertDontSee('Bayar listrik');
        $response->assertDontSee('Deleted row');
    }

    public function test_admin_sees_owner_readable_report_sections_on_operational_expense_page(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.operational_expense.index', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-01',
            ])
        );

        $response->assertOk();
        $response->assertSee('Ringkasan Utama');
        $response->assertSee('Catatan Laporan');
        $response->assertSee('Detail lengkap tersedia di Excel');
    }

    public function test_admin_can_filter_operational_expense_report_with_custom_range(): void
    {
        $this->seedExpenseCategory('expense-category-1', 'LISTRIK', 'Listrik');
        $this->seedExpenseCategory('expense-category-2', 'MAKAN', 'Makan');

        $this->seedOperationalExpense('expense-1', 'expense-category-1', 100000, '2030-01-09', 'Before custom range', 'cash', null);
        $this->seedOperationalExpense('expense-2', 'expense-category-2', 50000, '2030-01-10', 'Inside custom range', 'cash', null);
        $this->seedOperationalExpense('expense-3', 'expense-category-1', 75000, '2030-01-31', 'Inside custom end', 'cash', null);
        $this->seedOperationalExpense('expense-4', 'expense-category-2', 999000, '2030-02-01', 'After custom range', 'cash', null);

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.operational_expense.index', [
                'period_mode' => 'custom',
                'date_from' => '2030-01-10',
                'date_to' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertSee('Custom');
        $response->assertSee('10 Januari 2030 s/d 31 Januari 2030');
        $response->assertDontSee('Inside custom range');
        $response->assertDontSee('Inside custom end');
        $response->assertSee('Rp 125.000');
        $response->assertSee('Rp 5.681');
        $response->assertDontSee('Before custom range');
        $response->assertDontSee('After custom range');
    }

    public function test_operational_expense_custom_range_requires_start_and_end_dates(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.operational_expense.index', [
                'period_mode' => 'custom',
            ])
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors(['date_from', 'date_to']);
    }

    public function test_operational_expense_custom_range_rejects_start_date_after_end_date(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.operational_expense.index', [
                'period_mode' => 'custom',
                'date_from' => '2030-02-01',
                'date_to' => '2030-01-31',
            ])
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors(['date_from']);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-operational-expense-report@example.test',
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
            'reference_no' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => $deletedAt,
        ]);
    }
}
