<?php

declare(strict_types=1);

namespace App\Adapters\Out\ProductCatalog;

use App\Application\ProductCatalog\DTO\ProductIdentityMerge;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\ClockPort;
use App\Ports\Out\ProductCatalog\ProductIdentityMergePort;
use Illuminate\Support\Facades\DB;

final class DatabaseProductIdentityMergeAdapter implements ProductIdentityMergePort
{
    public function __construct(private readonly ClockPort $clock) {}

    public function findBySource(string $sourceProductId): ?ProductIdentityMerge
    {
        $row = DB::table('product_identity_merges')->where('source_product_id', $sourceProductId)->first();
        return $row === null ? null : new ProductIdentityMerge(
            (string) $row->id, (string) $row->source_product_id, (string) $row->canonical_product_id,
            (string) $row->actor_id, (string) $row->reason, (string) $row->prior_stock_transfer_source_id,
        );
    }

    public function recordTransferredMerge(ProductIdentityMerge $merge): bool
    {
        $products = DB::table('products')->whereIn('id', [$merge->sourceProductId, $merge->canonicalProductId])
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $source = $products->get($merge->sourceProductId);
        $target = $products->get($merge->canonicalProductId);
        if ($source === null || $target === null || $source->deleted_at === null || $target->deleted_at !== null) {
            throw new DomainException('Adoption requires a retired source and an active canonical product.');
        }
        if (! DB::table('actor_accesses')->where('actor_id', $merge->actorId)->exists()) {
            throw new DomainException('Merge actor is not registered.');
        }
        if ($this->findBySource($merge->canonicalProductId) !== null) {
            throw new DomainException('Target already has a canonical successor.');
        }
        $existing = $this->findBySource($merge->sourceProductId);
        if ($existing !== null) {
            if ($existing != $merge) {
                throw new DomainException('Source already has a different immutable merge operation.');
            }
            return false;
        }
        if (DB::table('product_identity_merges')->where('id', $merge->id)
            ->orWhere('prior_stock_transfer_source_id', $merge->priorStockTransferSourceId)->exists()) {
            throw new DomainException('Merge operation or prior transfer is already claimed.');
        }
        $this->assertPriorTransfer($merge);
        DB::table('product_identity_merges')->insert([
            'id' => $merge->id, 'source_product_id' => $merge->sourceProductId,
            'canonical_product_id' => $merge->canonicalProductId, 'actor_id' => $merge->actorId,
            'reason' => $merge->reason, 'occurred_at' => $this->clock->now(),
            'prior_stock_transfer_source_id' => $merge->priorStockTransferSourceId,
        ]);
        return true;
    }

    public function assertSourceDrained(ProductIdentityMerge $merge): void
    {
        // A locking read observes ledger writes committed while waiting for invoice locks.
        $rows = DB::table('inventory_movements')->where('product_id', $merge->sourceProductId)
            ->orderBy('id')->lockForUpdate()->get(['qty_delta', 'total_cost_rupiah']);
        if ((int) $rows->sum('qty_delta') !== 0 || (int) $rows->sum('total_cost_rupiah') !== 0) {
            throw new DomainException('Source still owns stock/value; prior transfer is not a completed stock migration.');
        }
    }

    private function assertPriorTransfer(ProductIdentityMerge $merge): void
    {
        $rows = DB::table('inventory_movements')->where('source_type', 'product_master_merge')
            ->where('source_id', $merge->priorStockTransferSourceId)->orderBy('id')->lockForUpdate()->get();
        $out = $rows->firstWhere('product_id', $merge->sourceProductId);
        $in = $rows->firstWhere('product_id', $merge->canonicalProductId);
        if ($rows->count() !== 2 || $out === null || $in === null
            || $out->movement_type !== 'stock_out' || $in->movement_type !== 'stock_in'
            || (int) $out->qty_delta >= 0 || (int) $in->qty_delta <= 0
            || (int) $out->qty_delta + (int) $in->qty_delta !== 0
            || (int) $out->total_cost_rupiah + (int) $in->total_cost_rupiah !== 0) {
            throw new DomainException('Prior transfer must prove the explicitly supplied A -> B stock and value transfer.');
        }
    }
}
