<?php

declare(strict_types=1);

namespace Database\Seeders\DefaultSeed;

use App\Application\Audit\UseCases\ProcessAuditOutboxHandler;
use Illuminate\Database\Seeder;
use RuntimeException;

final class DefaultSixMonthSeeder extends Seeder
{
    public function run(ProcessAuditOutboxHandler $auditOutbox): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Default demo seed is restricted to local/testing environments.');
        }

        $this->call([
            DefaultIdentitySeeder::class,
            DefaultProductSeeder::class,
            DefaultServiceSeeder::class,
            DefaultServicePackageSeeder::class,
            DefaultProcurementSeeder::class,
            DefaultEmployeeFinanceSeeder::class,
            DefaultExpenseSeeder::class,
        ]);

        $summary = $auditOutbox->handle(5000, false, 3);

        if (($summary['failed'] ?? 0) > 0) {
            throw new RuntimeException('Default seed audit outbox materialization failed.');
        }
    }
}
