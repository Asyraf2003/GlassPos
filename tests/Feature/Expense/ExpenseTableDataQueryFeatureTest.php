<?php

declare(strict_types=1);

namespace Tests\Feature\Expense;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ExpenseTableDataQueryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_and_filter_active_expense_table(): void
    {
        $this->seedCategory('expense-category-1', 'EXP-ELEC', 'Listrik Bengkel');

        $this->seedExpense(
            'expense-1',
            'expense-category-1',
            'EXP-ELEC',
            'Listrik Bengkel',
            250000,
            '2026-03-23',
            'Bayar token listrik',
            'cash',
            null,
        );

        $this->seedExpense(
            'expense-2',
            'expense-category-1',
            'EXP-ELEC',
            'Listrik Bengkel',
            350000,
            '2026-03-22',
            'Bayar listrik lama',
            'transfer',
            '2026-03-24 10:00:00',
        );

        $response = $this->actingAs($this->admin())->get(route('admin.expenses.table', [
            'q' => 'Bayar',
            'category_id' => 'expense-category-1',
            'date_from' => '2026-03-21',
            'date_to' => '2026-03-23',
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data.rows');
        $response->assertJsonPath('data.rows.0.category_code', 'EXP-ELEC');
        $response->assertJsonPath('data.meta.filters.q', 'Bayar');
        $response->assertJsonPath('data.meta.filters.category_id', 'expense-category-1');
    }

    public function test_admin_can_sort_expense_table_by_amount_desc(): void
    {
        $this->seedCategory('expense-category-1', 'EXP-ELEC', 'Listrik Bengkel');
        $this->seedExpense('expense-1', 'expense-category-1', 'EXP-ELEC', 'Listrik Bengkel', 250000, '2026-03-23', 'Bayar token listrik', 'cash');
        $this->seedExpense('expense-2', 'expense-category-1', 'EXP-ELEC', 'Listrik Bengkel', 450000, '2026-03-22', 'Bayar genset', 'cash');

        $response = $this->actingAs($this->admin())->get(route('admin.expenses.table', [
            'sort_by' => 'amount_rupiah',
            'sort_dir' => 'desc',
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.rows.0.amount_rupiah', 450000);
        $response->assertJsonPath('data.rows.1.amount_rupiah', 250000);
    }

    public function test_free_text_search_prioritizes_exact_category_identity_before_newer_broad_description_match(): void
    {
        $this->seedCategory('expense-category-focus', 'FOCUS', 'Biaya Fokus');
        $this->seedCategory('expense-category-other', 'OTHER', 'Biaya Lain');

        $this->seedExpense('expense-exact', 'expense-category-focus', 'FOCUS', 'Biaya Fokus', 250000, '2026-03-20', 'Pembelian rutin', 'cash');
        $this->seedExpense('expense-broad', 'expense-category-other', 'OTHER', 'Biaya Lain', 450000, '2026-03-24', 'Pembelian focus mendesak', 'transfer');

        $response = $this->actingAs($this->admin())->get(route('admin.expenses.table', [
            'q' => 'FOCUS',
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.rows.0.id', 'expense-exact');
        $response->assertJsonPath('data.meta.sort_by', 'relevance');
    }

    public function test_admin_can_access_second_page_of_expense_table(): void
    {
        $this->seedCategory('expense-category-1', 'EXP-ELEC', 'Listrik Bengkel');

        for ($i = 1; $i <= 11; $i++) {
            $this->seedExpense(
                'expense-'.$i,
                'expense-category-1',
                'EXP-ELEC',
                'Listrik Bengkel',
                10000 + $i,
                '2026-03-'.str_pad((string) min($i, 28), 2, '0', STR_PAD_LEFT),
                'Expense '.$i,
                'cash',
            );
        }

        $response = $this->actingAs($this->admin())->get(route('admin.expenses.table', ['page' => 2]));

        $response->assertOk();
        $response->assertJsonPath('data.meta.page', 2);
        $response->assertJsonPath('data.meta.last_page', 2);
        $response->assertJsonCount(1, 'data.rows');
    }

    private function admin(): User
    {
        $user = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-expense-table-query@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $user;
    }

    private function seedCategory(string $id, string $code, string $name): void
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

    private function seedExpense(
        string $id,
        string $categoryId,
        string $categoryCodeSnapshot,
        string $categoryNameSnapshot,
        int $amount,
        string $date,
        string $description,
        string $paymentMethod,
        ?string $deletedAt = null,
    ): void {
        DB::table('operational_expenses')->insert([
            'id' => $id,
            'category_id' => $categoryId,
            'category_code_snapshot' => $categoryCodeSnapshot,
            'category_name_snapshot' => $categoryNameSnapshot,
            'amount_rupiah' => $amount,
            'expense_date' => $date,
            'description' => $description,
            'payment_method' => $paymentMethod,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => $deletedAt,
        ]);
    }
}
