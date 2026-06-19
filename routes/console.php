<?php

use App\Application\Note\Services\NoteHistoryProjectionService;
use App\Application\Procurement\Services\SupplierInvoiceListProjectionService;
use App\Application\Procurement\Services\SupplierListProjectionService;
use App\Application\PushNotification\UseCases\SendDueNoteReminderPushHandler;
use App\Application\PushNotification\UseCases\SendSupplierPayableReminderPushHandler;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command(
    'projection:rebuild-indexes {scope=all} {--chunk=200}',
    function (
        SupplierInvoiceListProjectionService $procurementProjection,
        SupplierListProjectionService $supplierProjection,
        NoteHistoryProjectionService $noteProjection
    ): int {
        $scope = strtolower(trim((string) $this->argument('scope')));
        $chunkSize = max((int) $this->option('chunk'), 1);

        if (! in_array($scope, ['all', 'procurement', 'supplier', 'note'], true)) {
            $this->error('Scope harus salah satu dari: all, procurement, supplier, note.');

            return 1;
        }

        if ($scope === 'all' || $scope === 'procurement') {
            $this->info('Rebuild projection procurement dimulai.');
            DB::table('supplier_invoice_list_projection')->delete();

            $total = (int) DB::table('supplier_invoices')->count();
            $processed = 0;

            DB::table('supplier_invoices')
                ->select('id')
                ->orderBy('id')
                ->chunk($chunkSize, function ($rows) use (&$processed, $total, $procurementProjection): void {
                    foreach ($rows as $row) {
                        $procurementProjection->syncInvoice((string) $row->id);
                        $processed++;
                    }

                    $this->line("Procurement projection: {$processed}/{$total}");
                });

            $this->info('Rebuild projection procurement selesai.');
        }

        if ($scope === 'all' || $scope === 'supplier') {
            $this->info('Rebuild projection supplier dimulai.');
            DB::table('supplier_list_projection')->delete();

            $total = (int) DB::table('suppliers')->count();
            $processed = 0;

            DB::table('suppliers')
                ->select('id')
                ->orderBy('id')
                ->chunk($chunkSize, function ($rows) use (&$processed, $total, $supplierProjection): void {
                    foreach ($rows as $row) {
                        $supplierProjection->syncSupplier((string) $row->id);
                        $processed++;
                    }

                    $this->line("Supplier projection: {$processed}/{$total}");
                });

            $this->info('Rebuild projection supplier selesai.');
        }

        if ($scope === 'all' || $scope === 'note') {
            $this->info('Rebuild projection note dimulai.');
            DB::table('note_history_projection')->delete();

            $total = (int) DB::table('notes')->count();
            $processed = 0;

            DB::table('notes')
                ->select('id')
                ->orderBy('id')
                ->chunk($chunkSize, function ($rows) use (&$processed, $total, $noteProjection): void {
                    foreach ($rows as $row) {
                        $noteProjection->syncNote((string) $row->id);
                        $processed++;
                    }

                    $this->line("Note projection: {$processed}/{$total}");
                });

            $this->info('Rebuild projection note selesai.');
        }

        $this->info('Projection rebuild selesai.');

        return 0;
    }
)->purpose('Rebuild read-model projection untuk procurement invoices, supplier list, dan admin note history');


Artisan::command(
    'push-notifications:send-due-note-reminders {--today=} {--note-limit=100} {--subscription-limit=500}',
    function (SendDueNoteReminderPushHandler $handler): int {
        $today = trim((string) ($this->option('today') ?: now()->toDateString()));
        $noteLimit = max(1, (int) $this->option('note-limit'));
        $subscriptionLimit = max(1, (int) $this->option('subscription-limit'));

        try {
            $summary = $handler->handle($today, $noteLimit, $subscriptionLimit);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return 1;
        }

        $this->info('Due reminder notes: '.$summary->dueNoteCount);
        $this->info('Push subscriptions: '.$summary->subscriptionCount);
        $this->info('Push sent: '.$summary->sentCount);
        $this->info('Push expired: '.$summary->expiredCount);
        $this->info('Push failed: '.$summary->failedCount);

        return $summary->failedCount > 0 ? 1 : 0;
    }
)->purpose('Send push notification untuk nota pelanggan yang mendekati atau melewati jatuh tempo');


Artisan::command(
    'push-notifications:send-supplier-payable-reminders {--today=} {--invoice-limit=100} {--subscription-limit=500}',
    function (SendSupplierPayableReminderPushHandler $handler): int {
        $today = trim((string) ($this->option('today') ?: now()->toDateString()));
        $invoiceLimit = max(1, (int) $this->option('invoice-limit'));
        $subscriptionLimit = max(1, (int) $this->option('subscription-limit'));

        try {
            $summary = $handler->handle($today, $invoiceLimit, $subscriptionLimit);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return 1;
        }

        $this->info('Supplier payable reminders: '.$summary->supplierPayableReminderCount);
        $this->info('Push subscriptions: '.$summary->subscriptionCount);
        $this->info('Push sent: '.$summary->sentCount);
        $this->info('Push expired: '.$summary->expiredCount);
        $this->info('Push failed: '.$summary->failedCount);

        return $summary->failedCount > 0 ? 1 : 0;
    }
)->purpose('Send push notification untuk hutang pemasok yang mendekati atau melewati jatuh tempo');


if (! app()->runningUnitTests()) {
    require __DIR__ . '/console_audit.php';
}

require __DIR__ . '/console_audit_outbox.php';
