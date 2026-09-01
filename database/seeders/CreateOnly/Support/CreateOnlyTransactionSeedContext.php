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
     * Reconstruct the product snapshot that existed immediately before a seed
     * profile started. Current stock is adjusted only by the net stock movement
     * already produced by that same profile, so reruns rebuild the exact same
     * payload pool while still respecting prerequisite-profile consumption.
     *
     * @return list<object{id:string,harga_jual:int,qty_on_hand:int}>
     */
    public function products(
        string $profile,
        int $limit,
        int $minimumProducts,
        int $minimumAvailableQuantity = 1,
        int $minimumCapacity = 0,
        bool $quantityDesc = false,
    ): array {
        $ownNetMovements = DB::table('idempotency_records as idem')
            ->join('work_items as work_item', 'work_item.note_id', '=', 'idem.result_note_id')
            ->join('work_item_store_stock_lines as stock_line', 'stock_line.work_item_id', '=', 'work_item.id')
            ->join('inventory_movements as movement', static function (JoinClause $join): void {
                $join->on('movement.source_id', '=', 'stock_line.id')
                    ->whereIn('movement.source_type', [
                        'work_item_store_stock_line',
                        'work_item_store_stock_line_reversal',
                    ]);
            })
            ->where('idem.operation', 'create_transaction_workspace')
            ->where('idem.status', 'succeeded')
            ->where('idem.idempotency_key', 'like', CreateOnlyTransactionSeedIdentity::prefix($profile).'-%')
            ->groupBy('stock_line.product_id')
            ->select('stock_line.product_id')
            ->selectRaw('COALESCE(SUM(movement.qty_delta), 0) as own_net_qty_delta');

        $availableSql = '(product_inventory.qty_on_hand - COALESCE(own_seed_movement.own_net_qty_delta, 0))';

        $query = DB::table('products')
            ->join('product_inventory', 'product_inventory.product_id', '=', 'products.id')
            ->join('product_inventory_costing', 'product_inventory_costing.product_id', '=', 'products.id')
            ->leftJoinSub($ownNetMovements, 'own_seed_movement', static function (JoinClause $join): void {
                $join->on('own_seed_movement.product_id', '=', 'products.id');
            })
            ->whereNull('products.deleted_at')
            ->where('products.harga_jual', '>', 0)
            ->where('product_inventory_costing.avg_cost_rupiah', '>', 0)
            ->whereRaw($availableSql.' >= ?', [$minimumAvailableQuantity]);

        if ($quantityDesc) {
            $query->orderByRaw($availableSql.' DESC');
        }

        $rows = $query
            ->orderBy('products.id')
            ->limit($limit)
            ->get([
                'products.id',
                'products.harga_jual',
                DB::raw($availableSql.' as seed_available_qty'),
            ])
            ->map(static fn (object $row): object => (object) [
                'id' => (string) $row->id,
                'harga_jual' => (int) $row->harga_jual,
                'qty_on_hand' => (int) $row->seed_available_qty,
            ])
            ->values()
            ->all();

        if (count($rows) < $minimumProducts) {
            throw new RuntimeException(sprintf(
                'Transaction seed profile %s requires at least %d stable products; found %d.',
                $profile,
                $minimumProducts,
                count($rows),
            ));
        }

        $capacity = array_sum(array_map(
            static fn (object $row): int => $row->qty_on_hand,
            $rows,
        ));

        if ($capacity < $minimumCapacity) {
            throw new RuntimeException(sprintf(
                'Transaction seed profile %s requires at least %d store-stock units; found %d.',
                $profile,
                $minimumCapacity,
                $capacity,
            ));
        }

        return $rows;
    }
}
