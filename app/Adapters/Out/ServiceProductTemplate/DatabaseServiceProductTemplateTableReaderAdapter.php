<?php

declare(strict_types=1);

namespace App\Adapters\Out\ServiceProductTemplate;

use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplateTableQuery;
use App\Application\ServiceProductTemplate\Services\ServiceProductTemplatePackageSplitCalculator;
use App\Ports\Out\ServiceProductTemplate\ServiceProductTemplateTableReaderPort;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class DatabaseServiceProductTemplateTableReaderAdapter implements ServiceProductTemplateTableReaderPort
{
    public function __construct(private readonly ServiceProductTemplatePackageSplitCalculator $split) {}

    public function search(ServiceProductTemplateTableQuery $query): array
    {
        $builder = DB::table('service_product_templates')
            ->join('products', 'products.id', '=', 'service_product_templates.product_id')
            ->join('service_catalog_items', 'service_catalog_items.id', '=', 'service_product_templates.service_catalog_item_id')
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
            $like = '%' . mb_strtolower($query->q) . '%';
            $builder->where(function (Builder $inner) use ($like): void {
                $inner->whereRaw('LOWER(products.kode_barang) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(products.nama_barang) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(service_catalog_items.name) LIKE ?', [$like]);
            });
        }

        $this->order($builder, $query);
        $paginator = $builder->paginate($query->perPage, ['*'], 'page', $query->page);

        return [
            'rows' => array_map(fn (object $row): array => $this->row($row), $paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(),
                'total' => $paginator->total(), 'last_page' => $paginator->lastPage(),
                'sort_by' => $query->sortBy, 'sort_dir' => $query->sortDir,
                'filters' => ['q' => $query->q, 'status' => $query->status],
            ],
        ];
    }

    private function order(Builder $builder, ServiceProductTemplateTableQuery $query): void
    {
        $columns = [
            'product_name' => 'products.nama_barang',
            'service_name' => 'service_catalog_items.name',
            'default_service_price_rupiah' => 'service_product_templates.default_service_price_rupiah',
            'is_active' => 'service_product_templates.is_active',
        ];
        if ($query->sortBy === 'package_total') {
            $builder->orderByRaw(
                'COALESCE(service_product_templates.default_package_total_rupiah, products.harga_jual + service_product_templates.default_service_price_rupiah) ' . $query->sortDir,
            );
        } elseif ($query->sortBy !== null) {
            $builder->orderBy($columns[$query->sortBy], $query->sortDir);
        } elseif ($query->q !== null) {
            $term = mb_strtolower($query->q);
            $builder->orderByRaw(
                'CASE WHEN LOWER(products.kode_barang) = ? THEN 0 WHEN LOWER(products.kode_barang) LIKE ? THEN 1 WHEN LOWER(products.nama_barang) = ? THEN 2 WHEN LOWER(products.nama_barang) LIKE ? THEN 3 WHEN LOWER(service_catalog_items.name) = ? THEN 4 WHEN LOWER(service_catalog_items.name) LIKE ? THEN 5 ELSE 6 END',
                [$term, $term . '%', $term, $term . '%', $term, $term . '%'],
            );
        }
        $builder->orderBy('products.nama_barang')
            ->orderBy('service_product_templates.sort_order')
            ->orderBy('service_product_templates.id');
    }

    /** @return array<string, mixed> */
    private function row(object $row): array
    {
        $split = $this->split->calculate(
            (int) $row->harga_jual,
            (int) $row->default_service_price_rupiah,
            $row->default_package_total_rupiah !== null ? (int) $row->default_package_total_rupiah : null,
        );
        $id = (string) $row->id;

        return [
            'id' => $id,
            'product_id' => (string) $row->product_id,
            'service_catalog_item_id' => (string) $row->service_catalog_item_id,
            'kode_barang' => $row->kode_barang !== null ? (string) $row->kode_barang : '',
            'nama_barang' => (string) $row->nama_barang,
            'harga_jual' => (int) $row->harga_jual,
            'service_name' => (string) $row->service_name,
            'default_service_price_rupiah' => (int) $row->default_service_price_rupiah,
            'is_active' => (bool) $row->is_active,
        ] + $split + ['actions' => [
            'detail_url' => route('admin.service-product-templates.show', ['templateId' => $id]),
            'edit_url' => route('admin.service-product-templates.edit', ['templateId' => $id]),
            'product_url' => route('admin.products.show', ['productId' => (string) $row->product_id]),
            'service_url' => route('admin.services.edit', ['serviceId' => (string) $row->service_catalog_item_id]),
            'deactivate_url' => route('admin.service-product-templates.deactivate', ['templateId' => $id]),
            'reactivate_url' => route('admin.service-product-templates.reactivate', ['templateId' => $id]),
        ]];
    }
}
