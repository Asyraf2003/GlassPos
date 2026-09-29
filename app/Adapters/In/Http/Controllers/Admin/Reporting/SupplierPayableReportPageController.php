<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Reporting;

use App\Adapters\In\Http\Requests\Reporting\SupplierPayableReportPageRequest;
use App\Adapters\In\Http\Support\ReportArrayPaginator;
use App\Application\Reporting\DTO\SupplierPayableReportPageQuery;
use App\Application\Reporting\Services\ReportTemporalContext;
use App\Application\Reporting\UseCases\GetSupplierPayableReportDatasetHandler;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class SupplierPayableReportPageController extends Controller
{
    public function __invoke(
        SupplierPayableReportPageRequest $request,
        GetSupplierPayableReportDatasetHandler $useCase,
        ReportArrayPaginator $paginator,
    ): View {
        $query = SupplierPayableReportPageQuery::fromValidated($request->validated());
        $result = $useCase->handle(
            $query->fromShipmentDate(),
            $query->toShipmentDate(),
            $query->referenceDate(),
        );
        $payload = is_array($result->data()) ? $result->data() : [];

        return view('admin.reporting.supplier_payable.index', [
            'temporalSummaryRows' => $payload['temporal_summary_rows'] ?? [],
            'temporalContext' => ReportTemporalContext::description('SupplierPayableReport', $query->toViewData()),
            'filters' => $query->toViewData(),
            'summary' => is_array($payload['summary'] ?? null) ? $payload['summary'] : [],
            'periodRows' => is_array($payload['period_rows'] ?? null) ? $payload['period_rows'] : [],
            'supplierRows' => is_array($payload['supplier_rows'] ?? null) ? $payload['supplier_rows'] : [],
            'rows' => $paginator->paginate(
                is_array($payload['rows'] ?? null) ? $payload['rows'] : [],
                $request,
                'detail_page',
            ),
        ]);
    }
}
