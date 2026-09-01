<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use App\Application\Note\UseCases\CreateTransactionWorkspaceHandler;
use Database\Seeders\CreateOnly\Support\CreateOnlySeeder;
use Database\Seeders\CreateOnly\Support\CreateOnlyTransactionSeedContext;
use Database\Seeders\CreateOnly\Support\CreateTransactionMonthStress10BPayloadFactory;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CreateTransactionMonthStress10BSeeder extends CreateOnlySeeder
{
    public function run(): void
    {
        $this->assertLocalOrTesting();

        /** @var CreateTransactionWorkspaceHandler $handler */
        $handler = app(CreateTransactionWorkspaceHandler::class);
        $context = new CreateOnlyTransactionSeedContext();
        $payloads = (new CreateTransactionMonthStress10BPayloadFactory(
            $context->cashierActorId(),
            $context->products(
                limit: 240,
                minimumProducts: 50,
                minimumOpeningCapacity: 4000,
                openingQuantityDesc: true,
            ),
        ))->payloads();

        $created = 0;
        $replayed = 0;

        foreach ($payloads as $payload) {
            $before = (int) DB::table('notes')->count();
            $result = $handler->handle($payload);

            if ($result->isFailure()) {
                throw new RuntimeException('Create transaction month-stress 10B seed failed: '.($result->message() ?? 'unknown failure'));
            }

            ((int) DB::table('notes')->count() > $before) ? $created++ : $replayed++;
        }

        $this->command?->info(sprintf(
            'create-only transaction month-stress-10b notes: planned=%d created=%d replayed=%d',
            count($payloads),
            $created,
            $replayed,
        ));
    }
}
