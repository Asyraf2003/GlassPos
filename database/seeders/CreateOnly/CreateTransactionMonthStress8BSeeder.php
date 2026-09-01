<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use App\Application\Note\UseCases\CreateTransactionWorkspaceHandler;
use Database\Seeders\CreateOnly\Support\CreateOnlySeeder;
use Database\Seeders\CreateOnly\Support\CreateOnlyTransactionSeedContext;
use Database\Seeders\CreateOnly\Support\CreateTransactionMonthStress8BPayloadFactory;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CreateTransactionMonthStress8BSeeder extends CreateOnlySeeder
{
    public function run(): void
    {
        $this->assertLocalOrTesting();

        /** @var CreateTransactionWorkspaceHandler $handler */
        $handler = app(CreateTransactionWorkspaceHandler::class);
        $context = new CreateOnlyTransactionSeedContext();
        $payloads = (new CreateTransactionMonthStress8BPayloadFactory(
            $context->cashierActorId(),
            $context->products(
                profile: 'month-stress-8b',
                limit: 200,
                minimumProducts: 40,
                minimumCapacity: 3000,
                quantityDesc: true,
            ),
        ))->payloads();

        $created = 0;
        $replayed = 0;

        foreach ($payloads as $payload) {
            $before = (int) DB::table('notes')->count();
            $result = $handler->handle($payload);

            if ($result->isFailure()) {
                throw new RuntimeException('Create transaction month-stress 8B seed failed: '.($result->message() ?? 'unknown failure'));
            }

            ((int) DB::table('notes')->count() > $before) ? $created++ : $replayed++;
        }

        $this->command?->info(sprintf(
            'create-only transaction month-stress-8b notes: planned=%d created=%d replayed=%d',
            count($payloads),
            $created,
            $replayed,
        ));
    }
}
