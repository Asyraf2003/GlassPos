<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class CreateEmployeeDebtAdjustmentSeeder extends Seeder
{
    private const DEBT_TABLE = 'employee_debts';
    private const ADJUSTMENT_TABLE = 'employee_debt_adjustments';

    /**
     * @var list<array{
     *   id:string,
     *   employee_index:int,
     *   before_total_debt:int,
     *   adjustment_amount:int,
     *   notes:string,
     *   created_at:string,
     *   adjustment:array{
     *     id:string,
     *     reason:string,
     *     created_at:string
     *   }
     * }>
     */
    private const SCENARIOS = [
        [
            'id' => '00000000-0000-5000-0003-000000000001',
            'employee_index' => 10,
            'before_total_debt' => 300000,
            'adjustment_amount' => 50000,
            'notes' => 'Seed kasbon adjustment increase - tambahan sparepart',
            'created_at' => '2026-05-20 13:10:00',
            'adjustment' => [
                'id' => '00000000-0000-5200-0003-000000000001',
                'reason' => 'Seed penyesuaian principal kasbon tambah sparepart',
                'created_at' => '2026-05-20 13:40:00',
            ],
        ],
        [
            'id' => '00000000-0000-5000-0003-000000000002',
            'employee_index' => 11,
            'before_total_debt' => 450000,
            'adjustment_amount' => 125000,
            'notes' => 'Seed kasbon adjustment increase - tambahan kebutuhan operasional',
            'created_at' => '2026-05-20 13:20:00',
            'adjustment' => [
                'id' => '00000000-0000-5200-0003-000000000002',
                'reason' => 'Seed penyesuaian principal kasbon kebutuhan operasional',
                'created_at' => '2026-05-20 13:50:00',
            ],
        ],
        [
            'id' => '00000000-0000-5000-0003-000000000003',
            'employee_index' => 12,
            'before_total_debt' => 700000,
            'adjustment_amount' => 200000,
            'notes' => 'Seed kasbon adjustment increase - tambahan pinjaman sementara',
            'created_at' => '2026-05-20 13:30:00',
            'adjustment' => [
                'id' => '00000000-0000-5200-0003-000000000003',
                'reason' => 'Seed penyesuaian principal kasbon pinjaman sementara',
                'created_at' => '2026-05-20 14:00:00',
            ],
        ],
    ];

    public function run(): void
    {
        $this->guardEnvironment();
        $this->guardSchema();

        $employeeIds = $this->employeeIds();
        $actorId = $this->adminActorId();

        $createdDebts = 0;
        $createdAdjustments = 0;

        foreach (self::SCENARIOS as $scenario) {
            $afterTotalDebt = $this->afterTotalDebt($scenario);
            $employeeId = $employeeIds[$scenario['employee_index']] ?? null;

            if ($employeeId === null) {
                throw new RuntimeException('Not enough employees to seed employee debt adjustments.');
            }

            if ($this->debtExists($scenario['id'])) {
                $this->assertExistingDebtMatches($scenario['id'], $afterTotalDebt);
            } else {
                DB::table(self::DEBT_TABLE)->insert($this->filterExistingColumns(self::DEBT_TABLE, [
                    'id' => $scenario['id'],
                    'employee_id' => $employeeId,
                    'total_debt' => $afterTotalDebt,
                    'remaining_balance' => $afterTotalDebt,
                    'status' => 'unpaid',
                    'notes' => $scenario['notes'],
                    'created_at' => $scenario['created_at'],
                    'updated_at' => $scenario['adjustment']['created_at'],
                ]));

                $createdDebts++;
            }

            $adjustment = $scenario['adjustment'];

            if ($this->adjustmentExists($adjustment['id'])) {
                $this->assertExistingAdjustmentMatches($adjustment['id'], $scenario['id'], $scenario, $afterTotalDebt);
                continue;
            }

            DB::table(self::ADJUSTMENT_TABLE)->insert($this->filterExistingColumns(self::ADJUSTMENT_TABLE, [
                'id' => $adjustment['id'],
                'employee_debt_id' => $scenario['id'],
                'adjustment_type' => 'increase',
                'amount' => $scenario['adjustment_amount'],
                'reason' => $adjustment['reason'],
                'performed_by_actor_id' => $actorId,
                'before_total_debt' => $scenario['before_total_debt'],
                'after_total_debt' => $afterTotalDebt,
                'before_remaining_balance' => $scenario['before_total_debt'],
                'after_remaining_balance' => $afterTotalDebt,
                'created_at' => $adjustment['created_at'],
                'updated_at' => $adjustment['created_at'],
            ]));

            $createdAdjustments++;
        }

        $this->command?->info(sprintf(
            'create-only employee debt adjustments: planned_debts=%d created_debts=%d planned_adjustments=%d created_adjustments=%d',
            count(self::SCENARIOS),
            $createdDebts,
            count(self::SCENARIOS),
            $createdAdjustments
        ));
    }

    private function guardEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('CreateEmployeeDebtAdjustmentSeeder may only run in local/testing environment.');
        }
    }

    private function guardSchema(): void
    {
        foreach ([self::DEBT_TABLE, self::ADJUSTMENT_TABLE, 'employees', 'actor_accesses'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException(sprintf('Required table missing: %s.', $table));
            }
        }

        $requiredColumns = [
            self::DEBT_TABLE => [
                'id',
                'employee_id',
                'total_debt',
                'remaining_balance',
                'status',
                'notes',
                'created_at',
                'updated_at',
            ],
            self::ADJUSTMENT_TABLE => [
                'id',
                'employee_debt_id',
                'adjustment_type',
                'amount',
                'reason',
                'performed_by_actor_id',
                'before_total_debt',
                'after_total_debt',
                'before_remaining_balance',
                'after_remaining_balance',
                'created_at',
                'updated_at',
            ],
            'employees' => [
                'id',
            ],
            'actor_accesses' => [
                'actor_id',
                'role',
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
            ->limit(30)
            ->pluck('id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->values()
            ->all();
    }

    private function adminActorId(): string
    {
        $actorId = DB::table('actor_accesses')
            ->where('role', 'admin')
            ->orderBy('actor_id')
            ->value('actor_id');

        if ($actorId === null) {
            throw new RuntimeException('No admin actor found for employee debt adjustment seed.');
        }

        $actorId = trim((string) $actorId);

        if ($actorId === '') {
            throw new RuntimeException('Resolved admin actor id is empty.');
        }

        return $actorId;
    }

    /**
     * @param array<string, mixed> $scenario
     */
    private function afterTotalDebt(array $scenario): int
    {
        return (int) $scenario['before_total_debt'] + (int) $scenario['adjustment_amount'];
    }

    private function debtExists(string $id): bool
    {
        return DB::table(self::DEBT_TABLE)
            ->where('id', $id)
            ->exists();
    }

    private function adjustmentExists(string $id): bool
    {
        return DB::table(self::ADJUSTMENT_TABLE)
            ->where('id', $id)
            ->exists();
    }

    private function assertExistingDebtMatches(string $id, int $afterTotalDebt): void
    {
        $row = DB::table(self::DEBT_TABLE)
            ->where('id', $id)
            ->first();

        if ($row === null) {
            throw new RuntimeException(sprintf('Expected existing debt missing: %s.', $id));
        }

        $expected = [
            'total_debt' => $afterTotalDebt,
            'remaining_balance' => $afterTotalDebt,
            'status' => 'unpaid',
        ];

        $actual = [
            'total_debt' => (int) $row->total_debt,
            'remaining_balance' => (int) $row->remaining_balance,
            'status' => (string) $row->status,
        ];

        if ($actual !== $expected) {
            throw new RuntimeException(sprintf('Existing debt differs from adjustment seed contract: %s.', $id));
        }
    }

    /**
     * @param array<string, mixed> $scenario
     */
    private function assertExistingAdjustmentMatches(string $id, string $debtId, array $scenario, int $afterTotalDebt): void
    {
        $row = DB::table(self::ADJUSTMENT_TABLE)
            ->where('id', $id)
            ->first();

        if ($row === null) {
            throw new RuntimeException(sprintf('Expected existing adjustment missing: %s.', $id));
        }

        $expected = [
            'employee_debt_id' => $debtId,
            'adjustment_type' => 'increase',
            'amount' => (int) $scenario['adjustment_amount'],
            'before_total_debt' => (int) $scenario['before_total_debt'],
            'after_total_debt' => $afterTotalDebt,
            'before_remaining_balance' => (int) $scenario['before_total_debt'],
            'after_remaining_balance' => $afterTotalDebt,
        ];

        $actual = [
            'employee_debt_id' => (string) $row->employee_debt_id,
            'adjustment_type' => (string) $row->adjustment_type,
            'amount' => (int) $row->amount,
            'before_total_debt' => (int) $row->before_total_debt,
            'after_total_debt' => (int) $row->after_total_debt,
            'before_remaining_balance' => (int) $row->before_remaining_balance,
            'after_remaining_balance' => (int) $row->after_remaining_balance,
        ];

        if ($actual !== $expected) {
            throw new RuntimeException(sprintf('Existing adjustment differs from seed contract: %s.', $id));
        }
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
