<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Reporting;

use App\Adapters\In\Http\Requests\Reporting\TransactionReportPageRequest;
use App\Application\Reporting\DTO\TransactionReportPageQuery;
use App\Application\Reporting\Exports\TransactionReportPdfViewDataBuilder;
use App\Application\Reporting\UseCases\GetTransactionReportDatasetHandler;
use Carbon\CarbonImmutable;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class TransactionReportPdfExportController extends Controller
{
    private const MAX_PDF_RANGE_DAYS = 30;

    public function __invoke(
        TransactionReportPageRequest $request,
        GetTransactionReportDatasetHandler $useCase,
        TransactionReportPdfViewDataBuilder $viewDataBuilder,
        ViewFactory $viewFactory,
    ): Response {
        $query = TransactionReportPageQuery::fromValidated($request->validated());

        $rangeRejection = $this->rejectWhenPdfRangeIsTooLong(
            $query->fromTransactionDate(),
            $query->toTransactionDate(),
        );

        if ($rangeRejection instanceof Response) {
            return $rangeRejection;
        }

        $result = $useCase->handle($query->fromTransactionDate(), $query->toTransactionDate());
        $dataset = is_array($result->data()) ? $result->data() : [];

        $html = $viewFactory->make(
            'admin.reporting.transaction_summary.export_pdf',
            $viewDataBuilder->build($dataset, $query->toViewData()),
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

    private function filename(TransactionReportPageQuery $query): string
    {
        return sprintf(
            'laporan-transaksi-%s-sampai-%s.pdf',
            $query->fromTransactionDate(),
            $query->toTransactionDate(),
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
