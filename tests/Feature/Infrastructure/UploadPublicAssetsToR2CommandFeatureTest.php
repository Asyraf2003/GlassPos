<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Illuminate\Support\Facades\Storage;
use Mockery;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;
use Tests\TestCase;

final class UploadPublicAssetsToR2CommandFeatureTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryDirectories = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            $this->removeDirectory($directory);
        }

        parent::tearDown();
    }

    public function test_dry_run_never_touches_r2(): void
    {
        $root = $this->makeAssetTree([
            'a.css' => 'body{}',
            'nested/b.js' => 'console.log(1);',
        ]);

        Storage::shouldReceive('disk')->never();

        $this->artisan('r2:upload-public-assets', [
            '--dry-run' => true,
            '--source' => $root,
        ])
            ->expectsOutputToContain('files: 2')
            ->expectsOutputToContain('Dry-run selesai')
            ->assertExitCode(0);
    }

    public function test_resume_lists_existing_objects_once_and_uploads_only_missing_files(): void
    {
        $root = $this->makeAssetTree([
            'a.css' => 'body{}',
            'nested/b.js' => 'console.log(1);',
        ]);

        $disk = Mockery::mock();
        $disk->shouldReceive('allFiles')
            ->once()
            ->with('assets')
            ->andReturn(['assets/a.css']);
        $disk->shouldReceive('put')
            ->once()
            ->withArgs(function (string $key, mixed $stream, array $options): bool {
                self::assertSame('assets/nested/b.js', $key);
                self::assertIsResource($stream);
                self::assertSame('text/javascript; charset=utf-8', $options['ContentType'] ?? null);
                self::assertSame('public, max-age=86400', $options['CacheControl'] ?? null);

                return true;
            })
            ->andReturn(true);

        Storage::shouldReceive('disk')->once()->with('r2_public')->andReturn($disk);

        $this->artisan('r2:upload-public-assets', [
            '--source' => $root,
            '--progress' => 1,
        ])
            ->expectsOutputToContain('existing objects: 1')
            ->expectsOutputToContain('uploaded: 1')
            ->expectsOutputToContain('skipped existing: 1')
            ->expectsOutputToContain('failed: 0')
            ->assertExitCode(0);
    }

    public function test_failed_put_is_retried_with_fresh_stream_and_can_recover(): void
    {
        $root = $this->makeAssetTree([
            'retry.css' => '.retry{}',
        ]);

        $disk = Mockery::mock();
        $disk->shouldReceive('allFiles')->once()->with('assets')->andReturn([]);
        $disk->shouldReceive('put')
            ->twice()
            ->withArgs(function (string $key, mixed $stream, array $options): bool {
                self::assertSame('assets/retry.css', $key);
                self::assertIsResource($stream);

                return true;
            })
            ->andReturn(false, true);

        Storage::shouldReceive('disk')->once()->with('r2_public')->andReturn($disk);

        $this->artisan('r2:upload-public-assets', [
            '--source' => $root,
            '--retries' => 1,
            '--progress' => 1,
        ])
            ->expectsOutputToContain('retry 1/1')
            ->expectsOutputToContain('uploaded: 1')
            ->expectsOutputToContain('failed: 0')
            ->assertExitCode(0);
    }

    public function test_permanent_failure_returns_non_zero_and_is_safe_to_resume(): void
    {
        $root = $this->makeAssetTree([
            'broken.css' => '.broken{}',
        ]);

        $disk = Mockery::mock();
        $disk->shouldReceive('allFiles')->once()->with('assets')->andReturn([]);
        $disk->shouldReceive('put')->twice()->andReturn(false, false);

        Storage::shouldReceive('disk')->once()->with('r2_public')->andReturn($disk);

        $this->artisan('r2:upload-public-assets', [
            '--source' => $root,
            '--retries' => 1,
            '--progress' => 1,
        ])
            ->expectsOutputToContain('failed: 1')
            ->expectsOutputToContain('Jalankan command yang sama lagi untuk resume')
            ->assertExitCode(1);
    }

    public function test_force_mode_does_not_list_remote_objects_and_uploads_every_local_file(): void
    {
        $root = $this->makeAssetTree([
            'a.css' => 'body{}',
            'b.css' => 'html{}',
        ]);

        $disk = Mockery::mock();
        $disk->shouldNotReceive('allFiles');
        $disk->shouldReceive('put')->twice()->andReturn(true);

        Storage::shouldReceive('disk')->once()->with('r2_public')->andReturn($disk);

        $this->artisan('r2:upload-public-assets', [
            '--source' => $root,
            '--force' => true,
            '--progress' => 1,
        ])
            ->expectsOutputToContain('mode: force overwrite')
            ->expectsOutputToContain('uploaded: 2')
            ->expectsOutputToContain('skipped existing: 0')
            ->assertExitCode(0);
    }

    /** @param array<string,string> $files */
    private function makeAssetTree(array $files): string
    {
        $root = sys_get_temp_dir().'/glasspos-r2-assets-'.bin2hex(random_bytes(8));
        mkdir($root, 0777, true);
        $this->temporaryDirectories[] = $root;

        foreach ($files as $relativePath => $contents) {
            $path = $root.'/'.str_replace('\\', '/', $relativePath);
            $directory = dirname($path);

            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            file_put_contents($path, $contents);
        }

        return $root;
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($directory);
    }
}
