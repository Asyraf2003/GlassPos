<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class CreateEmployeeDebtSeeder extends Seeder
{
    private const TARGET_TABLE = 'employee_debts';

    /**
     * @var list<array{
     *   id:string,
     *   total_debt:int,
     *   notes:string,
     *   created_at:string
     * }>
     */
    private const DEBT_SCENARIOS = [
        [
            'id' => '00000000-0000-5000-0001-000000000001',
            'total_debt' => 150000,
            'notes' => 'Seed kasbon aktif - sparepart keluarga',
            'created_at' => '2026-05-20 08:10:00',
        ],
        [
            'id' => '00000000-0000-5000-0001-000000000002',
            'total_debt' => 225000,
            'notes' => 'Seed kasbon aktif - kebutuhan harian',
            'created_at' => '2026-05-20 08:20:00',
        ],
        [
            'id' => '00000000-0000-5000-0001-000000000003',
            'total_debt' => 300000,
            'notes' => 'Seed kasbon aktif - operasional pribadi',
            'created_at' => '2026-05-20 08:30:00',
        ],
        [
            'id' => '00000000-0000-5000-0001-000000000004',
            'total_debt' => 450000,
            'notes' => 'Seed kasbon aktif - cicilan internal',
            'created_at' => '2026-05-20 08:40:00',
        ],
        [
            'id' => '00000000-0000-5000-0001-000000000005',
            'total_debt' => 600000,
            'notes' => 'Seed kasbon aktif - kebutuhan mendadak',
            'created_at' => '2026-05-20 08:50:00',
        ],
        [
            'id' => '00000000-0000-5000-0001-000000000006',
            'total_debt' => 750000,
            'notes' => 'Seed kasbon aktif - pinjaman sementara',
            'created_at' => '2026-05-20 09:00:00',
        ],
    ];

    public function run(): void
    {
        $this->guardEnvironment();
        $this->guardSchema();

        $employeeIds = $this->employeeIds();

        $created = 0;

        foreach (self::DEBT_SCENARIOS as $index => $scenario) {
            $employeeId = $employeeIds[$index] ?? null;

            if ($employeeId === null) {
                throw new RuntimeException('Not enough employees to seed employee debts.');
            }

            $exists = DB::table(self::TARGET_TABLE)
                ->where('id', $scenario['id'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table(self::TARGET_TABLE)->insert($this->filterExistingColumns(self::TARGET_TABLE, [
                'id' => $scenario['id'],
                'employee_id' => $employeeId,
                'total_debt' => $scenario['total_debt'],
                'remaining_balance' => $scenario['total_debt'],
                'status' => 'unpaid',
                'notes' => $scenario['notes'],
                'created_at' => $scenario['created_at'],
                'updated_at' => $scenario['created_at'],
            ]));

            $created++;
        }

        $this->command?->info(sprintf(
            'create-only employee debts: planned=%d created=%d',
            count(self::DEBT_SCENARIOS),
            $created
        ));
    }

    private function guardEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('CreateEmployeeDebtSeeder may only run in local/testing environment.');
        }
    }

    private function guardSchema(): void
    {
        foreach ([self::TARGET_TABLE, 'employees'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException(sprintf('Required table missing: %s.', $table));
            }
        }

        $requiredColumns = [
            self::TARGET_TABLE => [
                'id',
                'employee_id',
                'total_debt',
                'remaining_balance',
                'status',
                'notes',
                'created_at',
                'updated_at',
            ],
            'employees' => [
                'id',
            ],
        ];

        foreach ($requiredColumns as $table => $columns) {
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    throw new RuntimeException(sprintf('Required column missing: %s.%s.', $table, $column));
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    private function employeeIds(): array
    {
        return DB::table('employees')
            ->orderBy('id')
            ->limit(count(self::DEBT_SCENARIOS))
            ->pluck('id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function filterExistingColumns(string $table, array $record): array
    {
        $columns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($record, $columns);
    }
}
