<?php

declare(strict_types=1);

namespace App\Adapters\Out\ProductCatalog\Concerns;

use App\Application\ProductCatalog\DTO\ProductTableQuery;
use Illuminate\Database\Query\Builder;

trait ProductTableOrdering
{
    private function applyTableSorting(Builder $query, ProductTableQuery $filters): Builder
    {
        if ($filters->sortBy() === 'relevance' && $filters->q() !== null) {
            return $this->applySearchRelevanceSorting($query, $filters->q());
        }

        $sortColumn = match ($filters->sortBy()) {
            'merek' => 'products.merek',
            'ukuran' => 'products.ukuran',
            'harga_jual' => 'products.harga_jual',
            'stok_saat_ini' => 'stok_saat_ini',
            default => 'products.nama_barang',
        };

        return $query
            ->orderBy($sortColumn, $filters->sortDir())
            ->orderBy('products.id');
    }

    private function applySearchRelevanceSorting(Builder $query, string $rawKeyword): Builder
    {
        $keyword = preg_replace('/\s+/', ' ', trim($rawKeyword)) ?? trim($rawKeyword);
        $keyword = mb_strtolower($keyword);
        $prefix = $keyword.'%';
        $contains = '%'.$keyword.'%';

        return $query
            ->orderByRaw(
                <<<'SQL'
CASE
    WHEN LOWER(COALESCE(products.kode_barang, '')) = ? THEN 0
    WHEN LOWER(COALESCE(products.kode_barang, '')) LIKE ? THEN 1
    WHEN LOWER(COALESCE(products.kode_barang, '')) LIKE ? THEN 2
    WHEN LOWER(products.nama_barang) = ? THEN 3
    WHEN LOWER(products.nama_barang) LIKE ? THEN 4
    WHEN LOWER(products.merek) = ? THEN 5
    WHEN LOWER(products.merek) LIKE ? THEN 6
    WHEN LOWER(products.nama_barang) LIKE ? THEN 7
    WHEN LOWER(products.merek) LIKE ? THEN 8
    ELSE 9
END
SQL,
                [
                    $keyword,
                    $prefix,
                    $contains,
                    $keyword,
                    $prefix,
                    $keyword,
                    $prefix,
                    $contains,
                    $contains,
                ],
            )
            ->orderBy('products.nama_barang')
            ->orderBy('products.id');
    }
}
