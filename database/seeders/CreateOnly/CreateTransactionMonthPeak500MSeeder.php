<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use App\Application\Note\UseCases\CreateTransactionWorkspaceHandler;
use App\Core\IdentityAccess\Role\Role;
use Database\Seeders\CreateOnly\Support\CreateOnlySeeder;
use Database\Seeders\CreateOnly\Support\CreateTransactionMonthPeak500MPayloadFactory;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CreateTransactionMonthPeak500MSeeder extends CreateOnlySeeder
{
    public function run(): void
    {
        $this->assertLocalOrTesting();

        /** @var CreateTransactionWorkspaceHandler $handler */
        $handler = app(CreateTransactionWorkspaceHandler::class);
        $payloads = (new CreateTransactionMonthPeak500MPayloadFactory(
            $this->resolveActorId(),
            $this->storeStockProducts(),
        ))->payloads();

        $created = 0;
        $replayed = 0;

        foreach ($payloads as $payload) {
            $before = (int) DB::table('notes')->count();
            $result = $handler->handle($payload);

            if ($result->isFailure()) {
                throw new RuntimeException('Create transaction month-peak 500M seed failed: '.($result->message() ?? 'unknown failure'));
            }

            if ((int) DB::table('notes')->count() > $before) {
                $created++;
            } else {
                $replayed++;
            }
        }

        $this->command?->info(sprintf(
            'create-only transaction month-peak-500m notes: planned=%d created=%d replayed=%d',
            count($payloads),
            $created,
            $replayed,
        ));
    }

    private function resolveActorId(): string
    {
        $actorId = DB::table('actor_accesses')
            ->whereIn('role', [Role::KASIR, Role::ADMIN])
            ->orderByRaw("CASE WHEN role = ? THEN 0 ELSE 1 END", [Role::KASIR])
            ->orderBy('actor_id')
            ->value('actor_id');

        if (! is_string($actorId) || trim($actorId) === '') {
            throw new RuntimeException('CreateTransactionMonthPeak500MSeeder requires cashier/admin actor access.');
        }

        return trim($actorId);
    }

    /** @return list<object{id:string,harga_jual:int}> */
    private function storeStockProducts(): array
    {
        $rows = DB::table('products')
            ->join('product_inventory', 'product_inventory.product_id', '=', 'products.id')
            ->join('product_inventory_costing', 'product_inventory_costing.product_id', '=', 'products.id')
            ->where('product_inventory.qty_on_hand', '>=', 20)
            ->where('products.harga_jual', '>', 0)
            ->orderBy('products.id')
            ->limit(80)
            ->get(['products.id', 'products.harga_jual'])
            ->map(static fn (object $row): object => (object) ['id' => (string) $row->id, 'harga_jual' => (int) $row->harga_jual])
            ->values()
            ->all();

        if (count($rows) < 24) {
            throw new RuntimeException('CreateTransactionMonthPeak500MSeeder requires at least 24 stocked products with qty_on_hand >= 20.');
        }

        return $rows;
    }
}
