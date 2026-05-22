<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        if ($this->baselineAlreadySeeded()) {
            $this->command?->info('ExpenseSeeder dilewati: baseline expense categories sudah ada.');

            return;
        }

        $now = Carbon::now();

        $categoriesData = [
            ['code' => 'EXP-ELEC', 'name' => 'Listrik Bengkel', 'desc' => 'Token atau tagihan listrik'],
            ['code' => 'EXP-WTR', 'name' => 'Air PDAM', 'desc' => 'Tagihan air bengkel'],
            ['code' => 'EXP-SNCK', 'name' => 'Konsumsi Harian', 'desc' => 'Makan siang, kopi, rokok mekanik'],
            ['code' => 'EXP-FUEL', 'name' => 'Bensin Operasional', 'desc' => 'Bensin kendaraan operasional'],
            ['code' => 'EXP-ATK', 'name' => 'ATK & Kebersihan', 'desc' => 'Nota, pulpen, sabun, kain lap'],
            ['code' => 'EXP-MISC', 'name' => 'Lain-lain', 'desc' => 'Pengeluaran tak terduga'],
        ];

        $categories = [];
        $categoryInserts = [];

        foreach ($categoriesData as $category) {
            $id = (string) Str::uuid();

            $categories[] = [
                'id' => $id,
                'code' => $category['code'],
                'name' => $category['name'],
            ];

            $categoryInserts[] = [
                'id' => $id,
                'code' => $category['code'],
                'name' => $category['name'],
                'description' => $category['desc'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('expense_categories')->insert($categoryInserts);

        $expenses = [];
        $paymentMethods = ['cash', 'cash', 'cash', 'tf'];

        $startDate = Carbon::now()->subYear();
        $endDate = Carbon::now();

        $this->command?->info('Membuat ribuan data pengeluaran operasional...');

        while ($startDate->lte($endDate)) {
            $dailyTransactions = rand(1, 5);

            for ($i = 0; $i < $dailyTransactions; $i++) {
                $category = $categories[array_rand($categories)];

                $amountRupiah = match ($category['code']) {
                    'EXP-ELEC' => rand(20, 100) * 10000,
                    'EXP-SNCK' => rand(3, 15) * 10000,
                    'EXP-FUEL' => rand(5, 15) * 10000,
                    'EXP-ATK' => rand(2, 8) * 10000,
                    'EXP-WTR' => rand(5, 20) * 10000,
                    default => rand(5, 20) * 10000,
                };

                $createdAt = $startDate->copy()->addHours(rand(8, 17));

                $expenses[] = [
                    'id' => (string) Str::uuid(),
                    'category_id' => $category['id'],
                    'category_code_snapshot' => $category['code'],
                    'category_name_snapshot' => $category['name'],
                    'amount_rupiah' => $amountRupiah,
                    'expense_date' => $startDate->format('Y-m-d'),
                    'description' => 'Pembayaran ' . $category['name'],
                    'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                    'reference_no' => 'REF-' . $startDate->format('Ymd') . '-' . Str::upper(Str::random(4)),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt->copy()->addHours(rand(0, 3)),
                    'deleted_at' => null,
                ];
            }

            $startDate->addDay();
        }

        foreach (array_chunk($expenses, 500) as $chunk) {
            DB::table('operational_expenses')->insert($chunk);
        }

        $this->command?->info('Berhasil menanamkan ' . count($expenses) . ' baris pengeluaran operasional!');
    }

    private function baselineAlreadySeeded(): bool
    {
        return DB::table('expense_categories')
            ->where('code', 'EXP-ELEC')
            ->exists();
    }
}
