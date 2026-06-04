<?php

declare(strict_types=1);

namespace App\Adapters\Out\ServiceCatalog;

use App\Core\ServiceCatalog\ServiceCatalogItem;
use App\Core\ServiceCatalog\ServiceNameNormalizer;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\ServiceCatalog\ServiceCatalogReaderPort;
use App\Ports\Out\ServiceCatalog\ServiceCatalogWriterPort;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class DatabaseServiceCatalogAdapter implements ServiceCatalogReaderPort, ServiceCatalogWriterPort
{
    public function __construct(private readonly ServiceNameNormalizer $normalizer)
    {
    }

    public function findByNormalizedName(string $normalizedName): ?ServiceCatalogItem
    {
        $row = DB::table('service_catalog_items')
            ->where('normalized_name', trim($normalizedName))
            ->first();

        return $row === null ? null : $this->map($row);
    }

    public function search(string $query, int $limit = 10): array
    {
        $normalized = $this->normalizer->normalize($query);
        $builder = DB::table('service_catalog_items')->where('is_active', true);

        foreach (explode(' ', $normalized) as $token) {
            if ($token !== '') {
                $builder->where('normalized_name', 'like', '%' . $token . '%');
            }
        }

        return $builder->orderBy('name')->limit(max(1, $limit))->get()
            ->map(fn (object $row): ServiceCatalogItem => $this->map($row))
            ->values()
            ->all();
    }

    public function createIfMissing(string $name, int $defaultPriceRupiah): ServiceCatalogItem
    {
        $trimmed = trim($name);
        $normalized = $this->normalizer->normalize($trimmed);

        if ($trimmed === '' || $normalized === '' || $defaultPriceRupiah <= 0) {
            throw new DomainException('Nama dan harga default jasa wajib valid.');
        }

        $existing = $this->findByNormalizedName($normalized);
        if ($existing !== null) {
            return $existing;
        }

        try {
            DB::table('service_catalog_items')->insert([
                'id' => (string) Str::uuid(),
                'name' => $trimmed,
                'normalized_name' => $normalized,
                'default_price_rupiah' => $defaultPriceRupiah,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException) {
            // Concurrent blur/submit may create the same normalized service first.
        }

        return $this->findByNormalizedName($normalized)
            ?? throw new DomainException('Master jasa gagal dibuat.');
    }

    private function map(object $row): ServiceCatalogItem
    {
        return ServiceCatalogItem::rehydrate(
            (string) $row->id,
            (string) $row->name,
            (string) $row->normalized_name,
            (int) $row->default_price_rupiah,
            (bool) $row->is_active,
        );
    }
}
