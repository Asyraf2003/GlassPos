<?php

declare(strict_types=1);

namespace App\Adapters\Out\ServiceCatalog;

use App\Application\ServiceCatalog\DTO\ServiceCatalogTableQuery;
use App\Ports\Out\ServiceCatalog\ServiceCatalogTableReaderPort;
use Illuminate\Support\Facades\DB;

final class DatabaseServiceCatalogTableReaderAdapter implements ServiceCatalogTableReaderPort
{
    public function search(ServiceCatalogTableQuery $query): array
    {
        $builder = DB::table('service_catalog_items');

        if ($query->status !== 'all') {
            $builder->where('is_active', $query->status === 'active');
        }

        if ($query->q !== null) {
            $builder->where(function ($inner) use ($query): void {
                $like = '%' . mb_strtolower($query->q) . '%';
                $inner->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(normalized_name) LIKE ?', [$like]);
            });
        }

        if ($query->sortBy !== null) {
            $builder->orderBy($query->sortBy, $query->sortDir)
                ->orderBy('name')
                ->orderBy('id');
        } elseif ($query->q !== null) {
            $normalized = mb_strtolower($query->q);
            $builder->orderByRaw(
                'CASE WHEN LOWER(name) = ? THEN 0 WHEN LOWER(name) LIKE ? THEN 1 WHEN LOWER(normalized_name) = ? THEN 2 ELSE 3 END',
                [$normalized, $normalized . '%', $normalized],
            )->orderBy('name')->orderBy('id');
        } else {
            $builder->orderByDesc('is_active')->orderBy('name')->orderBy('id');
        }

        $paginator = $builder->paginate($query->perPage, ['*'], 'page', $query->page);

        return [
            'rows' => array_map(static fn (object $row): array => [
                'id' => (string) $row->id,
                'name' => (string) $row->name,
                'normalized_name' => (string) $row->normalized_name,
                'default_price_rupiah' => (int) $row->default_price_rupiah,
                'is_active' => (bool) $row->is_active,
                'actions' => [
                    'edit_url' => route('admin.services.edit', ['serviceId' => (string) $row->id]),
                    'activate_url' => route('admin.services.activate', ['serviceId' => (string) $row->id]),
                    'deactivate_url' => route('admin.services.deactivate', ['serviceId' => (string) $row->id]),
                ],
            ], $paginator->items()),
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
