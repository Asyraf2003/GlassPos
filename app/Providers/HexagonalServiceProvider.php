<?php

// @audit-skip: line-limit

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Out\Note\DatabaseDueNoteReminderReaderAdapter;
use App\Adapters\Out\Note\DatabaseNoteCorrectionHistoryReaderAdapter;
use App\Adapters\Out\Note\DatabaseNoteHistoryProjectionSourceReaderAdapter;
use App\Adapters\Out\Note\DatabaseNoteHistoryProjectionWriterAdapter;
use App\Adapters\Out\Note\DatabaseNoteMutationEventWriterAdapter;
use App\Adapters\Out\Note\DatabaseNoteMutationSnapshotWriterAdapter;
use App\Adapters\Out\Note\DatabaseNoteRevisionSettlementAdapter;
use App\Adapters\Out\Note\DatabaseNoteRevisionSurplusDispositionAdapter;
use App\Adapters\Out\Note\DatabaseNoteRevisionSurplusRefundDueSourceReaderAdapter;
use App\Adapters\Out\Note\DatabaseNoteRevisionSurplusRefundPaymentAdapter;
use App\Adapters\Out\Note\DatabaseNoteSurplusDispositionAuditTimelineReaderAdapter;
use App\Adapters\Out\Note\DatabaseNoteReaderAdapter;
use App\Adapters\Out\Note\DatabaseNoteWriterAdapter;
use App\Adapters\Out\Note\DatabaseTransactionWorkspaceDraftDeleterAdapter;
use App\Adapters\Out\Note\DatabaseTransactionWorkspaceDraftReaderAdapter;
use App\Adapters\Out\Note\DatabaseTransactionWorkspaceDraftWriterAdapter;
use App\Adapters\Out\Note\DatabaseWorkItemWriterAdapter;
use App\Adapters\Out\Note\DatabaseWorkItemStoreStockLineReaderAdapter;
use App\Adapters\Out\Note\Queries\AdminNoteHistoryTableQuery;
use App\Adapters\Out\Note\Queries\CashierNoteHistoryTableQuery;
use App\Adapters\Out\Payment\DatabaseCustomerPaymentReaderAdapter;
use App\Adapters\Out\Payment\DatabaseCustomerPaymentWriterAdapter;
use App\Adapters\Out\Payment\DatabaseCustomerRefundReaderAdapter;
use App\Adapters\Out\Payment\DatabaseCustomerRefundWriterAdapter;
use App\Adapters\Out\Payment\DatabaseRefundComponentAllocationReaderAdapter;
use App\Adapters\Out\Payment\DatabaseRefundComponentAllocationWriterAdapter;
use App\Adapters\Out\Payment\DatabasePaymentAllocationReaderAdapter;
use App\Adapters\Out\Payment\DatabasePaymentAllocationWriterAdapter;
use App\Adapters\Out\Payment\DatabasePaymentComponentAllocationReaderAdapter;
use App\Adapters\Out\Payment\DatabasePaymentComponentAllocationWriterAdapter;
use App\Application\Note\Policies\NoteAddabilityPolicy;
use App\Application\Note\Policies\CashierNoteAccessGuard;
use App\Application\Note\Policies\NotePaidStatusPolicy;
use App\Application\Note\Services\AddWorkItemErrorClassifier;
use App\Application\Note\Services\BuildNoteRevisionSettlement;
use App\Application\Note\Services\BuildCreateNoteRevisionSettlement;
use App\Application\Note\Services\AutoCloseNoteWhenFullyPaid;
use App\Application\Note\Services\NoteCorrectionSnapshotBuilder;
use App\Application\Note\Services\NoteHistoryProjectionService;
use App\Application\Note\Services\NoteRowSettlementSummaryBuilder;
use App\Application\Note\Services\PersistNoteMutationTimeline;
use App\Application\Note\Services\FinalizePaidNoteCorrection;
use App\Application\Note\Services\WorkItemFactory;
use App\Application\Note\Services\WorkItemStatusTransitionService;
use App\Application\Payment\Services\AllocatePaymentAcrossComponents;
use App\Application\Payment\Services\AllocatePaymentErrorClassifier;
use App\Application\Payment\Services\AllocateRefundAcrossComponents;
use App\Application\Payment\Services\ResolveNotePayableComponents;
use App\Ports\Out\Note\AdminNoteHistoryTableReaderPort;
use App\Ports\Out\Note\CashierNoteHistoryTableReaderPort;
use App\Ports\Out\Note\DueNoteReminderReaderPort;
use App\Ports\Out\Note\NoteCorrectionHistoryReaderPort;
use App\Ports\Out\Note\NoteHistoryProjectionSourceReaderPort;
use App\Ports\Out\Note\NoteHistoryProjectionWriterPort;
use App\Ports\Out\Note\NoteMutationEventWriterPort;
use App\Ports\Out\Note\NoteMutationSnapshotWriterPort;
use App\Ports\Out\Note\NoteRevisionSettlementReaderPort;
use App\Ports\Out\Note\NoteRevisionSettlementWriterPort;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionWriterPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundDueSourceReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundPaymentReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundPaymentWriterPort;
use App\Ports\Out\Note\NoteSurplusDispositionAuditTimelineReaderPort;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Note\NoteWriterPort;
use App\Ports\Out\Note\TransactionWorkspaceDraftDeleterPort;
use App\Ports\Out\Note\TransactionWorkspaceDraftReaderPort;
use App\Ports\Out\Note\TransactionWorkspaceDraftWriterPort;
use App\Ports\Out\Note\WorkItemWriterPort;
use App\Ports\Out\Note\WorkItemStoreStockLineReaderPort;
use App\Ports\Out\Payment\CustomerPaymentReaderPort;
use App\Ports\Out\Payment\CustomerPaymentWriterPort;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use App\Ports\Out\Payment\CustomerRefundWriterPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;
use App\Ports\Out\Payment\PaymentAllocationWriterPort;
use App\Ports\Out\Payment\PaymentComponentAllocationReaderPort;
use App\Ports\Out\Payment\PaymentComponentAllocationWriterPort;
use App\Ports\Out\Payment\RefundComponentAllocationReaderPort;
use App\Ports\Out\Payment\RefundComponentAllocationWriterPort;
use Illuminate\Support\ServiceProvider;

class HexagonalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NotePaidStatusPolicy::class);
        $this->app->singleton(NoteAddabilityPolicy::class);
        $this->app->singleton(CashierNoteAccessGuard::class);

        $this->app->singleton(WorkItemFactory::class);
        $this->app->singleton(WorkItemStatusTransitionService::class);
        $this->app->singleton(AddWorkItemErrorClassifier::class);
        $this->app->singleton(AutoCloseNoteWhenFullyPaid::class);
        $this->app->singleton(NoteCorrectionSnapshotBuilder::class);
        $this->app->singleton(NoteHistoryProjectionService::class);
        $this->app->singleton(NoteRowSettlementSummaryBuilder::class);
        $this->app->singleton(BuildNoteRevisionSettlement::class);
        $this->app->singleton(BuildCreateNoteRevisionSettlement::class);
        $this->app->singleton(PersistNoteMutationTimeline::class);
        $this->app->singleton(FinalizePaidNoteCorrection::class);
        $this->app->singleton(AllocatePaymentErrorClassifier::class);
        $this->app->singleton(ResolveNotePayableComponents::class);
        $this->app->singleton(AllocatePaymentAcrossComponents::class);
        $this->app->singleton(AllocateRefundAcrossComponents::class);
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
        $this->app->singleton(NoteRevisionSettlementWriterPort::class, DatabaseNoteRevisionSettlementAdapter::class);
        $this->app->singleton(NoteRevisionSettlementReaderPort::class, DatabaseNoteRevisionSettlementAdapter::class);
        $this->app->singleton(NoteRevisionSurplusDispositionReaderPort::class, DatabaseNoteRevisionSurplusDispositionAdapter::class);
        $this->app->singleton(NoteRevisionSurplusDispositionWriterPort::class, DatabaseNoteRevisionSurplusDispositionAdapter::class);
        $this->app->singleton(NoteRevisionSurplusRefundDueSourceReaderPort::class, DatabaseNoteRevisionSurplusRefundDueSourceReaderAdapter::class);
        $this->app->singleton(NoteRevisionSurplusRefundPaymentReaderPort::class, DatabaseNoteRevisionSurplusRefundPaymentAdapter::class);
        $this->app->singleton(NoteRevisionSurplusRefundPaymentWriterPort::class, DatabaseNoteRevisionSurplusRefundPaymentAdapter::class);
        $this->app->singleton(NoteSurplusDispositionAuditTimelineReaderPort::class, DatabaseNoteSurplusDispositionAuditTimelineReaderAdapter::class);
        $this->app->singleton(CashierNoteHistoryTableReaderPort::class, CashierNoteHistoryTableQuery::class);
        $this->app->singleton(AdminNoteHistoryTableReaderPort::class, AdminNoteHistoryTableQuery::class);

        $this->app->singleton(CustomerPaymentWriterPort::class, DatabaseCustomerPaymentWriterAdapter::class);
        $this->app->singleton(CustomerPaymentReaderPort::class, DatabaseCustomerPaymentReaderAdapter::class);
        $this->app->singleton(CustomerRefundWriterPort::class, DatabaseCustomerRefundWriterAdapter::class);
        $this->app->singleton(CustomerRefundReaderPort::class, DatabaseCustomerRefundReaderAdapter::class);
        $this->app->singleton(RefundComponentAllocationWriterPort::class, DatabaseRefundComponentAllocationWriterAdapter::class);
        $this->app->singleton(RefundComponentAllocationReaderPort::class, DatabaseRefundComponentAllocationReaderAdapter::class);
        $this->app->singleton(PaymentAllocationWriterPort::class, DatabasePaymentAllocationWriterAdapter::class);
        $this->app->singleton(PaymentAllocationReaderPort::class, DatabasePaymentAllocationReaderAdapter::class);
        $this->app->singleton(PaymentComponentAllocationWriterPort::class, DatabasePaymentComponentAllocationWriterAdapter::class);
        $this->app->singleton(PaymentComponentAllocationReaderPort::class, DatabasePaymentComponentAllocationReaderAdapter::class);

    }
}
