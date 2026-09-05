<?php

declare(strict_types=1);

namespace App\Adapters\Out\ServiceProductTemplate;

use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplateTableQuery;
use App\Ports\Out\ServiceProductTemplate\ServiceProductTemplateTableReaderPort;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class DatabaseServiceProductTemplateTableReaderAdapter implements ServiceProductTemplateTableReaderPort
{
    public function __construct(
        private readonly ServiceProductTemplateTableOrderer $orderer,
        private readonly ServiceProductTemplateTableRowMapper $rows,
    ) {}

    public function search(ServiceProductTemplateTableQuery $query): array
    {
        $builder = DB::table('service_product_templates')
            ->join('products', 'products.id', '=', 'service_product_templates.product_id')
            ->join(
                'service_catalog_items',
                'service_catalog_items.id',
                '=',
                'service_product_templates.service_catalog_item_id',
            )
            ->select([
                'service_product_templates.*',
                'products.kode_barang',
                'products.nama_barang',
                'products.harga_jual',
                'service_catalog_items.name as service_name',
            ]);

        if ($query->status !== 'all') {
            $builder->where('service_product_templates.is_active', $query->status === 'active');
        }

        if ($query->q !== null) {
            $like = '%'.mb_strtolower($query->q).'%';
            $builder->where(function (Builder $inner) use ($like): void {
                $inner->whereRaw('LOWER(products.kode_barang) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(products.nama_barang) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(service_catalog_items.name) LIKE ?', [$like]);
            });
        }

        $this->orderer->apply($builder, $query);
        $paginator = $builder->paginate($query->perPage, ['*'], 'page', $query->page);

        return [
            'rows' => array_map($this->rows->map(...), $paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'sort_by' => $query->sortBy,
                'sort_dir' => $query->sortDir,
                'filters' => ['q' => $query->q, 'status' => $query->status],
            ],
        ];
    }
}
