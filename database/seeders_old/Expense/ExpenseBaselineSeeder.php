<?php

declare(strict_types=1);

namespace Database\Seeders\Expense;

use Database\Seeders\Support\SeedDensity;
use Database\Seeders\Support\SeedWindow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ExpenseBaselineSeeder extends Seeder
{
    /**
     * @var array<int, array{
     *   id:string,
     *   code:string,
     *   name:string,
     *   description:string
     * }>
     */
    private array $categories = [
        [
            'id' => 'seed-exp-cat-elec',
            'code' => 'EXP-ELEC',
            'name' => 'Listrik Bengkel',
            'description' => 'Token atau tagihan listrik',
        ],
        [
            'id' => 'seed-exp-cat-wtr',
            'code' => 'EXP-WTR',
            'name' => 'Air PDAM',
            'description' => 'Tagihan air bengkel',
        ],
        [
            'id' => 'seed-exp-cat-snck',
            'code' => 'EXP-SNCK',
            'name' => 'Konsumsi Harian',
            'description' => 'Makan siang, kopi, kebutuhan ringan',
        ],
        [
            'id' => 'seed-exp-cat-fuel',
            'code' => 'EXP-FUEL',
            'name' => 'Bensin Operasional',
            'description' => 'Bensin kendaraan operasional',
        ],
        [
            'id' => 'seed-exp-cat-atk',
            'code' => 'EXP-ATK',
            'name' => 'ATK & Kebersihan',
            'description' => 'Nota, pulpen, sabun, kain lap',
        ],
        [
            'id' => 'seed-exp-cat-misc',
            'code' => 'EXP-MISC',
            'name' => 'Lain-lain',
            'description' => 'Pengeluaran tak terduga',
        ],
    ];

    public function run(): void
    {
        $window = SeedWindow::baselineMonth();
        $density = SeedDensity::baseline();

        $this->seedCategories();
        $this->seedExpenses($window['days'], $density['expense_rows_per_day']);

        $this->command?->info('ExpenseBaselineSeeder selesai: baseline expense 1 bulan deterministic dibuat.');
    }

    private function seedCategories(): void
    {
        $now = now();

        foreach ($this->categories as $category) {
            DB::table('expense_categories')->updateOrInsert(
                ['code' => $category['code']],
                [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    /**
     * @param list<\Carbon\CarbonImmutable> $days
     */
    private function seedExpenses(array $days, int $rowsPerDay): void
    {
        foreach ($days as $dayIndex => $day) {
            for ($slot = 1; $slot <= $rowsPerDay; $slot++) {
                $category = $this->pickCategory($dayIndex, $slot);
                $expenseId = sprintf('seed-exp-bl-%s-%02d', $day->format('Ymd'), $slot);
                $referenceNo = sprintf('SEED-BL-EXP-%s-%02d', $day->format('Ymd'), $slot);
                $amount = $this->resolveAmount($category['code'], $dayIndex, $slot);
                $paymentMethod = $this->resolvePaymentMethod($dayIndex, $slot);
                $timestamp = $day->setTime(8 + $slot, ($slot * 7) % 60, 0);

                DB::table('operational_expenses')->updateOrInsert(
                    ['id' => $expenseId],
                    [
                        'category_id' => $category['id'],
                        'category_code_snapshot' => $category['code'],
                        'category_name_snapshot' => $category['name'],
                        'amount_rupiah' => $amount,
                        'expense_date' => $day->format('Y-m-d'),
                        'description' => $this->buildDescription($category['name'], $slot),
                        'payment_method' => $paymentMethod,
                        'reference_no' => $referenceNo,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                        'deleted_at' => null,
                    ]
                );
            }
        }
    }

    /**
     * @return array{
     *   id:string,
     *   code:string,
     *   name:string,
     *   description:string
     * }
     */
    private function pickCategory(int $dayIndex, int $slot): array
    {
        $index = ($dayIndex + $slot - 1) % count($this->categories);

        return $this->categories[$index];
    }

    private function resolveAmount(string $categoryCode, int $dayIndex, int $slot): int
    {
        $base = match ($categoryCode) {
            'EXP-ELEC' => 250000,
            'EXP-WTR' => 120000,
            'EXP-SNCK' => 50000,
            'EXP-FUEL' => 80000,
            'EXP-ATK' => 45000,
            default => 70000,
        };

        return $base + (($dayIndex + 1) * 5000) + ($slot * 2500);
    }

    private function resolvePaymentMethod(int $dayIndex, int $slot): string
    {
        return (($dayIndex + $slot) % 3 === 0) ? 'tf' : 'cash';
    }

    private function buildDescription(string $categoryName, int $slot): string
    {
        return sprintf('Seed baseline %s slot %02d', $categoryName, $slot);
    }
}
