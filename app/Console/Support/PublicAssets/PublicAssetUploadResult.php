<?php

declare(strict_types=1);

namespace App\Console\Support\PublicAssets;

final class PublicAssetUploadResult
{
    public int $processed = 0;
    public int $uploaded = 0;
    public int $uploadedBytes = 0;
    public int $skipped = 0;
    public int $skippedBytes = 0;

    /** @var list<string> */
    public array $failed = [];

    public function __construct(public readonly float $startedAt)
    {
    }

    public function markSkipped(int $bytes): void
    {
        $this->processed++;
        $this->skipped++;
        $this->skippedBytes += $bytes;
    }

    public function markUploaded(int $bytes): void
    {
        $this->processed++;
        $this->uploaded++;
        $this->uploadedBytes += $bytes;
    }

    public function markFailed(string $objectKey): void
    {
        $this->processed++;
        $this->failed[] = $objectKey;
    }

    public function elapsed(): float
    {
        return max(0.0, microtime(true) - $this->startedAt);
    }
}
