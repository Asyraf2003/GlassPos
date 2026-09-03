<?php

declare(strict_types=1);

namespace App\Console\Support\PublicAssets;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final readonly class PublicAssetInventory
{
    /** @param list<SplFileInfo> $files */
    public function __construct(
        public string $root,
        public array $files,
        public int $totalBytes,
    ) {
    }

    public static function build(string $root): self
    {
        $files = [];
        $totalBytes = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->isLink()) {
                continue;
            }

            $files[] = $file;
            $totalBytes += max(0, $file->getSize());
        }

        usort(
            $files,
            static fn (SplFileInfo $left, SplFileInfo $right): int => strcmp(
                $left->getPathname(),
                $right->getPathname(),
            ),
        );

        return new self($root, $files, $totalBytes);
    }

    public static function resolveRoot(mixed $sourceOption): string
    {
        $override = trim((string) ($sourceOption ?? ''));

        if ($override === '') {
            return public_path('assets');
        }

        $resolved = realpath($override);

        return is_string($resolved) ? $resolved : $override;
    }
}
