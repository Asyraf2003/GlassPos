<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use App\Application\Note\UseCases\CreateTransactionWorkspaceHandler;
use Database\Seeders\CreateOnly\Support\CreateOnlySeeder;
use Database\Seeders\CreateOnly\Support\CreateOnlyTransactionSeedContext;
use Database\Seeders\CreateOnly\Support\CreateTransactionMonthNormalPayloadFactory;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CreateTransactionMonthNormalSeeder extends CreateOnlySeeder
{
    public function run(): void
    {
        $this->assertLocalOrTesting();

        /** @var CreateTransactionWorkspaceHandler $handler */
        $handler = app(CreateTransactionWorkspaceHandler::class);
        $context = new CreateOnlyTransactionSeedContext();
        $payloads = (new CreateTransactionMonthNormalPayloadFactory(
            $context->cashierActorId(),
            $context->products(limit: 8, minimumProducts: 4),
        ))->payloads();

        $created = 0;
        $replayed = 0;

        foreach ($payloads as $payload) {
            $before = (int) DB::table('notes')->count();
            $result = $handler->handle($payload);

            if ($result->isFailure()) {
                throw new RuntimeException('Create transaction month-normal seed failed: '.($result->message() ?? 'unknown failure'));
            }

            if ((int) DB::table('notes')->count() > $before) {
                $created++;
            } else {
                $replayed++;
            }
        }

        $this->command?->info(sprintf(
            'create-only transaction month-normal notes: planned=%d created=%d replayed=%d',
            count($payloads),
            $created,
            $replayed,
        ));
    }
}
