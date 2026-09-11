<?php

declare(strict_types=1);

namespace App\Adapters\Out\ProductCatalog\Concerns;

use Illuminate\Support\Facades\DB;
use Throwable;

trait SoftDeletesProducts
{
    public function softDelete(string $productId, ?string $actorId): bool
    {
        $started = false;
        $context = $this->changeContext->snapshot();

        $auditActorId = $context['actor_id'] ?? $actorId;
        $auditActorRole = $context['actor_role'];
        $auditReason = $context['reason'];
        $auditSourceChannel = $context['source_channel'] ?? 'web_admin';

        try {
            $this->transactions->begin();
            $started = true;

            $row = DB::table('products')
                ->where('id', $productId)
                ->whereNull('deleted_at')
                ->first([
                    'id',
                    'kode_barang',
                    'nama_barang',
                    'merek',
                    'ukuran',
                    'harga_jual',
                    'reorder_point_qty',
                    'critical_threshold_qty',
                ]);

            if ($row === null) {
                $this->transactions->rollBack();

                return false;
            }

            $occurredAt = now();

            DB::table('products')
                ->where('id', $productId)
                ->update([
                    'deleted_at' => $occurredAt,
                    'deleted_by_actor_id' => $auditActorId,
                ]);

            $revisionNo = $this->nextRevisionNo($productId);
            $snapshot = $this->toDeletedSnapshot(
                $row,
                $occurredAt->toDateTimeString(),
                $auditActorId,
            );

            $this->recordProductVersion(
                $productId,
                $revisionNo,
                'product_soft_deleted',
                $occurredAt,
                $auditActorId,
                $auditReason,
                $snapshot,
            );

            $this->recordProductAuditEvent(
                $productId,
                $revisionNo,
                'product_soft_deleted',
                $occurredAt,
                $auditActorId,
                $auditActorRole,
                $auditReason,
                $auditSourceChannel,
                $snapshot,
            );

            $this->transactions->commit();

            return true;
        } catch (Throwable $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            throw $e;
        } finally {
            $this->changeContext->clear();
        }
    }
}
