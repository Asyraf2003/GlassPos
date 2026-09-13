<?php

declare(strict_types=1);

namespace App\Application\Reporting\UseCases;

use App\Application\Reporting\Services\TransactionReportingReconciliationService;
use App\Application\Reporting\Services\TransactionSummaryPerNoteBuilder;
use App\Application\Shared\DTO\Result;
use App\Ports\Out\Reporting\TransactionReportingSourceReaderPort;

final class GetTransactionSummaryPerNoteHandler
{
    public function __construct(
        private readonly TransactionReportingSourceReaderPort $sourceReader,
        private readonly TransactionSummaryPerNoteBuilder $builder,
        private readonly TransactionReportingReconciliationService $reconciliation,
    ) {
    }

    public function handle(string $fromTransactionDate, string $toTransactionDate): Result
    {
        $rawRows = $this->sourceReader->getTransactionSummaryPerNoteRows(
            $fromTransactionDate,
            $toTransactionDate,
        );

        $rows = $this->builder->build($rawRows);

        $this->reconciliation->assertTransactionSummaryMatches(
            $rows,
            $this->expectedFromRawRows($rawRows),
        );

        return Result::success([
            'rows' => $this->payloadRows($rows, $rawRows),
        ]);
    }

    private function expectedFromRawRows(array $rawRows): array
    {
        $expected = [
            'total_notes' => count($rawRows),
            'gross_transaction_rupiah' => 0,
            'allocated_payment_rupiah' => 0,
            'refunded_rupiah' => 0,
        ];

        foreach ($rawRows as $row) {
            $expected['gross_transaction_rupiah'] += (int) ($row['gross_transaction_rupiah'] ?? 0);
            $expected['allocated_payment_rupiah'] += (int) ($row['allocated_payment_rupiah'] ?? 0);
            $expected['refunded_rupiah'] += (int) ($row['refunded_rupiah'] ?? 0);
        }

        return $expected;
    }

    private function payloadRows(array $rows, array $rawRows): array
    {
        return array_map(
            static function ($row, int $index) use ($rawRows): array {
                $payload = $row->toArray();
                $raw = $rawRows[$index] ?? [];

                $surplusPaid = (int) ($raw['surplus_refund_paid_rupiah'] ?? 0);
                $remainingDue = (int) ($raw['remaining_refund_due_rupiah'] ?? 0);

                $payload['surplus_refund_paid_rupiah'] = $surplusPaid;
                $payload['remaining_refund_due_rupiah'] = $remainingDue;
                $payload['net_cash_collected_rupiah'] = (int) ($raw['gross_payment_rupiah'] ?? $payload['allocated_payment_rupiah'])
                    - $payload['refunded_rupiah'] - $surplusPaid;

                return $payload;
            },
            $rows,
            array_keys($rows),
        );
    }
}
