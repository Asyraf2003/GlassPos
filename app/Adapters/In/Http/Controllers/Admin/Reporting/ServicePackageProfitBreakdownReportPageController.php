<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Reporting;

use App\Adapters\In\Http\Requests\Reporting\TransactionReportPageRequest;
use App\Application\Reporting\DTO\TransactionReportPageQuery;
use App\Application\Reporting\UseCases\GetServicePackageProfitBreakdownHandler;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class ServicePackageProfitBreakdownReportPageController extends Controller
{
    public function __invoke(
        TransactionReportPageRequest $request,
        GetServicePackageProfitBreakdownHandler $useCase,
    ): View {
        $query = TransactionReportPageQuery::fromValidated($request->validated());
        $result = $useCase->handle($query->fromTransactionDate(), $query->toTransactionDate());
        $payload = is_array($result->data()) ? $result->data() : [];
        $filters = $query->toViewData();

        return view('admin.reporting.service_package_profit_breakdown.index', [
            'filters' => $filters,
            'summary' => is_array($payload['summary'] ?? null) ? $payload['summary'] : [],
        ]);
    }
}
