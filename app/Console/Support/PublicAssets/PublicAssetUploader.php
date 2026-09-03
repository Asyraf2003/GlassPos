<?php

declare(strict_types=1);

namespace App\Console\Support\PublicAssets;

use Illuminate\Console\Command;
use SplFileInfo;
use Throwable;

final readonly class PublicAssetUploader
{
    public function __construct(
        private mixed $disk,
        private Command $command,
        private int $retries,
        private bool $verbose,
    ) {
    }

    public function upload(SplFileInfo $file, string $objectKey): bool
    {
        $attempts = $this->retries + 1;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $stream = @fopen($file->getPathname(), 'rb');

            if (! is_resource($stream)) {
                $this->command->error('Gagal membuka file lokal: '.$file->getPathname());

                return false;
            }

            $startedAt = microtime(true);

            try {
                $stored = $this->disk->put($objectKey, $stream, [
                    'ContentType' => PublicAssetContentType::for($file),
                    'CacheControl' => 'public, max-age=86400',
                ]);
            } catch (Throwable $e) {
                $stored = false;

                if ($this->verbose || $attempt === $attempts) {
                    $this->command->warn(
                        "attempt {$attempt}/{$attempts} exception {$objectKey}: {$e->getMessage()}"
                    );
                }
            } finally {
                fclose($stream);
            }

            $elapsed = microtime(true) - $startedAt;

            if ($stored) {
                if ($this->verbose) {
                    $this->command->line(sprintf('uploaded %.2fs: %s', $elapsed, $objectKey));
                }

                return true;
            }

            if ($attempt < $attempts) {
                $this->command->warn(sprintf(
                    'retry %d/%d setelah gagal %.2fs: %s',
                    $attempt,
                    $this->retries,
                    $elapsed,
                    $objectKey,
                ));
                usleep(min(1_000_000, 250_000 * $attempt));
            }
        }

        return false;
    }
}
