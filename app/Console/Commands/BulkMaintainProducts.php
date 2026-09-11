<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\ProductCatalog\Services\BulkProductMaintenanceManifestReader;
use App\Application\ProductCatalog\Services\BulkProductMaintenanceRunner;
use App\Application\ProductCatalog\Services\BulkProductMaintenanceValidator;
use Illuminate\Console\Command;
use Throwable;

final class BulkMaintainProducts extends Command
{
    protected $signature = 'products:bulk-maintain
        {file : CSV manifest path}
        {--actor= : Actor ID recorded in audit}
        {--apply : Apply changes; without this option the command is dry-run}';

    protected $description = 'Validate and apply audited bulk product maintenance from CSV';

    public function handle(
        BulkProductMaintenanceManifestReader $reader,
        BulkProductMaintenanceValidator $validator,
        BulkProductMaintenanceRunner $runner,
    ): int {
        $actorId = trim((string) $this->option('actor'));

        if ($actorId === '') {
            $this->error('--actor wajib diisi.');

            return self::FAILURE;
        }

        try {
            $rows = $reader->read($this->resolvePath((string) $this->argument('file')));
            $validation = $validator->validate($rows, $actorId);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($validation['counts'] as $action => $count) {
            $this->line("{$action}: {$count}");
        }

        if ($validation['errors'] !== []) {
            $this->newLine();
            foreach ($validation['errors'] as $error) {
                $this->error($error);
            }

            $this->error('VALIDATION FAILED. Tidak ada perubahan dilakukan.');

            return self::FAILURE;
        }

        $this->info('VALIDATION PASSED: '.count($rows).' row.');

        if (! $this->option('apply')) {
            $this->warn('DRY RUN: tidak ada perubahan database.');

            return self::SUCCESS;
        }

        try {
            $runner->run(
                $rows,
                $actorId,
                (string) $validation['actor_role'],
            );
        } catch (Throwable $e) {
            $this->error('APPLY FAILED: '.$e->getMessage());
            $this->error('Seluruh batch di-rollback.');

            return self::FAILURE;
        }

        $this->info('APPLY SUCCESS: seluruh mutation row tersimpan.');

        return self::SUCCESS;
    }

    private function resolvePath(string $path): string
    {
        $trimmed = trim($path);

        if ($trimmed !== '' && str_starts_with($trimmed, DIRECTORY_SEPARATOR)) {
            return $trimmed;
        }

        return base_path($trimmed);
    }
}
