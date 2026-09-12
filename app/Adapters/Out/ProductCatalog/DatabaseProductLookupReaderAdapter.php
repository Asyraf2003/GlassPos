<?php

declare(strict_types=1);

namespace App\Adapters\Out\ProductCatalog;

use App\Application\ProductCatalog\DTO\ProductLookupRow;
use App\Ports\Out\ProductCatalog\ProductLookupReaderPort;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class DatabaseProductLookupReaderAdapter implements ProductLookupReaderPort
{
    /**
     * @return list<ProductLookupRow>
     */
    public function search(string $query, int $limit = self::DEFAULT_LIMIT, bool $onlyInStock = false): array
    {
        $normalizedQuery = trim($query);
        $boundedLimit = $this->boundedLimit($limit);

        $builder = $this->baseQuery();

        if ($normalizedQuery !== '') {
            $this->applySearch($builder, $normalizedQuery);
        }

        if ($onlyInStock) {
            $builder->where('product_inventory.qty_on_hand', '>', 0);
        }

        $rows = $this->applyOrdering($builder)->limit($boundedLimit)->get();

        return $this->mapRows($rows->all());
    }

    /** @param list<string> $ids
     * @return list<ProductLookupRow>
     */
    public function findByIds(array $ids): array
    {
        return $this->mapRows($this->baseQuery()->whereIn('products.id', $ids)->get()->all());
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

    /** @param list<object> $rows
     * @return list<ProductLookupRow>
     */
    private function mapRows(array $rows): array
    {
        return array_map(
            static fn (object $row): ProductLookupRow => new ProductLookupRow(
                id: (string) $row->id,
                kodeBarang: $row->kode_barang !== null ? (string) $row->kode_barang : null,
                namaBarang: (string) $row->nama_barang,
                merek: (string) $row->merek,
                ukuran: $row->ukuran !== null ? (int) $row->ukuran : null,
                availableStock: (int) $row->available_stock,
                defaultUnitPriceRupiah: (int) $row->harga_jual,
                minimumUnitPriceRupiah: (int) $row->harga_jual,
            ),
            $rows,
        );
    }

    private function applySearch(Builder $query, string $keyword): void
    {
        $rawKeyword = $keyword;
        $normalizedKeyword = $this->normalizeForSearch($keyword);

        $query->where(function (Builder $builder) use ($rawKeyword, $normalizedKeyword): void {
            $builder
                ->where('products.kode_barang', 'like', '%'.$rawKeyword.'%')
                ->orWhere('products.nama_barang', 'like', '%'.$rawKeyword.'%')
                ->orWhere('products.merek', 'like', '%'.$rawKeyword.'%')
                ->orWhere('products.nama_barang_normalized', 'like', '%'.$normalizedKeyword.'%')
                ->orWhere('products.merek_normalized', 'like', '%'.$normalizedKeyword.'%');
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
        $normalized = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);

        return mb_strtolower($normalized);
    }
}
