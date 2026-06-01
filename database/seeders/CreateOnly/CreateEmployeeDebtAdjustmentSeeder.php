<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use App\Core\IdentityAccess\Role\Role;
use Database\Seeders\CreateOnly\Support\CreateOnlySeeder;
use Database\Seeders\CreateOnly\Support\CreateOnlySeedCalendar;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CreateEmployeeDebtAdjustmentSeeder extends CreateOnlySeeder
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
     *   day:int,
     *   time:string,
     *   adjustment:array{
     *     id:string,
     *     reason:string,
     *     day:int,
     *     time:string
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
            'day' => 20,
            'time' => '13:10:00',
            'adjustment' => [
                'id' => '00000000-0000-5200-0003-000000000001',
                'reason' => 'Seed penyesuaian principal kasbon tambah sparepart',
                'day' => 20,
                'time' => '13:40:00',
            ],
        ],
        [
            'id' => '00000000-0000-5000-0003-000000000002',
            'employee_index' => 11,
            'before_total_debt' => 450000,
            'adjustment_amount' => 125000,
            'notes' => 'Seed kasbon adjustment increase - tambahan kebutuhan operasional',
            'day' => 20,
            'time' => '13:20:00',
            'adjustment' => [
                'id' => '00000000-0000-5200-0003-000000000002',
                'reason' => 'Seed penyesuaian principal kasbon kebutuhan operasional',
                'day' => 20,
                'time' => '13:50:00',
            ],
        ],
        [
            'id' => '00000000-0000-5000-0003-000000000003',
            'employee_index' => 12,
            'before_total_debt' => 700000,
            'adjustment_amount' => 200000,
            'notes' => 'Seed kasbon adjustment increase - tambahan pinjaman sementara',
            'day' => 20,
            'time' => '13:30:00',
            'adjustment' => [
                'id' => '00000000-0000-5200-0003-000000000003',
                'reason' => 'Seed penyesuaian principal kasbon pinjaman sementara',
                'day' => 20,
                'time' => '14:00:00',
            ],
        ],
    ];

    public function run(): void
    {
        $this->assertLocalOrTesting();

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

            $debtCreatedAt = $this->scenarioDateTime($scenario);
            $adjustmentCreatedAt = $this->scenarioDateTime($scenario['adjustment']);

            if ($this->debtExists($scenario['id'])) {
                $this->assertExistingDebtMatches($scenario['id'], $afterTotalDebt);
            } else {
                if ($this->createOnly(self::DEBT_TABLE, 'id', $scenario['id'], [
                    'id' => $scenario['id'],
                    'employee_id' => $employeeId,
                    'total_debt' => $afterTotalDebt,
                    'remaining_balance' => $afterTotalDebt,
                    'status' => 'unpaid',
                    'notes' => $scenario['notes'],
                    'created_at' => $debtCreatedAt,
                    'updated_at' => $adjustmentCreatedAt,
                ])) {
                    $createdDebts++;
                }
            }

            $adjustment = $scenario['adjustment'];

            if ($this->adjustmentExists($adjustment['id'])) {
                $this->assertExistingAdjustmentMatches($adjustment['id'], $scenario['id'], $scenario, $afterTotalDebt);
                continue;
            }

            if ($this->createOnly(self::ADJUSTMENT_TABLE, 'id', $adjustment['id'], [
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
                'created_at' => $adjustmentCreatedAt,
                'updated_at' => $adjustmentCreatedAt,
            ])) {
                $createdAdjustments++;
            }
        }

        $this->command?->info(sprintf(
            'create-only employee debt adjustments: planned_debts=%d created_debts=%d planned_adjustments=%d created_adjustments=%d',
            count(self::SCENARIOS),
            $createdDebts,
            count(self::SCENARIOS),
            $createdAdjustments
        ));
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

    /**
     * @param array<string, mixed> $scenario
     */
    private function scenarioDateTime(array $scenario): string
    {
        return CreateOnlySeedCalendar::currentMonthDate((int) $scenario['day'])
            .' '
            .(string) $scenario['time'];
    }


    private function adminActorId(): string
    {
        $actorId = DB::table('actor_accesses')
            ->where('role', Role::ADMIN)
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

}
