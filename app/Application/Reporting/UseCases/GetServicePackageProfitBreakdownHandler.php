<?php

declare(strict_types=1);

namespace App\Application\Reporting\UseCases;

use App\Application\Shared\DTO\Result;
use App\Ports\Out\Reporting\ServicePackageProfitBreakdownSourceReaderPort;

final class GetServicePackageProfitBreakdownHandler
{
    public function __construct(
        private readonly ServicePackageProfitBreakdownSourceReaderPort $sourceReader,
    ) {
    }

    public function handle(string $fromTransactionDate, string $toTransactionDate): Result
    {
        $rows = $this->sourceReader->getRows($fromTransactionDate, $toTransactionDate);

        return Result::success([
            'rows' => $rows,
            'summary' => $this->summary($rows),
        ]);
    }

    public function handleSummaryOnly(string $fromTransactionDate, string $toTransactionDate): Result
    {
        return Result::success([
            'summary' => $this->sourceReader->getSummary($fromTransactionDate, $toTransactionDate),
        ]);
    }

    /**
     * @param list<array<string, int|string|null>> $rows
     * @return array<string, int>
     */
    private function summary(array $rows): array
    {
        $summary = [
            'total_packages' => count($rows),
            'package_sold_amount_rupiah' => 0,
            'parts_total_rupiah' => 0,
            'sparepart_cogs_rupiah' => 0,
            'sparepart_margin_rupiah' => 0,
            'service_fee_rupiah' => 0,
            'package_profit_rupiah' => 0,
            'total_service_component_rupiah' => 0,
            'refunded_product_component_rupiah' => 0,
            'refunded_service_component_rupiah' => 0,
            'total_package_gross_profit_rupiah' => 0,
        ];

        $rowKeyBySummaryKey = [
            'package_sold_amount_rupiah' => 'package_sold_amount_rupiah',
            'parts_total_rupiah' => 'parts_total_rupiah',
            'sparepart_cogs_rupiah' => 'sparepart_cogs_rupiah',
            'sparepart_margin_rupiah' => 'sparepart_margin_rupiah',
            'service_fee_rupiah' => 'service_price_rupiah',
            'package_profit_rupiah' => 'package_profit_rupiah',
            'total_service_component_rupiah' => 'total_service_component_rupiah',
            'refunded_product_component_rupiah' => 'refunded_product_component_rupiah',
            'refunded_service_component_rupiah' => 'refunded_service_component_rupiah',
            'total_package_gross_profit_rupiah' => 'total_package_gross_profit_rupiah',
        ];

        foreach ($rows as $row) {
            foreach ($rowKeyBySummaryKey as $summaryKey => $rowKey) {
                $summary[$summaryKey] += (int) ($row[$rowKey] ?? 0);
            }
        }

        return $summary;
    }
}
