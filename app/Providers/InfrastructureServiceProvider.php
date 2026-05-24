<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Out\Audit\DatabaseAuditOutboxWriterAdapter;
use App\Adapters\Out\Audit\DatabaseAuditEventWriterAdapter;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueAuditEventFactory;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueDispositionFactory;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueGuard;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueHandler;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueResultFactory;
use App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentAuditEventFactory;
use App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentFactory;
use App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentGuard;
use App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentHandler;
use App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentResultFactory;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionWriterPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundDueSourceReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundPaymentReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundPaymentWriterPort;
use Illuminate\Contracts\Foundation\Application;
use App\Adapters\Out\Audit\DatabaseAuditEventWriterAdapter;
use App\Adapters\Out\Audit\DatabaseAuditLogAdapter;
use App\Adapters\Out\Audit\DatabaseAuditLogReaderAdapter;
use App\Adapters\Out\Auth\LaravelUuidAdapter;
use App\Adapters\Out\Clock\SystemClockAdapter;
use App\Adapters\Out\Persistence\DatabaseTransactionManagerAdapter;
use App\Adapters\Out\Routing\LaravelRouteUrlGeneratorAdapter;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueHandler;
use App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentHandler;
use App\Application\System\Health\HealthCheckHandler;
use App\Ports\In\HealthCheckUseCase;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\AuditLogReaderPort;
use App\Ports\Out\ClockPort;
use App\Ports\Out\RouteUrlGeneratorPort;
use App\Ports\Out\TransactionManagerPort;
use App\Ports\Out\UuidPort;
use Illuminate\Support\ServiceProvider;

class InfrastructureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(HealthCheckUseCase::class, HealthCheckHandler::class);

        $this->app->singleton(ClockPort::class, SystemClockAdapter::class);
        $this->app->singleton(RouteUrlGeneratorPort::class, LaravelRouteUrlGeneratorAdapter::class);
        $this->app->singleton(UuidPort::class, LaravelUuidAdapter::class);
        $this->app->singleton(AuditEventWriterPort::class, DatabaseAuditOutboxWriterAdapter::class);

        $this->app->bind(
            CreateNoteRevisionSurplusRefundDueHandler::class,
            function (Application $app): CreateNoteRevisionSurplusRefundDueHandler {
                return new CreateNoteRevisionSurplusRefundDueHandler(
                    $app->make(NoteRevisionSurplusDispositionReaderPort::class),
                    $app->make(NoteRevisionSurplusDispositionWriterPort::class),
                    $app->make(DatabaseAuditEventWriterAdapter::class),
                    $app->make(TransactionManagerPort::class),
                    $app->make(CreateNoteRevisionSurplusRefundDueGuard::class),
                    $app->make(CreateNoteRevisionSurplusRefundDueDispositionFactory::class),
                    $app->make(CreateNoteRevisionSurplusRefundDueAuditEventFactory::class),
                    $app->make(CreateNoteRevisionSurplusRefundDueResultFactory::class),
                );
            },
        );

        $this->app->bind(
            RecordNoteRevisionSurplusRefundPaymentHandler::class,
            function (Application $app): RecordNoteRevisionSurplusRefundPaymentHandler {
                return new RecordNoteRevisionSurplusRefundPaymentHandler(
                    $app->make(NoteRevisionSurplusRefundDueSourceReaderPort::class),
                    $app->make(NoteRevisionSurplusRefundPaymentReaderPort::class),
                    $app->make(NoteRevisionSurplusRefundPaymentWriterPort::class),
                    $app->make(DatabaseAuditEventWriterAdapter::class),
                    $app->make(TransactionManagerPort::class),
                    $app->make(RecordNoteRevisionSurplusRefundPaymentGuard::class),
                    $app->make(RecordNoteRevisionSurplusRefundPaymentFactory::class),
                    $app->make(RecordNoteRevisionSurplusRefundPaymentAuditEventFactory::class),
                    $app->make(RecordNoteRevisionSurplusRefundPaymentResultFactory::class),
                );
            },
        );

        $this->app->singleton(AuditLogPort::class, DatabaseAuditLogAdapter::class);
        $this->app->singleton(AuditLogReaderPort::class, DatabaseAuditLogReaderAdapter::class);
        $this->app->singleton(TransactionManagerPort::class, DatabaseTransactionManagerAdapter::class);

        $this->app
            ->when([
                CreateNoteRevisionSurplusRefundDueHandler::class,
                RecordNoteRevisionSurplusRefundPaymentHandler::class,
            ])
            ->needs(AuditEventWriterPort::class)
            ->give(DatabaseAuditEventWriterAdapter::class);
    }
}
