<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Reporting;

use App\Adapters\In\Http\Requests\Reporting\SupplierPayableReportPageRequest;
use App\Application\Reporting\DTO\SupplierPayableReportPageQuery;
use App\Application\Reporting\Exports\SupplierPayableReportPdfViewDataBuilder;
use App\Application\Reporting\UseCases\GetSupplierPayableReportDatasetHandler;
use Carbon\CarbonImmutable;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class SupplierPayableReportPdfExportController extends Controller
{
    private const MAX_PDF_RANGE_DAYS = 30;

    public function __invoke(
        SupplierPayableReportPageRequest $request,
        GetSupplierPayableReportDatasetHandler $useCase,
        SupplierPayableReportPdfViewDataBuilder $viewDataBuilder,
        ViewFactory $viewFactory,
    ): Response {
        $query = SupplierPayableReportPageQuery::fromValidated($request->validated());

        $rangeRejection = $this->rejectWhenPdfRangeIsTooLong(
            $query->fromShipmentDate(),
            $query->toShipmentDate(),
        );

        if ($rangeRejection instanceof Response) {
            return $rangeRejection;
        }

        $result = $useCase->handle(
            $query->fromShipmentDate(),
            $query->toShipmentDate(),
            $query->referenceDate(),
        );

        $dataset = is_array($result->data()) ? $result->data() : [];

        $html = $viewFactory->make(
            'admin.reporting.supplier_payable.export_pdf',
            $viewDataBuilder->build($dataset, $query->toViewData()),
        )->render();

        $dompdf = new Dompdf($this->options());
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->filename($query) . '"',
        ]);
    }

    private function rejectWhenPdfRangeIsTooLong(?string $from, ?string $to): ?Response
    {
        if ($from === null && $to === null) {
            return null;
        }

        $fromDate = CarbonImmutable::parse($from);
        $toDate = CarbonImmutable::parse($to);

        if ($fromDate->diffInDays($toDate) <= self::MAX_PDF_RANGE_DAYS) {
            return null;
        }

        return response('Export PDF maksimal 1 bulan.', 422);
    }

    private function filename(SupplierPayableReportPageQuery $query): string
    {
        if ($query->fromShipmentDate() === null) {
            return 'laporan-hutang-pemasok-seluruh-periode.pdf';
        }

        return sprintf(
            'laporan-hutang-pemasok-%s-sampai-%s.pdf',
            $query->fromShipmentDate(),
            $query->toShipmentDate(),
        );
    }

    private function options(): Options
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        return $options;
    }
}
