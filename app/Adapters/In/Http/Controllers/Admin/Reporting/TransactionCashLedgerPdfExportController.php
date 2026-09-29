<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Reporting;

use App\Adapters\In\Http\Requests\Reporting\TransactionCashLedgerPageRequest;
use App\Application\Reporting\DTO\TransactionCashLedgerPageQuery;
use App\Application\Reporting\Exports\TransactionCashLedgerPdfViewDataBuilder;
use App\Application\Reporting\Services\TransactionCashLedgerSummaryBuilder;
use App\Application\Reporting\UseCases\GetTransactionCashLedgerPerNoteHandler;
use Carbon\CarbonImmutable;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class TransactionCashLedgerPdfExportController extends Controller
{
    private const MAX_PDF_RANGE_DAYS = 30;

    public function __invoke(
        TransactionCashLedgerPageRequest $request,
        GetTransactionCashLedgerPerNoteHandler $useCase,
        TransactionCashLedgerSummaryBuilder $summaryBuilder,
        TransactionCashLedgerPdfViewDataBuilder $viewDataBuilder,
        ViewFactory $viewFactory,
    ): Response {
        $query = TransactionCashLedgerPageQuery::fromValidated($request->validated());

        $rangeRejection = $this->rejectWhenPdfRangeIsTooLong(
            $query->fromEventDate(),
            $query->toEventDate(),
        );

        if ($rangeRejection instanceof Response) {
            return $rangeRejection;
        }

        $result = $useCase->handle($query->fromEventDate(), $query->toEventDate());
        $payload = is_array($result->data()) ? $result->data() : [];
        $rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];

        $html = $viewFactory->make(
            'admin.reporting.transaction_cash_ledger.export_pdf',
            $viewDataBuilder->build([
                'summary' => $summaryBuilder->build($rows),
                'rows' => $rows,
            ], $query->toViewData()),
        )->render();

        $dompdf = new Dompdf($this->options());
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($query).'"',
        ]);
    }

    private function rejectWhenPdfRangeIsTooLong(string $from, string $to): ?Response
    {
        $fromDate = CarbonImmutable::parse($from);
        $toDate = CarbonImmutable::parse($to);

        if ($fromDate->diffInDays($toDate) <= self::MAX_PDF_RANGE_DAYS) {
            return null;
        }

        return response('Export PDF maksimal 1 bulan.', 422);
    }

    private function filename(TransactionCashLedgerPageQuery $query): string
    {
        return sprintf(
            'laporan-buku-kas-transaksi-%s-sampai-%s.pdf',
            $query->fromEventDate(),
            $query->toEventDate(),
        );
    }

    private function options(): Options
    {
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        return $options;
    }
}
