<?php

declare(strict_types=1);

namespace App\Console\Support\PublicAssets;

use InvalidArgumentException;
use SplFileInfo;

final class PublicAssetPathSelector
{
    /** @param list<mixed> $requestedPaths */
    public static function select(PublicAssetInventory $inventory, array $requestedPaths): PublicAssetInventory
    {
        if ($requestedPaths === []) {
            return $inventory;
        }

        $root = rtrim(str_replace('\\', '/', $inventory->root), '/');
        $selected = [];

        foreach ($requestedPaths as $requestedPath) {
            $relativePath = self::relativePath($requestedPath);
            $candidate = $root.'/'.$relativePath;
            $resolved = realpath($candidate);

            if (! is_string($resolved) || ! is_file($resolved) || is_link($candidate)) {
                throw new InvalidArgumentException("Asset target tidak ditemukan atau bukan file: {$relativePath}");
            }

            $normalized = str_replace('\\', '/', $resolved);

            if (! str_starts_with($normalized, $root.'/')) {
                throw new InvalidArgumentException("Asset target berada di luar root: {$relativePath}");
            }

            $selected[$relativePath] = new SplFileInfo($resolved);
        }

        ksort($selected);
        $files = array_values($selected);
        $totalBytes = array_sum(array_map(
            static fn (SplFileInfo $file): int => max(0, $file->getSize()),
            $files,
        ));

        return new PublicAssetInventory($inventory->root, $files, $totalBytes);
    }

    private static function relativePath(mixed $value): string
    {
        $path = str_replace('\\', '/', trim((string) $value));
        $segments = explode('/', $path);

        if (
            $path === ''
            || str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:\//', $path) === 1
            || in_array('', $segments, true)
            || in_array('.', $segments, true)
            || in_array('..', $segments, true)
        ) {
            throw new InvalidArgumentException('Asset target harus berupa path file relatif yang aman di bawah public/assets.');
        }

        return $path;
    }
}
