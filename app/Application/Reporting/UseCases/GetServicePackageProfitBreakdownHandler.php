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
            'total_service_component_rupiah' => 0,
            'total_package_gross_profit_rupiah' => 0,
        ];

        foreach ($rows as $row) {
            foreach (array_keys($summary) as $key) {
                if ($key === 'total_packages') {
                    continue;
                }

                $summary[$key] += (int) ($row[$key] ?? 0);
            }
        }

        return $summary;
    }
}
