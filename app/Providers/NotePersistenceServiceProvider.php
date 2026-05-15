<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Out\Note\DatabaseDueNoteReminderReaderAdapter;
use App\Adapters\Out\Note\DatabaseNoteCorrectionHistoryReaderAdapter;
use App\Adapters\Out\Note\DatabaseNoteHistoryProjectionSourceReaderAdapter;
use App\Adapters\Out\Note\DatabaseNoteHistoryProjectionWriterAdapter;
use App\Adapters\Out\Note\DatabaseNoteMutationEventWriterAdapter;
use App\Adapters\Out\Note\DatabaseNoteMutationSnapshotWriterAdapter;
use App\Adapters\Out\Note\DatabaseNoteReaderAdapter;
use App\Adapters\Out\Note\DatabaseNoteWriterAdapter;
use App\Adapters\Out\Note\DatabaseTransactionWorkspaceDraftDeleterAdapter;
use App\Adapters\Out\Note\DatabaseTransactionWorkspaceDraftReaderAdapter;
use App\Adapters\Out\Note\DatabaseTransactionWorkspaceDraftWriterAdapter;
use App\Adapters\Out\Note\DatabaseWorkItemStoreStockLineReaderAdapter;
use App\Adapters\Out\Note\DatabaseWorkItemWriterAdapter;
use App\Adapters\Out\Note\Queries\AdminNoteHistoryTableQuery;
use App\Adapters\Out\Note\Queries\CashierNoteHistoryTableQuery;
use App\Ports\Out\Note\AdminNoteHistoryTableReaderPort;
use App\Ports\Out\Note\CashierNoteHistoryTableReaderPort;
use App\Ports\Out\Note\DueNoteReminderReaderPort;
use App\Ports\Out\Note\NoteCorrectionHistoryReaderPort;
use App\Ports\Out\Note\NoteHistoryProjectionSourceReaderPort;
use App\Ports\Out\Note\NoteHistoryProjectionWriterPort;
use App\Ports\Out\Note\NoteMutationEventWriterPort;
use App\Ports\Out\Note\NoteMutationSnapshotWriterPort;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Note\NoteWriterPort;
use App\Ports\Out\Note\TransactionWorkspaceDraftDeleterPort;
use App\Ports\Out\Note\TransactionWorkspaceDraftReaderPort;
use App\Ports\Out\Note\TransactionWorkspaceDraftWriterPort;
use App\Ports\Out\Note\WorkItemStoreStockLineReaderPort;
use App\Ports\Out\Note\WorkItemWriterPort;
use Illuminate\Support\ServiceProvider;

class NotePersistenceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NoteReaderPort::class, DatabaseNoteReaderAdapter::class);
        $this->app->singleton(NoteWriterPort::class, DatabaseNoteWriterAdapter::class);
        $this->app->singleton(TransactionWorkspaceDraftWriterPort::class, DatabaseTransactionWorkspaceDraftWriterAdapter::class);
        $this->app->singleton(TransactionWorkspaceDraftReaderPort::class, DatabaseTransactionWorkspaceDraftReaderAdapter::class);
        $this->app->singleton(TransactionWorkspaceDraftDeleterPort::class, DatabaseTransactionWorkspaceDraftDeleterAdapter::class);
        $this->app->singleton(WorkItemWriterPort::class, DatabaseWorkItemWriterAdapter::class);
        $this->app->singleton(WorkItemStoreStockLineReaderPort::class, DatabaseWorkItemStoreStockLineReaderAdapter::class);
        $this->app->singleton(NoteMutationEventWriterPort::class, DatabaseNoteMutationEventWriterAdapter::class);
        $this->app->singleton(NoteMutationSnapshotWriterPort::class, DatabaseNoteMutationSnapshotWriterAdapter::class);
        $this->app->singleton(DueNoteReminderReaderPort::class, DatabaseDueNoteReminderReaderAdapter::class);
        $this->app->singleton(NoteCorrectionHistoryReaderPort::class, DatabaseNoteCorrectionHistoryReaderAdapter::class);
        $this->app->singleton(NoteHistoryProjectionSourceReaderPort::class, DatabaseNoteHistoryProjectionSourceReaderAdapter::class);
        $this->app->singleton(NoteHistoryProjectionWriterPort::class, DatabaseNoteHistoryProjectionWriterAdapter::class);
        $this->app->singleton(CashierNoteHistoryTableReaderPort::class, CashierNoteHistoryTableQuery::class);
        $this->app->singleton(AdminNoteHistoryTableReaderPort::class, AdminNoteHistoryTableQuery::class);
    }
}
