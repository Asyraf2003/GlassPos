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

        $items = $paginator->items();
        $lines = DB::table('service_product_template_lines as lines')
            ->join('products', 'products.id', '=', 'lines.product_id')
            ->whereIn('lines.service_product_template_id', array_map(static fn (object $row): string => (string) $row->id, $items))
            ->orderBy('lines.sort_order')
            ->orderBy('lines.id')
            ->get(['lines.service_product_template_id', 'lines.product_id', 'products.nama_barang as name'])
            ->groupBy('service_product_template_id');

        return [
            'rows' => array_map(fn (object $row): array => $this->rows->map($row) + [
                'product_lines' => $lines->get((string) $row->id)?->map(static fn (object $line): array => [
                    'product_id' => (string) $line->product_id,
                    'name' => (string) $line->name,
                ])->all() ?: [['product_id' => (string) $row->product_id, 'name' => (string) $row->nama_barang]],
            ], $items),
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
