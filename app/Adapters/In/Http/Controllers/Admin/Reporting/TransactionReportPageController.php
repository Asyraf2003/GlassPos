<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Reporting;

use App\Adapters\In\Http\Requests\Reporting\TransactionReportPageRequest;
use App\Adapters\In\Http\Support\ReportArrayPaginator;
use App\Application\Reporting\DTO\TransactionReportPageQuery;
use App\Application\Reporting\Exports\TransactionReportPdfViewDataBuilder;
use App\Application\Reporting\Services\ReportTemporalContext;
use App\Application\Reporting\UseCases\GetTransactionReportDatasetHandler;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class TransactionReportPageController extends Controller
{
    public function __invoke(
        TransactionReportPdfViewDataBuilder $presentation,
        TransactionReportPageRequest $request,
        GetTransactionReportDatasetHandler $useCase,
        ReportArrayPaginator $paginator,
    ): View {
        $query = TransactionReportPageQuery::fromValidated($request->validated());
        $result = $useCase->handle($query->fromTransactionDate(), $query->toTransactionDate());
        $payload = is_array($result->data()) ? $result->data() : [];
        $filters = $query->toViewData();

        return view('admin.reporting.transaction_summary.index', [
            'detailTables' => $presentation->build($payload, $query->toViewData())['detailTables'],
            'temporalContext' => ReportTemporalContext::description('TransactionReport', $query->toViewData()),
            'filters' => $filters,
            'exportExcelUrl' => route('admin.reports.transaction_summary.export_excel', $filters),
            'summary' => is_array($payload['summary'] ?? null) ? $payload['summary'] : [],
            'periodRows' => is_array($payload['period_rows'] ?? null) ? $payload['period_rows'] : [],
            'customerRows' => $paginator->paginate(
                is_array($payload['customer_rows'] ?? null) ? $payload['customer_rows'] : [],
                $request,
                'customer_page',
            ),
            'rows' => $paginator->paginate(
                is_array($payload['rows'] ?? null) ? $payload['rows'] : [],
                $request,
                'detail_page',
            ),
        ]);
    }
}
