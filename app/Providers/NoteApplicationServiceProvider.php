<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Note\Policies\CashierNoteAccessGuard;
use App\Application\Note\Policies\NoteAddabilityPolicy;
use App\Application\Note\Policies\NotePaidStatusPolicy;
use App\Application\Note\Services\AddWorkItemErrorClassifier;
use App\Application\Note\Services\AutoCloseNoteWhenFullyPaid;
use App\Application\Note\Services\BuildCreateNoteRevisionSettlement;
use App\Application\Note\Services\BuildNoteRevisionSettlement;
use App\Application\Note\Services\CurrentRevision\CurrentRevisionRowSettlementProjector;
use App\Application\Note\Services\FinalizePaidNoteCorrection;
use App\Application\Note\Services\NoteCorrectionSnapshotBuilder;
use App\Application\Note\Services\NoteHistoryProjectionService;
use App\Application\Note\Services\NoteCurrentRevisionResolver;
use App\Application\Note\Services\NoteOperationalStatusEvaluator;
use App\Application\Note\Services\NoteOperationalStatusResolver;
use App\Application\Note\Services\NoteRowSettlementSummaryBuilder;
use App\Application\Note\Services\PersistNoteMutationTimeline;
use App\Application\Note\Services\WorkItemFactory;
use App\Application\Note\Services\WorkItemStatusTransitionService;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;
use Illuminate\Support\ServiceProvider;

class NoteApplicationServiceProvider extends ServiceProvider
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
        $this->app->singleton(NoteOperationalStatusResolver::class, fn ($app) => new NoteOperationalStatusResolver(
            $app->make(PaymentAllocationReaderPort::class),
            $app->make(CustomerRefundReaderPort::class),
            $app->make(NoteOperationalStatusEvaluator::class),
            $app->make(NoteCurrentRevisionResolver::class),
            $app->make(CurrentRevisionRowSettlementProjector::class),
        ));
        $this->app->singleton(NoteRowSettlementSummaryBuilder::class);
        $this->app->singleton(BuildNoteRevisionSettlement::class);
        $this->app->singleton(BuildCreateNoteRevisionSettlement::class);
        $this->app->singleton(PersistNoteMutationTimeline::class);
        $this->app->singleton(FinalizePaidNoteCorrection::class);
    }
}
