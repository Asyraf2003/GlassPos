<?php

declare(strict_types=1);

namespace Database\Seeders;

use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EmployeeFinanceSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Factory::create('id_ID');
        $payPeriods = ['daily', 'weekly', 'monthly'];

        for ($i = 0; $i < 20; $i++) {
            $employeeId = Str::uuid()->toString();
            $payPeriod = $payPeriods[$i % count($payPeriods)];

            $baseSalary = match ($payPeriod) {
                'daily' => $faker->randomElement([100000, 125000, 150000, 175000, 200000]),
                'weekly' => $faker->randomElement([700000, 850000, 1000000, 1150000, 1300000]),
                default => $faker->randomElement([3000000, 3500000, 4000000, 4500000, 5000000]),
            };

            DB::table('employees')->insert([
                'id' => $employeeId,
                'name' => $faker->name,
                'phone' => '081' . $faker->numerify('#########'),
                'base_salary' => $baseSalary,
                'pay_period' => $payPeriod,
                'status' => 'active',
                'created_at' => Carbon::now()->subYear(),
                'updated_at' => Carbon::now()->subYear(),
            ]);

            for ($m = 1; $m <= 12; $m++) {
                $date = Carbon::now()->subMonths($m)->day(25);

                DB::table('payroll_disbursements')->insert([
                    'id' => Str::uuid()->toString(),
                    'employee_id' => $employeeId,
                    'amount' => $baseSalary,
                    'disbursement_date' => $date->format('Y-m-d H:i:s'),
                    'mode' => $payPeriod,
                    'notes' => 'Pencairan gaji ' . $date->format('F Y'),
                    'created_at' => $date,
                ]);
            }

            if ($i < 10) {
                $debtId = Str::uuid()->toString();
                $totalDebt = $faker->numberBetween(5, 15) * 100000;
                $paidAmount = $faker->numberBetween(1, 4) * 100000;

                DB::table('employee_debts')->insert([
                    'id' => $debtId,
                    'employee_id' => $employeeId,
                    'total_debt' => $totalDebt,
                    'remaining_balance' => $totalDebt - $paidAmount,
                    'status' => 'unpaid',
                    'notes' => 'Pinjaman kasbon operasional',
                    'created_at' => Carbon::now()->subMonths(2),
                ]);

                DB::table('employee_debt_payments')->insert([
                    'id' => Str::uuid()->toString(),
                    'employee_debt_id' => $debtId,
                    'amount' => $paidAmount,
                    'payment_date' => Carbon::now()->subMonth()->format('Y-m-d H:i:s'),
                    'notes' => 'Pembayaran hutang karyawan',
                    'created_at' => Carbon::now()->subMonth(),
                ]);
            }
        }
    }
}
