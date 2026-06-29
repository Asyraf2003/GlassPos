<?php

use App\Application\Note\Services\NoteHistoryProjectionService;
use App\Application\Procurement\Services\SupplierInvoiceListProjectionService;
use App\Application\Procurement\Services\SupplierListProjectionService;
use App\Application\PushNotification\UseCases\SendDueNoteReminderPushHandler;
use App\Application\PushNotification\UseCases\SendSupplierPayableReminderPushHandler;
use App\Support\ViewDateFormatter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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




Artisan::command(
    'diagnostics:timestamp-readonly {--limit=5 : Jumlah row terbaru per field} {--table=all : Batasi ke satu table kandidat, atau all}',
    function (): int {
        $limit = max(1, min((int) $this->option('limit'), 50));
        $requestedTable = strtolower(trim((string) $this->option('table')));
        $displayTimezone = trim((string) config('app.display_timezone', 'Asia/Makassar'));

        $candidates = [
            [
                'table' => 'audit_events',
                'column' => 'occurred_at',
                'label_columns' => ['event_name', 'aggregate_type', 'bounded_context'],
            ],
            [
                'table' => 'audit_events',
                'column' => 'created_at',
                'label_columns' => ['event_name', 'aggregate_type', 'bounded_context'],
            ],
            [
                'table' => 'audit_event_snapshots',
                'column' => 'created_at',
                'label_columns' => ['snapshot_kind', 'audit_event_id'],
            ],
            [
                'table' => 'note_mutation_events',
                'column' => 'occurred_at',
                'label_columns' => ['mutation_type', 'actor_role', 'note_id'],
            ],
            [
                'table' => 'note_revision_surplus_dispositions',
                'column' => 'occurred_at',
                'label_columns' => ['disposition_type', 'status', 'note_root_id'],
            ],
            [
                'table' => 'note_revision_surplus_dispositions',
                'column' => 'created_at',
                'label_columns' => ['disposition_type', 'status', 'note_root_id'],
            ],
            [
                'table' => 'note_revision_surplus_refund_payments',
                'column' => 'occurred_at',
                'label_columns' => ['status', 'note_root_id', 'audit_event_id'],
            ],
            [
                'table' => 'note_revision_surplus_refund_payments',
                'column' => 'created_at',
                'label_columns' => ['status', 'note_root_id', 'audit_event_id'],
            ],
            [
                'table' => 'note_revisions',
                'column' => 'created_at',
                'label_columns' => ['note_root_id', 'revision_number'],
            ],
            [
                'table' => 'supplier_invoice_versions',
                'column' => 'changed_at',
                'label_columns' => ['supplier_invoice_id', 'revision_no', 'event_name'],
            ],
        ];

        $knownTables = array_values(array_unique(array_map(
            static fn (array $candidate): string => $candidate['table'],
            $candidates
        )));

        if ($requestedTable !== 'all' && ! in_array($requestedTable, $knownTables, true)) {
            $this->error('Table harus all atau salah satu dari: '.implode(', ', $knownTables));

            return 1;
        }

        if ($requestedTable !== 'all') {
            $candidates = array_values(array_filter(
                $candidates,
                static fn (array $candidate): bool => $candidate['table'] === $requestedTable
            ));
        }

        $this->info('Timestamp read-only diagnostic');
        $this->line('mode: READ ONLY');
        $this->line('app.timezone: '.(string) config('app.timezone'));
        $this->line('app.display_timezone: '.$displayTimezone);
        $this->line('now(): '.now()->toDateTimeString());
        $this->line('now(display): '.now($displayTimezone)->toDateTimeString());
        $this->line('limit per field: '.$limit);
        $this->newLine();

        foreach ($candidates as $candidate) {
            $table = $candidate['table'];
            $column = $candidate['column'];
            $fieldName = "{$table}.{$column}";

            if (! Schema::hasTable($table)) {
                $this->warn("SKIP {$fieldName}: table tidak ada.");
                continue;
            }

            if (! Schema::hasColumn($table, $column)) {
                $this->warn("SKIP {$fieldName}: column tidak ada.");
                continue;
            }

            $columns = ['id', $column];

            foreach ($candidate['label_columns'] as $labelColumn) {
                if (Schema::hasColumn($table, $labelColumn)) {
                    $columns[] = $labelColumn;
                }
            }

            $columns = array_values(array_unique($columns));

            $rows = DB::table($table)
                ->select($columns)
                ->whereNotNull($column)
                ->orderByDesc($column)
                ->limit($limit)
                ->get();

            if ($rows->isEmpty()) {
                $this->line("EMPTY {$fieldName}: tidak ada row sample.");
                continue;
            }

            $outputRows = [];

            foreach ($rows as $row) {
                $raw = (string) ($row->{$column} ?? '');

                $labelParts = [];

                foreach ($candidate['label_columns'] as $labelColumn) {
                    if (! property_exists($row, $labelColumn)) {
                        continue;
                    }

                    $labelValue = $row->{$labelColumn};

                    if ($labelValue === null || $labelValue === '') {
                        continue;
                    }

                    $labelParts[] = $labelColumn.'='.(string) $labelValue;
                }

                $outputRows[] = [
                    'table' => $table,
                    'id' => property_exists($row, 'id') ? (string) $row->id : '-',
                    'field' => $column,
                    'raw_db' => $raw,
                    'display' => ViewDateFormatter::display($raw, true),
                    'label' => $labelParts === [] ? '-' : implode(' | ', $labelParts),
                ];
            }

            $this->table(
                ['table', 'id', 'field', 'raw_db', 'display', 'label'],
                $outputRows
            );
        }

        $this->newLine();
        $this->info('Diagnostic selesai. Tidak ada repair/write yang dijalankan.');

        return 0;
    }
)->purpose('Read-only diagnostic timestamp audit/history untuk membandingkan raw DB dan tampilan timezone operasional');


if (! app()->runningUnitTests()) {
    require __DIR__ . '/console_audit.php';
}

require __DIR__ . '/console_audit_outbox.php';
