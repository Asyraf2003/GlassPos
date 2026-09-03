<?php

declare(strict_types=1);

namespace App\Console\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

final class UploadPublicAssetsToR2 extends Command
{
    protected $signature = 'r2:upload-public-assets {--dry-run : Inventaris file tanpa upload ke R2}';

    protected $description = 'Upload public/assets ke bucket R2 publik dengan object key assets/...';

    public function handle(): int
    {
        $root = public_path('assets');

        if (! is_dir($root)) {
            $this->error("Direktori asset tidak ditemukan: {$root}");

            return self::FAILURE;
        }

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

        $this->info('Public asset R2 upload');
        $this->line('source: '.$root);
        $this->line('disk: r2_public');
        $this->line('object prefix: assets/');
        $this->line('files: '.count($files));
        $this->line('bytes: '.$totalBytes.' ('.$this->formatBytes($totalBytes).')');

        if ((bool) $this->option('dry-run')) {
            $this->info('Dry-run selesai. Tidak ada object yang diupload.');

            return self::SUCCESS;
        }

        $disk = Storage::disk('r2_public');
        $uploaded = 0;
        $uploadedBytes = 0;
        $failed = [];

        foreach ($files as $index => $file) {
            $sourcePath = $file->getPathname();
            $relativePath = substr($sourcePath, strlen($root) + 1);
            $objectKey = 'assets/'.str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            $stream = @fopen($sourcePath, 'rb');

            if (! is_resource($stream)) {
                $failed[] = $objectKey;
                $this->error("Gagal membuka file: {$sourcePath}");
                continue;
            }

            try {
                $stored = $disk->put($objectKey, $stream, [
                    'ContentType' => $this->contentType($file),
                    'CacheControl' => 'public, max-age=86400',
                ]);
            } catch (Throwable $e) {
                $stored = false;
                $this->error("Upload exception {$objectKey}: {$e->getMessage()}");
            } finally {
                fclose($stream);
            }

            if (! $stored) {
                $failed[] = $objectKey;
                $this->error("Upload gagal: {$objectKey}");
                continue;
            }

            $uploaded++;
            $uploadedBytes += max(0, $file->getSize());

            if (($index + 1) % 250 === 0 || $index + 1 === count($files)) {
                $this->line('progress: '.($index + 1).'/'.count($files));
            }
        }

        $this->newLine();
        $this->info("uploaded: {$uploaded}/".count($files));
        $this->line('uploaded bytes: '.$uploadedBytes.' ('.$this->formatBytes($uploadedBytes).')');
        $this->line('failed: '.count($failed));

        if ($failed !== []) {
            foreach (array_slice($failed, 0, 20) as $objectKey) {
                $this->line("- {$objectKey}");
            }

            if (count($failed) > 20) {
                $this->line('- ... '.(count($failed) - 20).' kegagalan lain');
            }

            return self::FAILURE;
        }

        $this->info('Upload public/assets ke R2 selesai tanpa kegagalan.');

        return self::SUCCESS;
    }

    private function contentType(SplFileInfo $file): string
    {
        $extension = strtolower($file->getExtension());

        $known = [
            'css' => 'text/css; charset=utf-8',
            'js' => 'text/javascript; charset=utf-8',
            'mjs' => 'text/javascript; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'map' => 'application/json; charset=utf-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            'eot' => 'application/vnd.ms-fontobject',
            'wasm' => 'application/wasm',
            'html' => 'text/html; charset=utf-8',
            'htm' => 'text/html; charset=utf-8',
            'xml' => 'application/xml; charset=utf-8',
            'txt' => 'text/plain; charset=utf-8',
            'pdf' => 'application/pdf',
        ];

        if (isset($known[$extension])) {
            return $known[$extension];
        }

        $detected = @mime_content_type($file->getPathname());

        return is_string($detected) && $detected !== ''
            ? $detected
            : 'application/octet-stream';
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;

        foreach ($units as $index => $unit) {
            if ($value < 1024 || $index === array_key_last($units)) {
                return number_format($value, 2).' '.$unit;
            }

            $value /= 1024;
        }

        return $bytes.' B';
    }
}
