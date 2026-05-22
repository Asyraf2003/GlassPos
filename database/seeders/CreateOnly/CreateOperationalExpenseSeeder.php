<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class CreateOperationalExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $this->assertLocalOrTesting();

        $categories = DB::table('expense_categories')
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(15)
            ->get(['id', 'code', 'name']);

        if ($categories->count() < 3) {
            throw new RuntimeException('CreateOperationalExpenseSeeder requires at least 3 active expense categories. Run make product-1/product-2 first.');
        }

        $now = now()->format('Y-m-d H:i:s');
        $created = 0;
        $paymentMethods = ['cash', 'transfer', 'qris'];

        DB::transaction(function () use ($categories, $paymentMethods, $now, &$created): void {
            for ($i = 1; $i <= 45; $i++) {
                $category = $categories[($i - 1) % $categories->count()];
                $id = sprintf('seed-operational-expense-%04d', $i);

                $amountRupiah = 15000 + ($i * 2500);
                $expenseDate = sprintf('2026-05-%02d', (($i - 1) % 30) + 1);
                $paymentMethod = $paymentMethods[($i - 1) % count($paymentMethods)];

                if ($this->insertIfMissing('operational_expenses', $id, [
                    'id' => $id,
                    'category_id' => (string) $category->id,
                    'category_code_snapshot' => (string) ($category->code ?? ''),
                    'category_name_snapshot' => (string) ($category->name ?? ''),
                    'amount_rupiah' => $amountRupiah,
                    'expense_date' => $expenseDate,
                    'description' => sprintf('Seed biaya operasional %04d', $i),
                    'payment_method' => $paymentMethod,
                    'reference_no' => $i % 3 === 0 ? sprintf('SEED-OPEX-REF-%04d', $i) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ])) {
                    $created++;
                }
            }
        });

        $this->command?->info('operational_expenses created=' . $created);
    }

    private function assertLocalOrTesting(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Create-only seeders may only run in local or testing environments.');
        }
    }

    /**
     * @param array<string, mixed> $values
     */
    private function insertIfMissing(string $table, string $id, array $values): bool
    {
        if (DB::table($table)->where('id', $id)->exists()) {
            return false;
        }

        DB::table($table)->insert($this->filterExistingColumns($table, $values));

        return true;
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function filterExistingColumns(string $table, array $values): array
    {
        $columns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($values, $columns);
    }
}
