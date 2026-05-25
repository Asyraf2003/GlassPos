<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Out\Audit\DatabaseAuditEventWriterAdapter;
use App\Adapters\Out\Audit\DatabaseAuditLogAdapter;
use App\Adapters\Out\Audit\DatabaseAuditLogReaderAdapter;
use App\Adapters\Out\Audit\DatabaseAuditOutboxWriterAdapter;
use App\Adapters\Out\Idempotency\DatabaseIdempotencyRecordAdapter;
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
use App\Ports\Out\IdempotencyRecordPort;
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
        $this->app->singleton(AuditLogPort::class, DatabaseAuditLogAdapter::class);
        $this->app->singleton(AuditLogReaderPort::class, DatabaseAuditLogReaderAdapter::class);
        $this->app->singleton(TransactionManagerPort::class, DatabaseTransactionManagerAdapter::class);
        $this->app->singleton(IdempotencyRecordPort::class, DatabaseIdempotencyRecordAdapter::class);

        $this->app
            ->when([
                CreateNoteRevisionSurplusRefundDueHandler::class,
                RecordNoteRevisionSurplusRefundPaymentHandler::class,
            ])
            ->needs(AuditEventWriterPort::class)
            ->give(DatabaseAuditEventWriterAdapter::class);
    }
}
