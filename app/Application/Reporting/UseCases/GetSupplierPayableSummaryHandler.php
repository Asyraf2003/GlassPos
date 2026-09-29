<?php

declare(strict_types=1);

namespace App\Application\Reporting\UseCases;

use App\Application\Reporting\Services\SupplierPayableReportingReconciliationService;
use App\Application\Reporting\Services\SupplierPayableSummaryBuilder;
use App\Application\Shared\DTO\Result;
use App\Ports\Out\Reporting\SupplierPayableReportingSourceReaderPort;

final class GetSupplierPayableSummaryHandler
{
    public function __construct(
        private readonly SupplierPayableReportingSourceReaderPort $sourceReader,
        private readonly SupplierPayableSummaryBuilder $builder,
        private readonly SupplierPayableReportingReconciliationService $reconciliation,
    ) {}

    public function handle(
        ?string $fromShipmentDate,
        ?string $toShipmentDate,
        string $referenceDate,
    ): Result {
        $rawRows = $this->sourceReader->getSupplierPayableSummaryRows(
            $fromShipmentDate,
            $toShipmentDate,
        );

        $rows = $this->builder->build($rawRows, $toShipmentDate ?? $referenceDate);

        $expected = $this->sourceReader->getSupplierPayableSummaryReconciliation(
            $fromShipmentDate,
            $toShipmentDate,
        );

        $this->reconciliation->assertSupplierPayableSummaryMatches($rows, $expected);

        return Result::success([
            'rows' => array_map(
                static fn ($row, array $raw): array => array_merge($row->toArray(), $raw),
                $rows, $rawRows,
            ),
        ]);
    }
}
