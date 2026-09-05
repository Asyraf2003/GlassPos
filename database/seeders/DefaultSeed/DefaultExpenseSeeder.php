<?php

declare(strict_types=1);

namespace Database\Seeders\DefaultSeed;

use App\Application\Expense\UseCases\CreateAuditedExpenseCategoryHandler;
use App\Application\Expense\UseCases\RecordAuditedOperationalExpenseHandler;
use Database\Seeders\DefaultSeed\Support\DefaultSeedActor;
use Database\Seeders\DefaultSeed\Support\DefaultSeedWindow;
use Database\Seeders\DefaultSeed\Support\SeedResultGuard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class DefaultExpenseSeeder extends Seeder
{
    private const CATEGORIES = [
        ['UTIL', 'Utilitas Bengkel'],
        ['RENT', 'Sewa dan Tempat'],
        ['FUEL', 'BBM dan Transport'],
        ['CLEAN', 'Kebersihan'],
        ['MISC', 'Operasional Lainnya'],
    ];

    public function run(
        CreateAuditedExpenseCategoryHandler $categories,
        RecordAuditedOperationalExpenseHandler $expenses,
    ): void {
        $actorId = DefaultSeedActor::adminId();

        foreach (self::CATEGORIES as [$code, $name]) {
            if (! DB::table('expense_categories')->where('code', $code)->exists()) {
                SeedResultGuard::data($categories->handle(
                    $code,
                    $name,
                    'Default six-month seed category.',
                    $actorId,
                    'seed_default',
                ), 'create expense category '.$code);
            }
        }

        $categoryIds = DB::table('expense_categories')
            ->whereIn('code', array_column(self::CATEGORIES, 0))
            ->orderBy('code')
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->values();

        if ($categoryIds->count() !== count(self::CATEGORIES)) {
            throw new RuntimeException('Default expense categories are incomplete.');
        }

        $methods = ['cash', 'transfer', 'qris'];

        for ($index = 0; $index < 50; $index++) {
            $description = sprintf('Default seed operational expense %02d', $index + 1);

            if (DB::table('operational_expenses')->where('description', $description)->exists()) {
                continue;
            }

            SeedResultGuard::data($expenses->handle(
                $categoryIds[$index % $categoryIds->count()],
                25000 + (($index % 10) * 17500),
                DefaultSeedWindow::dateAt($index, 50)->format('Y-m-d'),
                $description,
                $methods[$index % count($methods)],
                $actorId,
                'seed_default',
            ), 'create operational expense '.($index + 1));
        }
    }
}
