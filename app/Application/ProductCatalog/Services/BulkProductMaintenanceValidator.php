<?php

declare(strict_types=1);

namespace App\Application\ProductCatalog\Services;

use Illuminate\Support\Facades\DB;

final class BulkProductMaintenanceValidator
{
    public const ACTIONS = [
        'UPDATE_PRICE',
        'DELETE',
        'SKIP_UNKNOWN_PRICE',
        'UNCHANGED',
    ];

    public function __construct(
        private readonly BulkProductMaintenanceRowValidator $rowValidator,
    ) {
    }

    /** @param array<int, array<string, string>> $rows
     *  @return array{errors:array<int,string>,counts:array<string,int>,actor_role:?string}
     */
    public function validate(array $rows, string $actorId): array
    {
        $errors = [];
        $counts = array_fill_keys(self::ACTIONS, 0);
        $actorRole = DB::table('actor_accesses')->where('actor_id', $actorId)->value('role');

        if (! is_string($actorRole) || trim($actorRole) === '') {
            $errors[] = 'Actor tidak memiliki role pada actor_accesses.';
        }

        $ids = array_map(static fn (array $row): string => trim($row['product_id']), $rows);
        $products = DB::table('products')
            ->whereIn('id', array_values(array_unique($ids)))
            ->get()
            ->keyBy('id');
        $seen = [];

        foreach ($rows as $row) {
            $line = (int) $row['__line'];
            $id = trim($row['product_id']);
            $action = strtoupper(trim($row['action']));

            if ($id === '') {
                $errors[] = "Baris {$line}: product_id kosong.";
                continue;
            }

            if (isset($seen[$id])) {
                $errors[] = "Baris {$line}: product_id {$id} duplikat.";
                continue;
            }
            $seen[$id] = true;

            if (! in_array($action, self::ACTIONS, true)) {
                $errors[] = "Baris {$line}: action {$action} tidak valid.";
                continue;
            }
            $counts[$action]++;

            $product = $products->get($id);

            if ($product === null) {
                $errors[] = "Baris {$line}: product {$id} tidak ditemukan.";
                continue;
            }

            if ($product->deleted_at !== null) {
                $errors[] = "Baris {$line}: product {$id} sudah dihapus.";
            }

            $this->rowValidator->validate($row, $product, $action, $line, $errors);
        }

        return [
            'errors' => $errors,
            'counts' => $counts,
            'actor_role' => is_string($actorRole) ? trim($actorRole) : null,
        ];
    }
}
