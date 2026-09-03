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
    protected $signature = 'r2:upload-public-assets
        {--dry-run : Inventaris file tanpa upload ke R2}
        {--force : Upload ulang object walaupun sudah ada di R2}
        {--retries=2 : Jumlah retry setelah percobaan pertama, maksimum 5}
        {--progress=50 : Tampilkan progress setiap N file yang diproses}
        {--source= : Override direktori sumber untuk testing/diagnostic}';

    protected $description = 'Upload public/assets ke bucket R2 publik dengan resume, retry, dan object key assets/...';

    public function handle(): int
    {
        $root = $this->resolveRoot();

        if (! is_dir($root)) {
            $this->error("Direktori asset tidak ditemukan: {$root}");

            return self::FAILURE;
        }

        [$files, $totalBytes] = $this->inventory($root);
        $fileCount = count($files);
        $retries = max(0, min((int) $this->option('retries'), 5));
        $progressEvery = max(1, min((int) $this->option('progress'), 1000));
        $force = (bool) $this->option('force');

        $this->info('Public asset R2 upload');
        $this->line('source: '.$root);
        $this->line('disk: r2_public');
        $this->line('object prefix: assets/');
        $this->line('files: '.$fileCount);
        $this->line('bytes: '.$totalBytes.' ('.$this->formatBytes($totalBytes).')');
        $this->line('mode: '.($force ? 'force overwrite' : 'resume / skip existing'));
        $this->line('retries: '.$retries);
        $this->line('progress interval: '.$progressEvery);

        if ((bool) $this->option('dry-run')) {
            $this->info('Dry-run selesai. Tidak ada object yang diupload.');

            return self::SUCCESS;
        }

        $disk = Storage::disk('r2_public');
        $existing = [];

        if (! $force) {
            try {
                $this->line('Memuat daftar object assets/ yang sudah ada di R2...');
                $existing = array_fill_keys($disk->allFiles('assets'), true);
                $this->line('existing objects: '.count($existing));
            } catch (Throwable $e) {
                $this->error('Gagal membaca daftar object R2 untuk resume: '.$e->getMessage());
                $this->error('Dihentikan agar command tidak mengunggah ulang seluruh tree secara tidak sengaja. Gunakan --force hanya jika memang ingin overwrite.');

                return self::FAILURE;
            }
        }

        $uploaded = 0;
        $uploadedBytes = 0;
        $skipped = 0;
        $skippedBytes = 0;
        $failed = [];
        $processed = 0;
        $startedAt = microtime(true);

        foreach ($files as $file) {
            $processed++;
            $sourcePath = $file->getPathname();
            $relativePath = substr($sourcePath, strlen($root) + 1);
            $objectKey = 'assets/'.str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            $fileBytes = max(0, $file->getSize());

            if (! $force && isset($existing[$objectKey])) {
                $skipped++;
                $skippedBytes += $fileBytes;

                if ($this->output->isVeryVerbose()) {
                    $this->line("skip existing: {$objectKey}");
                }

                $this->printProgress($processed, $fileCount, $uploaded, $skipped, count($failed), $progressEvery, $startedAt);
                continue;
            }

            $stored = $this->uploadWithRetry($disk, $file, $objectKey, $retries);

            if (! $stored) {
                $failed[] = $objectKey;
                $this->error("Upload gagal permanen: {$objectKey}");
            } else {
                $uploaded++;
                $uploadedBytes += $fileBytes;
            }

            $this->printProgress($processed, $fileCount, $uploaded, $skipped, count($failed), $progressEvery, $startedAt);
        }

        $elapsed = max(0.0, microtime(true) - $startedAt);

        $this->newLine();
        $this->info("processed: {$processed}/{$fileCount}");
        $this->info("uploaded: {$uploaded}");
        $this->line('uploaded bytes: '.$uploadedBytes.' ('.$this->formatBytes($uploadedBytes).')');
        $this->info("skipped existing: {$skipped}");
        $this->line('skipped bytes: '.$skippedBytes.' ('.$this->formatBytes($skippedBytes).')');
        $this->line('failed: '.count($failed));
        $this->line('elapsed: '.$this->formatDuration($elapsed));

        if ($failed !== []) {
            foreach (array_slice($failed, 0, 20) as $objectKey) {
                $this->line("- {$objectKey}");
            }

            if (count($failed) > 20) {
                $this->line('- ... '.(count($failed) - 20).' kegagalan lain');
            }

            $this->error('Sebagian asset gagal. Jalankan command yang sama lagi untuk resume; object yang sudah ada akan dilewati.');

            return self::FAILURE;
        }

        $this->info('Sinkronisasi public/assets ke R2 selesai tanpa kegagalan.');

        return self::SUCCESS;
    }

    private function resolveRoot(): string
    {
        $override = trim((string) ($this->option('source') ?? ''));

        if ($override !== '') {
            $resolved = realpath($override);

            return is_string($resolved) ? $resolved : $override;
        }

        return public_path('assets');
    }

    /** @return array{0:list<SplFileInfo>,1:int} */
    private function inventory(string $root): array
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
            static fn (SplFileInfo $left, SplFileInfo $right): int => strcmp($left->getPathname(), $right->getPathname()),
        );

        return [$files, $totalBytes];
    }

    private function uploadWithRetry(mixed $disk, SplFileInfo $file, string $objectKey, int $retries): bool
    {
        $attempts = $retries + 1;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $stream = @fopen($file->getPathname(), 'rb');

            if (! is_resource($stream)) {
                $this->error('Gagal membuka file lokal: '.$file->getPathname());

                return false;
            }

            $attemptStartedAt = microtime(true);

            try {
                $stored = $disk->put($objectKey, $stream, [
                    'ContentType' => $this->contentType($file),
                    'CacheControl' => 'public, max-age=86400',
                ]);
            } catch (Throwable $e) {
                $stored = false;

                if ($this->output->isVerbose() || $attempt === $attempts) {
                    $this->warn("attempt {$attempt}/{$attempts} exception {$objectKey}: {$e->getMessage()}");
                }
            } finally {
                fclose($stream);
            }

            $attemptElapsed = microtime(true) - $attemptStartedAt;

            if ($stored) {
                if ($this->output->isVerbose()) {
                    $this->line(sprintf('uploaded %.2fs: %s', $attemptElapsed, $objectKey));
                }

                return true;
            }

            if ($attempt < $attempts) {
                $this->warn(sprintf(
                    'retry %d/%d setelah gagal %.2fs: %s',
                    $attempt,
                    $retries,
                    $attemptElapsed,
                    $objectKey,
                ));
                usleep(min(1_000_000, 250_000 * $attempt));
            }
        }

        return false;
    }

    private function printProgress(
        int $processed,
        int $total,
        int $uploaded,
        int $skipped,
        int $failed,
        int $progressEvery,
        float $startedAt,
    ): void {
        if ($processed % $progressEvery !== 0 && $processed !== $total) {
            return;
        }

        $this->line(sprintf(
            'progress: %d/%d | uploaded=%d skipped=%d failed=%d | elapsed=%s',
            $processed,
            $total,
            $uploaded,
            $skipped,
            $failed,
            $this->formatDuration(microtime(true) - $startedAt),
        ));
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

    private function formatDuration(float $seconds): string
    {
        $seconds = max(0, (int) round($seconds));
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %02dm %02ds', $hours, $minutes, $remainingSeconds);
        }

        if ($minutes > 0) {
            return sprintf('%dm %02ds', $minutes, $remainingSeconds);
        }

        return $remainingSeconds.'s';
    }
}
