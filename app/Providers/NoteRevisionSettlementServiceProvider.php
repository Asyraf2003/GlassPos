<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Out\Note\DatabaseNoteRevisionSettlementAdapter;
use App\Adapters\Out\Note\DatabaseNoteRevisionSurplusDispositionAdapter;
use App\Adapters\Out\Note\DatabaseNoteRevisionSurplusRefundDueSourceReaderAdapter;
use App\Adapters\Out\Note\DatabaseNoteRevisionSurplusRefundPaymentAdapter;
use App\Adapters\Out\Note\DatabaseNoteSurplusDispositionAuditTimelineReaderAdapter;
use App\Ports\Out\Note\NoteRevisionSettlementReaderPort;
use App\Ports\Out\Note\NoteRevisionSettlementWriterPort;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionWriterPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundDueSourceReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundPaymentReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundPaymentWriterPort;
use App\Ports\Out\Note\NoteSurplusDispositionAuditTimelineReaderPort;
use Illuminate\Support\ServiceProvider;

class NoteRevisionSettlementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NoteRevisionSettlementWriterPort::class, DatabaseNoteRevisionSettlementAdapter::class);
        $this->app->singleton(NoteRevisionSettlementReaderPort::class, DatabaseNoteRevisionSettlementAdapter::class);
        $this->app->singleton(NoteRevisionSurplusDispositionReaderPort::class, DatabaseNoteRevisionSurplusDispositionAdapter::class);
        $this->app->singleton(NoteRevisionSurplusDispositionWriterPort::class, DatabaseNoteRevisionSurplusDispositionAdapter::class);
        $this->app->singleton(NoteRevisionSurplusRefundDueSourceReaderPort::class, DatabaseNoteRevisionSurplusRefundDueSourceReaderAdapter::class);
        $this->app->singleton(NoteRevisionSurplusRefundPaymentReaderPort::class, DatabaseNoteRevisionSurplusRefundPaymentAdapter::class);
        $this->app->singleton(NoteRevisionSurplusRefundPaymentWriterPort::class, DatabaseNoteRevisionSurplusRefundPaymentAdapter::class);
        $this->app->singleton(NoteSurplusDispositionAuditTimelineReaderPort::class, DatabaseNoteSurplusDispositionAuditTimelineReaderAdapter::class);
    }
}
