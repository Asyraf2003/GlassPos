<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\ProductCatalog\UseCases\AdoptTransferredProductMergeHandler;
use Illuminate\Console\Command;
use Throwable;

final class AdoptTransferredProductMerge extends Command
{
    protected $signature = 'products:adopt-transferred-merge
        {operation : Stable unique merge relation ID}
        {source : Explicit retired source product ID}
        {canonical : Explicit active canonical product ID}
        {--actor= : Existing actor ID}
        {--reason= : Authoritative business correction reason}
        {--prior-transfer= : Already applied product_master_merge source_id}
        {--same-physical-product : Attest these are duplicate identities of the same physical product}
        {--apply : Commit relation and invoice revisions; otherwise validate and roll back}';

    protected $description = 'Adopt an explicit prior stock merge and canonicalize current supplier invoices atomically';

    public function handle(AdoptTransferredProductMergeHandler $handler): int
    {
        if (! $this->option('same-physical-product')) {
            $this->error('Explicit same-physical-product attestation is required.');
            return self::FAILURE;
        }
        try {
            $changed = $handler->handle(
                trim((string) $this->argument('operation')), trim((string) $this->argument('source')),
                trim((string) $this->argument('canonical')), trim((string) $this->option('actor')),
                trim((string) $this->option('reason')), trim((string) $this->option('prior-transfer')),
                ! $this->option('apply'),
            );
            $this->info(($this->option('apply') ? 'APPLIED' : 'DRY RUN (rolled back)').': '.$changed.' supplier invoice revisions; no stock transfer.');
            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            $this->error('No relation or invoice revision committed.');
            return self::FAILURE;
        }
    }
}
