<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Reporting;

use App\Adapters\In\Http\Requests\Reporting\PayrollReportPageRequest;
use App\Adapters\In\Http\Support\ReportArrayPaginator;
use App\Application\Reporting\DTO\PayrollReportPageQuery;
use App\Application\Reporting\Exports\PayrollReportPdfViewDataBuilder;
use App\Application\Reporting\Services\ReportTemporalContext;
use App\Application\Reporting\UseCases\GetPayrollReportDatasetHandler;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class PayrollReportPageController extends Controller
{
    public function __invoke(
        PayrollReportPdfViewDataBuilder $presentation,
        PayrollReportPageRequest $request,
        GetPayrollReportDatasetHandler $useCase,
        ReportArrayPaginator $paginator,
    ): View {
        $query = PayrollReportPageQuery::fromValidated($request->validated());
        $result = $useCase->handle($query->fromDisbursementDate(), $query->toDisbursementDate());
        $payload = is_array($result->data()) ? $result->data() : [];

        return view('admin.reporting.payroll.index', [
            'detailTables' => $presentation->build($payload, $query->toViewData())['detailTables'],
            'temporalContext' => ReportTemporalContext::description('PayrollReport', $query->toViewData()),
            'filters' => $query->toViewData(),
            'summary' => is_array($payload['summary'] ?? null) ? $payload['summary'] : [],
            'periodRows' => is_array($payload['period_rows'] ?? null) ? $payload['period_rows'] : [],
            'modeRows' => is_array($payload['mode_rows'] ?? null) ? $payload['mode_rows'] : [],
            'rows' => $paginator->paginate(
                is_array($payload['rows'] ?? null) ? $payload['rows'] : [],
                $request,
                'detail_page',
            ),
        ]);
    }
}
