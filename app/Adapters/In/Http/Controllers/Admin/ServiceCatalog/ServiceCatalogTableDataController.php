<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceCatalog;

use App\Adapters\In\Http\Presenters\JsonPresenter;
use App\Adapters\In\Http\Requests\ServiceCatalog\ServiceCatalogTableQueryRequest;
use App\Application\ServiceCatalog\DTO\ServiceCatalogTableQuery;
use App\Application\ServiceCatalog\UseCases\GetServiceCatalogTableHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class ServiceCatalogTableDataController extends Controller
{
    public function __invoke(
        ServiceCatalogTableQueryRequest $request,
        GetServiceCatalogTableHandler $handler,
        JsonPresenter $presenter,
    ): JsonResponse {
        return $presenter->success($handler->handle(
            ServiceCatalogTableQuery::fromValidated($request->validated()),
        ));
    }
}
