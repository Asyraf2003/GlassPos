<?php

declare(strict_types=1);

namespace App\Console\Support\PublicAssets;

use Illuminate\Console\Command;

final readonly class PublicAssetUploadReporter
{
    public function __construct(private Command $command)
    {
    }

    public function overview(
        PublicAssetInventory $inventory,
        bool $force,
        int $retries,
        int $progressEvery,
    ): void {
        $this->command->info('Public asset R2 upload');
        $this->command->line('source: '.$inventory->root);
        $this->command->line('disk: r2_public');
        $this->command->line('object prefix: assets/');
        $this->command->line('files: '.count($inventory->files));
        $this->command->line(
            'bytes: '.$inventory->totalBytes.' ('.PublicAssetFormat::bytes($inventory->totalBytes).')'
        );
        $this->command->line('mode: '.($force ? 'force overwrite' : 'resume / skip existing'));
        $this->command->line('retries: '.$retries);
        $this->command->line('progress interval: '.$progressEvery);
    }

    public function progress(PublicAssetUploadResult $result, int $total, int $every): void
    {
        if ($result->processed % $every !== 0 && $result->processed !== $total) {
            return;
        }

        $this->command->line(sprintf(
            'progress: %d/%d | uploaded=%d skipped=%d failed=%d | elapsed=%s',
            $result->processed,
            $total,
            $result->uploaded,
            $result->skipped,
            count($result->failed),
            PublicAssetFormat::duration($result->elapsed()),
        ));
    }

    public function summary(PublicAssetUploadResult $result, int $total): void
    {
        $this->command->newLine();
        $this->command->info("processed: {$result->processed}/{$total}");
        $this->command->info("uploaded: {$result->uploaded}");
        $this->command->line(
            'uploaded bytes: '.$result->uploadedBytes.' ('.PublicAssetFormat::bytes($result->uploadedBytes).')'
        );
        $this->command->info("skipped existing: {$result->skipped}");
        $this->command->line(
            'skipped bytes: '.$result->skippedBytes.' ('.PublicAssetFormat::bytes($result->skippedBytes).')'
        );
        $this->command->line('failed: '.count($result->failed));
        $this->command->line('elapsed: '.PublicAssetFormat::duration($result->elapsed()));
    }

    public function failures(array $failed): void
    {
        foreach (array_slice($failed, 0, 20) as $objectKey) {
            $this->command->line("- {$objectKey}");
        }

        if (count($failed) > 20) {
            $this->command->line('- ... '.(count($failed) - 20).' kegagalan lain');
        }
    }
}
