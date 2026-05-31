<?php

declare(strict_types=1);

namespace App\Application\Note\Services\CurrentRevision;

use Illuminate\Support\Facades\DB;

final class CurrentRevisionPackageProductNameResolver
{
    /**
     * @param array<string, mixed> $line
     * @param array<string, string> $currentNames
     */
    public static function displayName(array $line, string $productId, array $currentNames): string
    {
        foreach (['product_name_snapshot', 'product_nama_barang_snapshot'] as $snapshotKey) {
            $snapshotName = trim((string) ($line[$snapshotKey] ?? ''));

            if ($snapshotName !== '') {
                return $snapshotName;
            }
        }

        return $currentNames[$productId] ?? $productId;
    }

    /**
     * @param list<string> $productIds
     * @return array<string, string>
     */
    public static function currentNames(array $productIds): array
    {
        $ids = array_values(array_unique(array_filter($productIds)));

        if ($ids === []) {
            return [];
        }

        return DB::table('products')
            ->whereIn('id', $ids)
            ->pluck('nama_barang', 'id')
            ->map(static fn ($name): string => (string) $name)
            ->all();
    }
}
