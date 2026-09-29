<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Reporting;

use App\Adapters\In\Http\Requests\Reporting\TransactionCashLedgerPageRequest;
use App\Adapters\In\Http\Support\ReportArrayPaginator;
use App\Application\Reporting\DTO\TransactionCashLedgerPageQuery;
use App\Application\Reporting\Exports\TransactionCashLedgerPdfViewDataBuilder;
use App\Application\Reporting\Services\ReportTemporalContext;
use App\Application\Reporting\Services\TransactionCashLedgerPeriodTableBuilder;
use App\Application\Reporting\Services\TransactionCashLedgerSummaryBuilder;
use App\Application\Reporting\UseCases\GetTransactionCashLedgerPerNoteHandler;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class TransactionCashLedgerPageController extends Controller
{
    public function __invoke(
        TransactionCashLedgerPdfViewDataBuilder $presentation,
        TransactionCashLedgerPageRequest $request,
        GetTransactionCashLedgerPerNoteHandler $useCase,
        TransactionCashLedgerSummaryBuilder $summary,
        TransactionCashLedgerPeriodTableBuilder $periods,
        ReportArrayPaginator $paginator,
    ): View {
        $query = TransactionCashLedgerPageQuery::fromValidated($request->validated());
        $result = $useCase->handle($query->fromEventDate(), $query->toEventDate());
        $payload = is_array($result->data()) ? $result->data() : [];
        $rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];

        return view('admin.reporting.transaction_cash_ledger.index', [
            'detailTables' => $paginator->paginateTables(
                $presentation->build($payload, $query->toViewData())['detailTables'],
                $request,
                'detail_table',
            ),
            'temporalContext' => ReportTemporalContext::description('TransactionCashLedger', $query->toViewData()),
            'filters' => $query->toViewData(),
            'summary' => $summary->build($rows),
            'periodRows' => $periods->build($rows),
            'rows' => $paginator->paginate($rows, $request, 'detail_page'),
        ]);
    }
}
