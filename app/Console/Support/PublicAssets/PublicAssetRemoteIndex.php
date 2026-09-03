<?php

declare(strict_types=1);

namespace App\Console\Support\PublicAssets;

use Illuminate\Console\Command;
use Throwable;

final class PublicAssetRemoteIndex
{
    /** @return array<string,bool>|null */
    public static function load(mixed $disk, bool $force, Command $command): ?array
    {
        if ($force) {
            return [];
        }

        try {
            $command->line('Memuat daftar object assets/ yang sudah ada di R2...');
            $existing = array_fill_keys($disk->allFiles('assets'), true);
            $command->line('existing objects: '.count($existing));

            return $existing;
        } catch (Throwable $e) {
            $command->error('Gagal membaca daftar object R2 untuk resume: '.$e->getMessage());
            $command->error(
                'Dihentikan agar command tidak mengunggah ulang seluruh tree secara tidak sengaja. '
                .'Gunakan --force hanya jika memang ingin overwrite.'
            );

            return null;
        }
    }
}
