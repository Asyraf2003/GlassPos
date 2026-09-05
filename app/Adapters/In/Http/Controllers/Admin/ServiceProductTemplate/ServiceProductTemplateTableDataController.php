<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate;

use App\Adapters\In\Http\Presenters\JsonPresenter;
use App\Adapters\In\Http\Requests\ServiceProductTemplate\ServiceProductTemplateTableQueryRequest;
use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplateTableQuery;
use App\Application\ServiceProductTemplate\UseCases\GetServiceProductTemplateTableHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class ServiceProductTemplateTableDataController extends Controller
{
    public function __invoke(
        ServiceProductTemplateTableQueryRequest $request,
        GetServiceProductTemplateTableHandler $handler,
        JsonPresenter $presenter,
    ): JsonResponse {
        return $presenter->success($handler->handle(
            ServiceProductTemplateTableQuery::fromValidated($request->validated()),
        ));
    }
}
