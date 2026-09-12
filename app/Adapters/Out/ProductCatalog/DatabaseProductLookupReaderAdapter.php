<?php

declare(strict_types=1);

namespace App\Adapters\Out\ProductCatalog;

use App\Ports\Out\ProductCatalog\ProductLookupReaderPort;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class DatabaseProductLookupReaderAdapter implements ProductLookupReaderPort
{
    public function __construct(
        private readonly ProductLookupRowMapper $rowMapper,
    ) {}

    public function search(string $query, int $limit = self::DEFAULT_LIMIT, bool $onlyInStock = false): array
    {
        $builder = $this->baseQuery();
        $query = trim($query);

        if ($query !== '') {
            $this->applySearch($builder, $query);
        }

        if ($onlyInStock) {
            $builder->where('product_inventory.qty_on_hand', '>', 0);
        }

        $rows = $this->applyOrdering($builder)
            ->limit($this->boundedLimit($limit))
            ->get()
            ->all();

        return array_map($this->rowMapper->map(...), $rows);
    }

    public function findByIds(array $ids): array
    {
        $rows = $this->baseQuery()
            ->whereIn('products.id', $ids)
            ->get()
            ->all();

        return array_map($this->rowMapper->map(...), $rows);
    }

    private function baseQuery(): Builder
    {
        return DB::table('products')
            ->leftJoin('product_inventory', 'product_inventory.product_id', '=', 'products.id')
            ->whereNull('products.deleted_at')
            ->select([
                'products.id',
                'products.kode_barang',
                'products.nama_barang',
                'products.merek',
                'products.ukuran',
                'products.harga_jual',
                DB::raw('COALESCE(product_inventory.qty_on_hand, 0) as available_stock'),
            ]);
    }

    private function applySearch(Builder $query, string $keyword): void
    {
        $normalized = $this->normalizeForSearch($keyword);

        $query->where(function (Builder $builder) use ($keyword, $normalized): void {
            $builder
                ->where('products.kode_barang', 'like', '%'.$keyword.'%')
                ->orWhere('products.nama_barang', 'like', '%'.$keyword.'%')
                ->orWhere('products.merek', 'like', '%'.$keyword.'%')
                ->orWhere('products.nama_barang_normalized', 'like', '%'.$normalized.'%')
                ->orWhere('products.merek_normalized', 'like', '%'.$normalized.'%');
        });
    }

    private function applyOrdering(Builder $query): Builder
    {
        return $query
            ->orderBy('products.nama_barang')
            ->orderBy('products.merek')
            ->orderBy('products.ukuran')
            ->orderBy('products.id');
    }

    private function boundedLimit(int $limit): int
    {
        return $limit < 1 ? self::DEFAULT_LIMIT : min($limit, self::MAX_LIMIT);
    }

    private function normalizeForSearch(string $value): string
    {
        return mb_strtolower(preg_replace('/\\s+/', ' ', trim($value)) ?? trim($value));
    }
}
