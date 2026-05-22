<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class EmployeeFinanceBaselineSeeder extends Seeder
{
    public function run(): void
    {
        $anchor = CarbonImmutable::create(2026, 4, 12, 12, 0, 0, 'Asia/Jakarta');
        $employeeCreatedAt = $anchor->subDays(30);
        $employeeUpdatedAt = $anchor->subDays(1);

        $employees = [
            [
                'id' => '11111111-1111-1111-1111-111111111001',
                'employee_name' => 'Andi Weekly',
                'phone' => '081210000001',
                'salary_basis_type' => 'weekly',
                'default_salary_amount' => 1200000,
                'employment_status' => 'active',
                'started_at' => '2025-11-01',
                'ended_at' => null,
            ],
            [
                'id' => '11111111-1111-1111-1111-111111111002',
                'employee_name' => 'Budi Weekly Debt',
                'phone' => '081210000002',
                'salary_basis_type' => 'weekly',
                'default_salary_amount' => 1350000,
                'employment_status' => 'active',
                'started_at' => '2025-10-15',
                'ended_at' => null,
            ],
            [
                'id' => '11111111-1111-1111-1111-111111111003',
                'employee_name' => 'Citra Weekly Paid',
                'phone' => '081210000003',
                'salary_basis_type' => 'weekly',
                'default_salary_amount' => 1100000,
                'employment_status' => 'active',
                'started_at' => '2025-08-01',
                'ended_at' => null,
            ],
            [
                'id' => '11111111-1111-1111-1111-111111111004',
                'employee_name' => 'Deni Monthly',
                'phone' => '081210000004',
                'salary_basis_type' => 'monthly',
                'default_salary_amount' => 4500000,
                'employment_status' => 'active',
                'started_at' => '2024-12-01',
                'ended_at' => null,
            ],
            [
                'id' => '11111111-1111-1111-1111-111111111005',
                'employee_name' => 'Eka Monthly Debt',
                'phone' => '081210000005',
                'salary_basis_type' => 'monthly',
                'default_salary_amount' => 4000000,
                'employment_status' => 'active',
                'started_at' => '2025-01-10',
                'ended_at' => null,
            ],
            [
                'id' => '11111111-1111-1111-1111-111111111006',
                'employee_name' => 'Farah Daily',
                'phone' => '081210000006',
                'salary_basis_type' => 'daily',
                'default_salary_amount' => 180000,
                'employment_status' => 'active',
                'started_at' => '2025-06-01',
                'ended_at' => null,
            ],
            [
                'id' => '11111111-1111-1111-1111-111111111007',
                'employee_name' => 'Gilang Daily Partial',
                'phone' => '081210000007',
                'salary_basis_type' => 'daily',
                'default_salary_amount' => 200000,
                'employment_status' => 'active',
                'started_at' => '2025-07-01',
                'ended_at' => null,
            ],
            [
                'id' => '11111111-1111-1111-1111-111111111008',
                'employee_name' => 'Hana Manual',
                'phone' => '081210000008',
                'salary_basis_type' => 'manual',
                'default_salary_amount' => null,
                'employment_status' => 'active',
                'started_at' => '2026-02-01',
                'ended_at' => null,
            ],
            [
                'id' => '11111111-1111-1111-1111-111111111009',
                'employee_name' => 'Irfan Manual Paid',
                'phone' => '081210000009',
                'salary_basis_type' => 'manual',
                'default_salary_amount' => null,
                'employment_status' => 'active',
                'started_at' => '2025-09-15',
                'ended_at' => null,
            ],
            [
                'id' => '11111111-1111-1111-1111-111111111010',
                'employee_name' => 'Joko Inactive',
                'phone' => '081210000010',
                'salary_basis_type' => 'monthly',
                'default_salary_amount' => 3800000,
                'employment_status' => 'inactive',
                'started_at' => '2024-05-01',
                'ended_at' => '2026-03-31',
            ],
            [
                'id' => '11111111-1111-1111-1111-111111111011',
                'employee_name' => 'Kiki Inactive Debt',
                'phone' => '081210000011',
                'salary_basis_type' => 'weekly',
                'default_salary_amount' => 950000,
                'employment_status' => 'inactive',
                'started_at' => '2025-01-01',
                'ended_at' => '2026-04-05',
            ],
            [
                'id' => '11111111-1111-1111-1111-111111111012',
                'employee_name' => 'Lala Active No Salary',
                'phone' => '081210000012',
                'salary_basis_type' => 'manual',
                'default_salary_amount' => null,
                'employment_status' => 'active',
                'started_at' => '2026-04-07',
                'ended_at' => null,
            ],
        ];

        foreach ($employees as $employee) {
            DB::table('employees')->updateOrInsert(
                ['id' => $employee['id']],
                array_merge($employee, [
                    'created_at' => $employeeCreatedAt,
                    'updated_at' => $employeeUpdatedAt,
                ])
            );

            $this->seedEmployeeCreatedVersion($employee, $employeeCreatedAt);
        }

        $payrolls = [
            ['id' => 'pay-v1-001', 'employee_id' => '11111111-1111-1111-1111-111111111001', 'amount' => 1200000, 'disbursement_date' => '2026-04-11 09:00:00', 'mode' => 'weekly', 'notes' => 'Gaji minggu kedua April'],
            ['id' => 'pay-v1-002', 'employee_id' => '11111111-1111-1111-1111-111111111002', 'amount' => 1350000, 'disbursement_date' => '2026-04-11 09:15:00', 'mode' => 'weekly', 'notes' => 'Gaji minggu kedua April'],
            ['id' => 'pay-v1-003', 'employee_id' => '11111111-1111-1111-1111-111111111003', 'amount' => 1100000, 'disbursement_date' => '2026-04-11 09:30:00', 'mode' => 'weekly', 'notes' => 'Gaji minggu kedua April'],
            ['id' => 'pay-v1-004', 'employee_id' => '11111111-1111-1111-1111-111111111004', 'amount' => 4500000, 'disbursement_date' => '2026-04-10 10:00:00', 'mode' => 'monthly', 'notes' => 'Gaji bulanan April'],
            ['id' => 'pay-v1-005', 'employee_id' => '11111111-1111-1111-1111-111111111005', 'amount' => 4000000, 'disbursement_date' => '2026-04-10 10:15:00', 'mode' => 'monthly', 'notes' => 'Gaji bulanan April'],
            ['id' => 'pay-v1-006', 'employee_id' => '11111111-1111-1111-1111-111111111006', 'amount' => 180000, 'disbursement_date' => '2026-04-07 17:00:00', 'mode' => 'daily', 'notes' => 'Gaji harian Selasa'],
            ['id' => 'pay-v1-007', 'employee_id' => '11111111-1111-1111-1111-111111111006', 'amount' => 180000, 'disbursement_date' => '2026-04-08 17:00:00', 'mode' => 'daily', 'notes' => 'Gaji harian Rabu'],
            ['id' => 'pay-v1-008', 'employee_id' => '11111111-1111-1111-1111-111111111006', 'amount' => 180000, 'disbursement_date' => '2026-04-09 17:00:00', 'mode' => 'daily', 'notes' => 'Gaji harian Kamis'],
            ['id' => 'pay-v1-009', 'employee_id' => '11111111-1111-1111-1111-111111111006', 'amount' => 180000, 'disbursement_date' => '2026-04-10 17:00:00', 'mode' => 'daily', 'notes' => 'Gaji harian Jumat'],
            ['id' => 'pay-v1-010', 'employee_id' => '11111111-1111-1111-1111-111111111007', 'amount' => 200000, 'disbursement_date' => '2026-04-07 17:10:00', 'mode' => 'daily', 'notes' => 'Gaji harian Selasa'],
            ['id' => 'pay-v1-011', 'employee_id' => '11111111-1111-1111-1111-111111111007', 'amount' => 200000, 'disbursement_date' => '2026-04-08 17:10:00', 'mode' => 'daily', 'notes' => 'Gaji harian Rabu'],
            ['id' => 'pay-v1-012', 'employee_id' => '11111111-1111-1111-1111-111111111007', 'amount' => 200000, 'disbursement_date' => '2026-04-10 17:10:00', 'mode' => 'daily', 'notes' => 'Gaji harian Jumat'],
        ];

        foreach ($payrolls as $row) {
            DB::table('payroll_disbursements')->updateOrInsert(
                ['id' => $row['id']],
                array_merge($row, [
                    'created_at' => $row['disbursement_date'],
                    'updated_at' => $row['disbursement_date'],
                ])
            );
        }

        $debts = [
            ['id' => 'debt-v1-001', 'employee_id' => '11111111-1111-1111-1111-111111111002', 'total_debt' => 1500000, 'remaining_balance' => 900000, 'status' => 'unpaid', 'notes' => 'Kasbon keluarga', 'created_at' => '2026-04-06 08:00:00'],
            ['id' => 'debt-v1-002', 'employee_id' => '11111111-1111-1111-1111-111111111003', 'total_debt' => 800000, 'remaining_balance' => 0, 'status' => 'paid', 'notes' => 'Talangan servis motor', 'created_at' => '2026-04-06 09:00:00'],
            ['id' => 'debt-v1-003', 'employee_id' => '11111111-1111-1111-1111-111111111005', 'total_debt' => 2000000, 'remaining_balance' => 2000000, 'status' => 'unpaid', 'notes' => 'Talangan kebutuhan rumah', 'created_at' => '2026-04-07 10:00:00'],
            ['id' => 'debt-v1-004', 'employee_id' => '11111111-1111-1111-1111-111111111007', 'total_debt' => 600000, 'remaining_balance' => 400000, 'status' => 'unpaid', 'notes' => 'Pinjaman transport operasional', 'created_at' => '2026-04-07 07:30:00'],
            ['id' => 'debt-v1-005', 'employee_id' => '11111111-1111-1111-1111-111111111009', 'total_debt' => 500000, 'remaining_balance' => 0, 'status' => 'paid', 'notes' => 'Talangan pribadi', 'created_at' => '2026-04-06 11:00:00'],
            ['id' => 'debt-v1-006', 'employee_id' => '11111111-1111-1111-1111-111111111011', 'total_debt' => 900000, 'remaining_balance' => 150000, 'status' => 'unpaid', 'notes' => 'Sisa kasbon sebelum nonaktif', 'created_at' => '2026-03-28 09:00:00'],
        ];

        foreach ($debts as $row) {
            DB::table('employee_debts')->updateOrInsert(
                ['id' => $row['id']],
                array_merge($row, [
                    'updated_at' => $row['created_at'],
                ])
            );
        }

        $payments = [
            ['id' => 'debt-pay-v1-001', 'employee_debt_id' => 'debt-v1-001', 'amount' => 300000, 'payment_date' => '2026-04-08 18:00:00', 'notes' => 'Potong gaji tahap 1'],
            ['id' => 'debt-pay-v1-002', 'employee_debt_id' => 'debt-v1-001', 'amount' => 300000, 'payment_date' => '2026-04-11 18:30:00', 'notes' => 'Potong gaji tahap 2'],
            ['id' => 'debt-pay-v1-003', 'employee_debt_id' => 'debt-v1-002', 'amount' => 300000, 'payment_date' => '2026-04-09 16:00:00', 'notes' => 'Pembayaran awal'],
            ['id' => 'debt-pay-v1-004', 'employee_debt_id' => 'debt-v1-002', 'amount' => 500000, 'payment_date' => '2026-04-11 16:30:00', 'notes' => 'Pelunasan hutang'],
            ['id' => 'debt-pay-v1-005', 'employee_debt_id' => 'debt-v1-004', 'amount' => 200000, 'payment_date' => '2026-04-10 18:10:00', 'notes' => 'Potong gaji harian'],
            ['id' => 'debt-pay-v1-006', 'employee_debt_id' => 'debt-v1-005', 'amount' => 500000, 'payment_date' => '2026-04-09 15:00:00', 'notes' => 'Pelunasan manual admin'],
            ['id' => 'debt-pay-v1-007', 'employee_debt_id' => 'debt-v1-006', 'amount' => 750000, 'payment_date' => '2026-04-04 12:00:00', 'notes' => 'Potong sebelum nonaktif'],
        ];

        foreach ($payments as $row) {
            DB::table('employee_debt_payments')->updateOrInsert(
                ['id' => $row['id']],
                array_merge($row, [
                    'created_at' => $row['payment_date'],
                    'updated_at' => $row['payment_date'],
                ])
            );
        }

        $this->command?->info('EmployeeFinanceBaselineSeeder selesai: 12 employee, 12 payroll, 6 debt, 7 payment.');
    }

    private function seedEmployeeCreatedVersion(array $employee, CarbonImmutable $occurredAt): void
    {
        $versionId = 'seed-employee-version-'.$employee['id'];
        $auditEventId = 'seed-audit-event-'.$employee['id'];
        $snapshotId = 'seed-audit-snapshot-after-'.$employee['id'];
        $snapshot = $this->employeeSnapshot($employee);
        $metadata = [
            'seed_source' => self::class,
            'seed_kind' => 'baseline_employee_created',
        ];

        DB::table('employee_versions')->updateOrInsert(
            [
                'employee_id' => $employee['id'],
                'revision_no' => 1,
            ],
            [
                'id' => $versionId,
                'event_name' => 'employee_created',
                'changed_by_actor_id' => null,
                'change_reason' => 'Seed baseline employee finance.',
                'changed_at' => $occurredAt,
                'snapshot_json' => json_encode($snapshot, JSON_THROW_ON_ERROR),
            ]
        );

        DB::table('audit_events')->updateOrInsert(
            [
                'id' => $auditEventId,
            ],
            [
                'bounded_context' => 'employee_finance',
                'aggregate_type' => 'employee',
                'aggregate_id' => $employee['id'],
                'event_name' => 'employee_created',
                'actor_id' => null,
                'actor_role' => null,
                'reason' => 'Seed baseline employee finance.',
                'source_channel' => 'seeder',
                'request_id' => null,
                'correlation_id' => 'employee-finance-baseline-seeder',
                'occurred_at' => $occurredAt,
                'metadata_json' => json_encode($metadata, JSON_THROW_ON_ERROR),
            ]
        );

        DB::table('audit_event_snapshots')->updateOrInsert(
            [
                'audit_event_id' => $auditEventId,
                'snapshot_kind' => 'after',
            ],
            [
                'id' => $snapshotId,
                'payload_json' => json_encode($snapshot, JSON_THROW_ON_ERROR),
                'created_at' => $occurredAt,
            ]
        );
    }

    private function employeeSnapshot(array $employee): array
    {
        return [
            'employee_name' => $employee['employee_name'],
            'phone' => $employee['phone'],
            'salary_basis_type' => $employee['salary_basis_type'],
            'default_salary_amount' => $employee['default_salary_amount'],
            'employment_status' => $employee['employment_status'],
            'started_at' => $employee['started_at'],
            'ended_at' => $employee['ended_at'],
        ];
    }
}
