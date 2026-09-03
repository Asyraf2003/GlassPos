<?php

declare(strict_types=1);

namespace App\Console\Support\PublicAssets;

use Illuminate\Console\Command;
use SplFileInfo;

final readonly class PublicAssetUploadRunner
{
    /**
     * @param array<string,bool> $existing
     * @param list<SplFileInfo> $files
     */
    public function run(
        mixed $disk,
        Command $command,
        array $files,
        string $root,
        array $existing,
        bool $force,
        int $retries,
        int $progressEvery,
        bool $verbose,
        bool $veryVerbose,
    ): PublicAssetUploadResult {
        $result = new PublicAssetUploadResult(microtime(true));
        $uploader = new PublicAssetUploader($disk, $command, $retries, $verbose);
        $reporter = new PublicAssetUploadReporter($command);
        $total = count($files);

        foreach ($files as $file) {
            $sourcePath = $file->getPathname();
            $relativePath = substr($sourcePath, strlen($root) + 1);
            $objectKey = 'assets/'.str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            $fileBytes = max(0, $file->getSize());

            if (! $force && isset($existing[$objectKey])) {
                $result->markSkipped($fileBytes);

                if ($veryVerbose) {
                    $command->line("skip existing: {$objectKey}");
                }

                $reporter->progress($result, $total, $progressEvery);
                continue;
            }

            if ($uploader->upload($file, $objectKey)) {
                $result->markUploaded($fileBytes);
            } else {
                $result->markFailed($objectKey);
                $command->error("Upload gagal permanen: {$objectKey}");
            }

            $reporter->progress($result, $total, $progressEvery);
        }

        return $result;
    }
}
