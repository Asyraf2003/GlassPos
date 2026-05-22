<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

final class FinancialCorrectionSeeder extends Seeder
{
    public function run(): void
    {
        if ($this->alreadySeeded()) {
            $this->command?->info('FinancialCorrectionSeeder dilewati: koreksi finansial baseline sudah ada.');

            return;
        }

        $now = Carbon::now();
        
        // Ambil ID Admin yang valid sebagai pelaku koreksi
        $adminId = '1';
        $admin = DB::table('users')->where('email', 'admin@gmail.com')->first();
        if ($admin) {
            $adminId = (string) $admin->id;
        }

        // ==========================================
        // 1. SEED: employee_debt_adjustments
        // ==========================================
        
        // Ambil 5 data hutang karyawan secara acak yang masih punya sisa saldo
        $debts = DB::table('employee_debts')
            ->where('remaining_balance', '>', 0)
            ->inRandomOrder()
            ->limit(15)
            ->get();

        $debtAdjustments = [];

        foreach ($debts as $debt) {
            $debtId = (string) $debt->id;
            
            // Kita simulasikan tipe penyesuaian 'pemotongan' (pengurangan hutang karena salah input)
            // Nilai koreksi maksimal 10% dari total hutang agar logis
            $amount = (int) ($debt->total_debt * 0.1); 
            
            $beforeTotal = (int) $debt->total_debt;
            $afterTotal = $beforeTotal - $amount;
            
            $beforeRemaining = (int) $debt->remaining_balance;
            $afterRemaining = $beforeRemaining - $amount;

            $debtAdjustments[] = [
                'id' => Str::uuid()->toString(),
                'employee_debt_id' => $debtId,
                'adjustment_type' => 'reduction',
                'amount' => $amount,
                'reason' => 'Koreksi salah input nominal awal hutang',
                'performed_by_actor_id' => $adminId,
                'before_total_debt' => $beforeTotal,
                'after_total_debt' => $afterTotal,
                'before_remaining_balance' => $beforeRemaining,
                'after_remaining_balance' => $afterRemaining,
                'created_at' => clone $now,
                'updated_at' => clone $now,
            ];

            // Update tabel utama agar saldo sinkron dengan tabel history adjustment
            DB::table('employee_debts')
                ->where('id', $debtId)
                ->update([
                    'total_debt' => $afterTotal,
                    'remaining_balance' => $afterRemaining
                ]);
        }

        if ($debtAdjustments !== []) {
            DB::table('employee_debt_adjustments')->insert($debtAdjustments);
            $this->command->info('Berhasil menanamkan ' . count($debtAdjustments) . ' data penyesuaian hutang karyawan.');
        }

        // ==========================================
        // 2. SEED: payroll_disbursement_reversals
        // ==========================================
        
        // Ambil 3 data pencairan gaji acak untuk dibatalkan (reversed)
        $payrolls = DB::table('payroll_disbursements')
            ->inRandomOrder()
            ->limit(10)
            ->get();

        $reversals = [];

        foreach ($payrolls as $payroll) {
            $reversals[] = [
                'id' => Str::uuid()->toString(),
                'payroll_disbursement_id' => (string) $payroll->id,
                'reason' => 'Dibatalkan: Salah transfer ke rekening karyawan',
                'performed_by_actor_id' => $adminId,
                'created_at' => clone $now,
                'updated_at' => clone $now,
            ];
        }

        if ($reversals !== []) {
            DB::table('payroll_disbursement_reversals')->insert($reversals);
            $this->command->info('Berhasil menanamkan ' . count($reversals) . ' data pembatalan pencairan gaji.');
        }
    }

    private function alreadySeeded(): bool
    {
        return DB::table('employee_debt_adjustments')->exists()
            || DB::table('payroll_disbursement_reversals')->exists();
    }
}
