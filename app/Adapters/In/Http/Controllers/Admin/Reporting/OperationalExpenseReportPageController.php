<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Reporting;

use App\Adapters\In\Http\Requests\Reporting\OperationalExpenseReportPageRequest;
use App\Adapters\In\Http\Support\ReportArrayPaginator;
use App\Application\Reporting\DTO\OperationalExpenseReportPageQuery;
use App\Application\Reporting\Exports\OperationalExpenseReportPdfViewDataBuilder;
use App\Application\Reporting\Services\ReportTemporalContext;
use App\Application\Reporting\UseCases\GetOperationalExpenseReportDatasetHandler;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class OperationalExpenseReportPageController extends Controller
{
    public function __invoke(
        OperationalExpenseReportPdfViewDataBuilder $presentation,
        OperationalExpenseReportPageRequest $request,
        GetOperationalExpenseReportDatasetHandler $useCase,
        ReportArrayPaginator $paginator,
    ): View {
        $query = OperationalExpenseReportPageQuery::fromValidated($request->validated());
        $result = $useCase->handle($query->fromExpenseDate(), $query->toExpenseDate());
        $payload = is_array($result->data()) ? $result->data() : [];

        return view('admin.reporting.operational_expense.index', [
            'detailTables' => $paginator->paginateTables(
                $presentation->build($payload, $query->toViewData())['detailTables'],
                $request,
                'detail_table',
            ),
            'temporalContext' => ReportTemporalContext::description('OperationalExpenseReport', $query->toViewData()),
            'filters' => $query->toViewData(),
            'summary' => is_array($payload['summary'] ?? null) ? $payload['summary'] : [],
            'periodRows' => is_array($payload['period_rows'] ?? null) ? $payload['period_rows'] : [],
            'categoryRows' => is_array($payload['category_rows'] ?? null) ? $payload['category_rows'] : [],
            'rows' => $paginator->paginate(
                is_array($payload['rows'] ?? null) ? $payload['rows'] : [],
                $request,
                'detail_page',
            ),
        ]);
    }
}
