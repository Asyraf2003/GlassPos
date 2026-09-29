<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Reporting;

use App\Adapters\In\Http\Requests\Reporting\InventoryStockValueReportPageRequest;
use App\Adapters\In\Http\Support\ReportArrayPaginator;
use App\Application\Reporting\DTO\InventoryStockValueReportPageQuery;
use App\Application\Reporting\Exports\InventoryStockValueReportPdfViewDataBuilder;
use App\Application\Reporting\Services\ReportTemporalContext;
use App\Application\Reporting\UseCases\GetInventoryStockValueReportDatasetHandler;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class InventoryStockValueReportPageController extends Controller
{
    public function __invoke(
        InventoryStockValueReportPdfViewDataBuilder $presentation,
        InventoryStockValueReportPageRequest $request,
        GetInventoryStockValueReportDatasetHandler $useCase,
        ReportArrayPaginator $paginator,
    ): View {
        $query = InventoryStockValueReportPageQuery::fromValidated($request->validated());
        $result = $useCase->handleSummaryOnly($query->fromMutationDate(), $query->toMutationDate());
        $payload = is_array($result->data()) ? $result->data() : [];

        return view('admin.reporting.inventory_stock_value.index', [
            'detailTables' => $paginator->paginateTables(
                $presentation->build($payload, $query->toViewData())['detailTables'],
                $request,
                'detail_table',
            ),
            'temporalContext' => ReportTemporalContext::description('InventoryStockValueReport', $query->toViewData()),
            'filters' => $query->toViewData(),
            'summary' => is_array($payload['summary'] ?? null) ? $payload['summary'] : [],
        ]);
    }
}
