<?php

declare(strict_types=1);

namespace App\Application\ServiceCatalog\Services;

use Illuminate\Support\Facades\DB;

final class ServiceCatalogAdminPageData
{
    /**
     * @return list<array<string, mixed>>
     */
    public function services(): array
    {
        return DB::table('service_catalog_items')
            ->select(['id', 'name', 'normalized_name', 'default_price_rupiah', 'is_active', 'created_at', 'updated_at'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn (object $row): array => [
                'id' => (string) $row->id,
                'name' => (string) $row->name,
                'normalized_name' => (string) $row->normalized_name,
                'default_price_rupiah' => (int) $row->default_price_rupiah,
                'is_active' => (bool) $row->is_active,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function service(string $serviceId): ?array
    {
        $row = DB::table('service_catalog_items')
            ->where('id', trim($serviceId))
            ->first();

        if ($row === null) {
            return null;
        }

        return [
            'id' => (string) $row->id,
            'name' => (string) $row->name,
            'normalized_name' => (string) $row->normalized_name,
            'default_price_rupiah' => (int) $row->default_price_rupiah,
            'is_active' => (bool) $row->is_active,
        ];
    }
}
