<?php

declare(strict_types=1);

namespace App\Application\ProductCatalog\Services;

use App\Application\ProductCatalog\Context\ProductChangeContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class BulkProductMaintenanceRunner
{
    public function __construct(
        private readonly BulkProductMaintenanceRowApplier $applier,
        private readonly ProductChangeContext $context,
    ) {
    }

    /** @param array<int, array<string,string>> $rows */
    public function run(
        array $rows,
        string $actorId,
        string $actorRole,
        bool $uppercaseMaster = false,
    ): void {
        $mutationIds = array_values(array_map(
            static fn (array $row): string => $row['product_id'],
            array_filter(
                $rows,
                static fn (array $row): bool => $uppercaseMaster
                    || in_array(strtoupper($row['action']), ['UPDATE_PRICE', 'DELETE'], true),
            ),
        ));

        DB::transaction(function () use (
            $rows,
            $actorId,
            $actorRole,
            $uppercaseMaster,
            $mutationIds,
        ): void {
            $locked = DB::table('products')
                ->whereIn('id', $mutationIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($rows as $row) {
                $action = strtoupper($row['action']);

                if (! $uppercaseMaster
                    && ! in_array($action, ['UPDATE_PRICE', 'DELETE'], true)) {
                    continue;
                }

                $product = $locked->get($row['product_id']);

                if ($product === null || $product->deleted_at !== null) {
                    throw new RuntimeException(
                        "Product {$row['product_id']} berubah setelah validasi."
                    );
                }

                if ((int) $product->harga_jual !== (int) $row['expected_old_price']) {
                    throw new RuntimeException(
                        "Harga product {$row['product_id']} berubah setelah validasi."
                    );
                }

                try {
                    $this->applier->apply(
                        $row,
                        $product,
                        $actorId,
                        $actorRole,
                        $uppercaseMaster,
                    );
                } finally {
                    $this->context->clear();
                }
            }
        });
    }
}
