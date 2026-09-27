<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Reporting;

use App\Adapters\In\Http\Requests\Reporting\SupplierPayableReportPageRequest;
use App\Application\Reporting\DTO\SupplierPayableReportPageQuery;
use App\Application\Reporting\Exports\SupplierPayableReportExcelWorkbookBuilder;
use App\Application\Reporting\UseCases\GetSupplierPayableReportDatasetHandler;
use Carbon\CarbonImmutable;
use Illuminate\Routing\Controller;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

final class SupplierPayableReportExcelExportController extends Controller
{
    private const MAX_EXCEL_RANGE_DAYS = 366;

    public function __invoke(
        SupplierPayableReportPageRequest $request,
        GetSupplierPayableReportDatasetHandler $useCase,
        SupplierPayableReportExcelWorkbookBuilder $workbookBuilder,
    ): Response {
        $query = SupplierPayableReportPageQuery::fromValidated($request->validated());

        $rangeRejection = $this->rejectWhenExcelRangeIsTooLong(
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
        $spreadsheet = $workbookBuilder->build($dataset, $query->toViewData());

        return response()->streamDownload(
            static function () use ($spreadsheet): void {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
                $spreadsheet->disconnectWorksheets();
            },
            $this->filename($query),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    private function rejectWhenExcelRangeIsTooLong(?string $from, ?string $to): ?Response
    {
        if ($from === null && $to === null) {
            return null;
        }

        $fromDate = CarbonImmutable::parse($from);
        $toDate = CarbonImmutable::parse($to);

        if (($fromDate->diffInDays($toDate) + 1) <= self::MAX_EXCEL_RANGE_DAYS) {
            return null;
        }

        return response('Export Excel maksimal 366 hari.', 422);
    }

    private function filename(SupplierPayableReportPageQuery $query): string
    {
        if ($query->fromShipmentDate() === null) {
            return 'laporan-hutang-pemasok-seluruh-periode.xlsx';
        }

        return sprintf(
            'laporan-hutang-pemasok-%s-sampai-%s.xlsx',
            $query->fromShipmentDate(),
            $query->toShipmentDate(),
        );
    }
}
