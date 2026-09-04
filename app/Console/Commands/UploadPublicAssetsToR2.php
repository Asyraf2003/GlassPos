<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Support\PublicAssets\PublicAssetInventory;
use App\Console\Support\PublicAssets\PublicAssetPathSelector;
use App\Console\Support\PublicAssets\PublicAssetRemoteIndex;
use App\Console\Support\PublicAssets\PublicAssetUploadReporter;
use App\Console\Support\PublicAssets\PublicAssetUploadRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

final class UploadPublicAssetsToR2 extends Command
{
    protected $signature = 'r2:upload-public-assets {--dry-run : Inventaris file tanpa upload ke R2}
        {--force : Upload ulang object walaupun sudah ada di R2} {--path=* : Overwrite hanya file relatif tertentu di bawah public/assets}
        {--retries=2 : Jumlah retry setelah percobaan pertama, maksimum 5} {--progress=50 : Tampilkan progress setiap N file yang diproses} {--source= : Override direktori sumber untuk testing/diagnostic}';

    protected $description = 'Upload public/assets ke bucket R2 publik dengan resume, retry, dan object key assets/...';

    public function handle(): int
    {
        if ($this->option('source') && ! app()->environment(['local', 'testing'])) {
            $this->error('--source hanya tersedia untuk testing/diagnostic lokal.');

            return self::FAILURE;
        }

        $root = PublicAssetInventory::resolveRoot($this->option('source'));

        if (! is_dir($root)) {
            $this->error("Direktori asset tidak ditemukan: {$root}");

            return self::FAILURE;
        }

        try {
            $requestedPaths = (array) $this->option('path');
            $inventory = PublicAssetPathSelector::select(
                PublicAssetInventory::build($root),
                $requestedPaths,
            );
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $targeted = $requestedPaths !== [];
        $retries = max(0, min((int) $this->option('retries'), 5));
        $progressEvery = max(1, min((int) $this->option('progress'), 1000));
        $force = (bool) $this->option('force');
        $reporter = new PublicAssetUploadReporter($this);
        $reporter->overview($inventory, $force, $targeted, $retries, $progressEvery);

        if ((bool) $this->option('dry-run')) {
            $this->info('Dry-run selesai. Tidak ada object yang diupload.');

            return self::SUCCESS;
        }

        $disk = Storage::disk('r2_public');
        $existing = PublicAssetRemoteIndex::load($disk, $force || $targeted, $this);

        if ($existing === null) {
            return self::FAILURE;
        }

        $result = (new PublicAssetUploadRunner)->run(
            $disk,
            $this,
            $inventory->files,
            $inventory->root,
            $existing,
            $force || $targeted,
            $retries,
            $progressEvery,
            $this->output->isVerbose(),
            $this->output->isVeryVerbose(),
        );
        $reporter->summary($result, count($inventory->files));

        if ($result->failed !== []) {
            $reporter->failures($result->failed);
            $this->error(
                'Sebagian asset gagal. Jalankan command yang sama lagi untuk resume; '
                .'object yang sudah ada akan dilewati.'
            );

            return self::FAILURE;
        }

        $this->info('Sinkronisasi public/assets ke R2 selesai tanpa kegagalan.');

        return self::SUCCESS;
    }
}
