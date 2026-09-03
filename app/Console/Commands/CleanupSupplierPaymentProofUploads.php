<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Procurement\UseCases\CleanupSupplierPaymentProofUploadsHandler;
use Illuminate\Console\Command;
use Throwable;

final class CleanupSupplierPaymentProofUploads extends Command
{
    protected $signature = 'supplier-payment-proofs:cleanup-uploads
        {--limit=100 : Maximum intents per run}
        {--stale-minutes=30 : Age for recovering stale finalizing intents}';

    protected $description = 'Expire and clean abandoned supplier payment proof direct uploads';

    public function handle(CleanupSupplierPaymentProofUploadsHandler $handler): int
    {
        try {
            $result = $handler->handle((int) $this->option('limit'), (int) $this->option('stale-minutes'));
        } catch (Throwable) {
            $this->error('Supplier payment proof upload cleanup failed.');

            return self::FAILURE;
        }

        $this->info('Examined: '.$result['examined']);
        $this->info('Expired: '.$result['expired']);
        $this->info('Failed: '.$result['failed']);

        return $result['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
