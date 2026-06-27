<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Reporting;

use App\Adapters\In\Http\Requests\Reporting\InventoryStockValueReportPageRequest;
use App\Application\Reporting\DTO\InventoryStockValueReportPageQuery;
use App\Application\Reporting\UseCases\GetInventoryStockValueReportDatasetHandler;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class InventoryStockValueReportPageController extends Controller
{
    public function __invoke(
        InventoryStockValueReportPageRequest $request,
        GetInventoryStockValueReportDatasetHandler $useCase,
    ): View {
        $query = InventoryStockValueReportPageQuery::fromValidated($request->validated());
        $result = $useCase->handle($query->fromMutationDate(), $query->toMutationDate());
        $payload = is_array($result->data()) ? $result->data() : [];

        return view('admin.reporting.inventory_stock_value.index', [
            'filters' => $query->toViewData(),
            'summary' => is_array($payload['summary'] ?? null) ? $payload['summary'] : [],
        ]);
    }
}
