<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use App\Application\Note\UseCases\CreateTransactionWorkspaceHandler;
use App\Core\IdentityAccess\Role\Role;
use Database\Seeders\CreateOnly\Support\CreateOnlySeeder;
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
        $payloads = (new CreateTransactionMonthStress8BPayloadFactory(
            $this->resolveActorId(),
            $this->storeStockProducts(),
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

    private function resolveActorId(): string
    {
        $actorId = DB::table('actor_accesses')
            ->whereIn('role', [Role::KASIR, Role::ADMIN])
            ->orderByRaw("CASE WHEN role = ? THEN 0 ELSE 1 END", [Role::KASIR])
            ->orderBy('actor_id')
            ->value('actor_id');

        if (! is_string($actorId) || trim($actorId) === '') {
            throw new RuntimeException('CreateTransactionMonthStress8BSeeder requires cashier/admin actor access.');
        }

        return trim($actorId);
    }

    /** @return list<object{id:string,harga_jual:int,qty_on_hand:int}> */
    private function storeStockProducts(): array
    {
        $rows = DB::table('products')
            ->join('product_inventory', 'product_inventory.product_id', '=', 'products.id')
            ->join('product_inventory_costing', 'product_inventory_costing.product_id', '=', 'products.id')
            ->whereNull('products.deleted_at')
            ->where('products.harga_jual', '>', 0)
            ->where('product_inventory.qty_on_hand', '>', 0)
            ->where('product_inventory_costing.avg_cost_rupiah', '>', 0)
            ->orderByDesc('product_inventory.qty_on_hand')
            ->orderBy('products.id')
            ->limit(200)
            ->get(['products.id', 'products.harga_jual', 'product_inventory.qty_on_hand'])
            ->map(static fn (object $row): object => (object) [
                'id' => (string) $row->id,
                'harga_jual' => (int) $row->harga_jual,
                'qty_on_hand' => (int) $row->qty_on_hand,
            ])
            ->values()
            ->all();

        $capacity = array_sum(array_map(static fn (object $row): int => $row->qty_on_hand, $rows));

        if (count($rows) < 40 || $capacity < 3000) {
            throw new RuntimeException('CreateTransactionMonthStress8BSeeder requires at least 40 products and 3000 store-stock units.');
        }

        return $rows;
    }
}
