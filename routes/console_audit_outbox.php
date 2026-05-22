<?php

use App\Application\Audit\UseCases\ProcessAuditOutboxHandler;
use Illuminate\Support\Facades\Artisan;

Artisan::command(
    'audit:outbox:process {--limit=100} {--retry-failed} {--max-attempts=5}',
    function (ProcessAuditOutboxHandler $handler): int {
        $limit = max(1, (int) $this->option('limit'));
        $retryFailed = (bool) $this->option('retry-failed');
        $maxAttempts = max(1, (int) $this->option('max-attempts'));

        $summary = $handler->handle($limit, $retryFailed, $maxAttempts);

        $this->info('Audit outbox processed: ' . $summary['processed']);
        $this->info('Audit outbox failed: ' . $summary['failed']);
        $this->info('Audit outbox skipped: ' . $summary['skipped']);

        return $summary['failed'] > 0 ? 1 : 0;
    }
)->purpose('Process pending audit outbox rows into canonical audit events');
