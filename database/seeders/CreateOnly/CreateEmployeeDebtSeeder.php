<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use Database\Seeders\CreateOnly\Support\CreateOnlySeeder;
use Database\Seeders\CreateOnly\Support\CreateOnlySeedCalendar;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CreateEmployeeDebtSeeder extends CreateOnlySeeder
{
    private const TARGET_TABLE = 'employee_debts';

    /**
     * @var list<array{
     *   id:string,
     *   total_debt:int,
     *   day:int,
     *   time:string,
     *   notes:string
     * }>
     */
    private const DEBT_SCENARIOS = [
        [
            'id' => '00000000-0000-5000-0001-000000000001',
            'total_debt' => 150000,
            'day' => 20,
            'time' => '08:10:00',
            'notes' => 'Seed kasbon aktif - sparepart keluarga',
        ],
        [
            'id' => '00000000-0000-5000-0001-000000000002',
            'total_debt' => 225000,
            'day' => 20,
            'time' => '08:20:00',
            'notes' => 'Seed kasbon aktif - kebutuhan harian',
        ],
        [
            'id' => '00000000-0000-5000-0001-000000000003',
            'total_debt' => 300000,
            'day' => 20,
            'time' => '08:30:00',
            'notes' => 'Seed kasbon aktif - operasional pribadi',
        ],
        [
            'id' => '00000000-0000-5000-0001-000000000004',
            'total_debt' => 450000,
            'day' => 20,
            'time' => '08:40:00',
            'notes' => 'Seed kasbon aktif - cicilan internal',
        ],
        [
            'id' => '00000000-0000-5000-0001-000000000005',
            'total_debt' => 600000,
            'day' => 20,
            'time' => '08:50:00',
            'notes' => 'Seed kasbon aktif - kebutuhan mendadak',
        ],
        [
            'id' => '00000000-0000-5000-0001-000000000006',
            'total_debt' => 750000,
            'day' => 20,
            'time' => '09:00:00',
            'notes' => 'Seed kasbon aktif - pinjaman sementara',
        ],
    ];

    public function run(): void
    {
        $this->assertLocalOrTesting();

        $employeeIds = $this->employeeIds();

        $created = 0;

        foreach (self::DEBT_SCENARIOS as $index => $scenario) {
            $employeeId = $employeeIds[$index] ?? null;

            if ($employeeId === null) {
                throw new RuntimeException('Not enough employees to seed employee debts.');
            }

            $createdAt = $this->scenarioDateTime($scenario);

            if ($this->createOnly(self::TARGET_TABLE, 'id', $scenario['id'], [
                'id' => $scenario['id'],
                'employee_id' => $employeeId,
                'total_debt' => $scenario['total_debt'],
                'remaining_balance' => $scenario['total_debt'],
                'status' => 'unpaid',
                'notes' => $scenario['notes'],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])) {
                $created++;
            }
        }

        $this->command?->info(sprintf(
            'create-only employee debts: planned=%d created=%d',
            count(self::DEBT_SCENARIOS),
            $created
        ));
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
}
