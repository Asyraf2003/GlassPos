<?php

declare(strict_types=1);

namespace App\Application\ProductCatalog\Services;

use App\Application\ProductCatalog\Context\ProductChangeContext;
use App\Application\ProductCatalog\UseCases\SoftDeleteProductHandler;
use App\Application\ProductCatalog\UseCases\UpdateProductHandler;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class BulkProductMaintenanceRunner
{
    public function __construct(
        private readonly UpdateProductHandler $updates,
        private readonly SoftDeleteProductHandler $deletes,
        private readonly ProductChangeContext $context,
    ) {
    }

    /** @param array<int, array<string,string>> $rows */
    public function run(array $rows, string $actorId, string $actorRole): void
    {
        $mutationIds = array_values(array_map(
            static fn (array $row): string => $row['product_id'],
            array_filter(
                $rows,
                static fn (array $row): bool => in_array(
                    strtoupper($row['action']),
                    ['UPDATE_PRICE', 'DELETE'],
                    true,
                ),
            ),
        ));

        DB::transaction(function () use ($rows, $actorId, $actorRole, $mutationIds): void {
            $locked = DB::table('products')
                ->whereIn('id', $mutationIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($rows as $row) {
                $action = strtoupper($row['action']);

                if (! in_array($action, ['UPDATE_PRICE', 'DELETE'], true)) {
                    continue;
                }

                $product = $locked->get($row['product_id']);

                if ($product === null || $product->deleted_at !== null) {
                    throw new RuntimeException("Product {$row['product_id']} berubah setelah validasi.");
                }

                if ((int) $product->harga_jual !== (int) $row['expected_old_price']) {
                    throw new RuntimeException("Harga product {$row['product_id']} berubah setelah validasi.");
                }

                $this->context->set(
                    $actorId,
                    $actorRole,
                    'cli_bulk_product_maintenance',
                    $row['reason'],
                );

                try {
                    $result = $action === 'UPDATE_PRICE'
                        ? $this->updatePrice($product, (int) $row['new_price'])
                        : $this->deletes->handle($row['product_id'], $actorId);

                    if ($result->isFailure()) {
                        throw new RuntimeException(
                            $result->message() ?? "Mutasi {$row['product_id']} gagal."
                        );
                    }
                } finally {
                    $this->context->clear();
                }
            }
        });
    }

    private function updatePrice(object $product, int $newPrice): \App\Application\Shared\DTO\Result
    {
        return $this->updates->handle(
            (string) $product->id,
            $product->kode_barang !== null ? (string) $product->kode_barang : null,
            (string) $product->nama_barang,
            (string) $product->merek,
            $product->ukuran !== null ? (int) $product->ukuran : null,
            $newPrice,
            $product->reorder_point_qty !== null ? (int) $product->reorder_point_qty : null,
            $product->critical_threshold_qty !== null ? (int) $product->critical_threshold_qty : null,
        );
    }
}
