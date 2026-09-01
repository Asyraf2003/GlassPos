<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly\Support;

use App\Core\IdentityAccess\Role\Role;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CreateOnlyTransactionSeedContext
{
    private const CASHIER_EMAIL = 'kasir@gmail.com';

    public function cashierActorId(): string
    {
        $userId = DB::table('users')
            ->where('email', self::CASHIER_EMAIL)
            ->value('id');

        if (! is_int($userId) && ! is_string($userId)) {
            throw new RuntimeException('Transaction seed requires the local cashier fixture user.');
        }

        $actorId = trim((string) $userId);

        if ($actorId === '' || ! DB::table('actor_accesses')
            ->where('actor_id', $actorId)
            ->where('role', Role::KASIR)
            ->exists()) {
            throw new RuntimeException('Transaction seed requires kasir@gmail.com with cashier actor access.');
        }

        return $actorId;
    }

    /**
     * Return a stable product pool based on deterministic opening-stock seed rows.
     * The exposed qty_on_hand is the opening quantity, not mutable current stock,
     * so replay payload identity cannot drift after stock-out mutations.
     *
     * @return list<object{id:string,harga_jual:int,qty_on_hand:int}>
     */
    public function products(
        int $limit,
        int $minimumProducts,
        int $minimumOpeningCapacity = 0,
        bool $openingQuantityDesc = false,
    ): array {
        $query = DB::table('products')
            ->join('inventory_movements as opening', static function (JoinClause $join): void {
                $join->on('opening.product_id', '=', 'products.id')
                    ->where('opening.source_type', '=', 'opening_stock_seed')
                    ->where('opening.movement_type', '=', 'stock_in');
            })
            ->join('product_inventory', 'product_inventory.product_id', '=', 'products.id')
            ->join('product_inventory_costing', 'product_inventory_costing.product_id', '=', 'products.id')
            ->whereNull('products.deleted_at')
            ->where('products.harga_jual', '>', 0)
            ->where('opening.qty_delta', '>', 0)
            ->where('product_inventory_costing.avg_cost_rupiah', '>', 0);

        if ($openingQuantityDesc) {
            $query->orderByDesc('opening.qty_delta');
        }

        $rows = $query
            ->orderBy('products.id')
            ->limit($limit)
            ->get([
                'products.id',
                'products.harga_jual',
                'opening.qty_delta as opening_qty',
            ])
            ->map(static fn (object $row): object => (object) [
                'id' => (string) $row->id,
                'harga_jual' => (int) $row->harga_jual,
                'qty_on_hand' => (int) $row->opening_qty,
            ])
            ->values()
            ->all();

        if (count($rows) < $minimumProducts) {
            throw new RuntimeException(sprintf(
                'Transaction seed requires at least %d deterministic opening-stock products.',
                $minimumProducts,
            ));
        }

        $openingCapacity = array_sum(array_map(
            static fn (object $row): int => $row->qty_on_hand,
            $rows,
        ));

        if ($openingCapacity < $minimumOpeningCapacity) {
            throw new RuntimeException(sprintf(
                'Transaction seed requires at least %d opening store-stock units; found %d.',
                $minimumOpeningCapacity,
                $openingCapacity,
            ));
        }

        return $rows;
    }
}
