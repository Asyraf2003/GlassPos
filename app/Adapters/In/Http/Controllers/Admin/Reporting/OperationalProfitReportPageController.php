<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Reporting;

use App\Adapters\In\Http\Requests\Reporting\OperationalProfitReportPageRequest;
use App\Application\Reporting\DTO\OperationalProfitReportPageQuery;
use App\Application\Reporting\Services\ReportTemporalContext;
use App\Application\Reporting\UseCases\GetOperationalProfitSummaryHandler;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class OperationalProfitReportPageController extends Controller
{
    public function __invoke(
        OperationalProfitReportPageRequest $request,
        GetOperationalProfitSummaryHandler $useCase,
    ): View {
        $query = OperationalProfitReportPageQuery::fromValidated($request->validated());
        $result = $useCase->handle($query->fromDate(), $query->toDate());
        $payload = is_array($result->data()) ? $result->data() : [];

        return view('admin.reporting.operational_profit.index', [
            'temporalContext' => ReportTemporalContext::description('OperationalProfitReport', $query->toViewData()),
            'filters' => $query->toViewData(),
            'row' => is_array($payload['row'] ?? null) ? $payload['row'] : [],
        ]);
    }
}
