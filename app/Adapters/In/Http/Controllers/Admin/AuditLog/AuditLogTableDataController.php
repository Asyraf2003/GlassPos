<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\AuditLog;

use App\Adapters\In\Http\Presenters\JsonPresenter;
use App\Adapters\In\Http\Requests\AuditLog\AuditLogTableQueryRequest;
use App\Application\Audit\DTO\AuditLogTableQuery;
use App\Application\Audit\UseCases\GetAuditLogTableHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class AuditLogTableDataController extends Controller
{
    public function __invoke(AuditLogTableQueryRequest $request, GetAuditLogTableHandler $handler, JsonPresenter $presenter): JsonResponse
    {
        return $presenter->success($handler->handle(AuditLogTableQuery::fromValidated($request->validated())));
    }
}
