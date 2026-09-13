<?php

declare(strict_types=1);

namespace App\Application\Note\Services\CurrentRevision;

use App\Core\Note\Revision\NoteRevisionLineSnapshot;
use App\Core\Note\WorkItem\WorkItem;

final class CurrentRevisionDetailBaseRowMapper
{
    public function __construct(
        private readonly CurrentRevisionLinePresentationSupport $presentation,
        private readonly CurrentRevisionPackageBreakdownMapper $packages,
        private readonly CurrentRevisionStoreStockLineLabelResolver $storeStockLabels,
    ) {
    }

    /**
     * @param array<string, int|string> $settlement
     * @return array<string, mixed>
     */
    public function map(
        string $key,
        NoteRevisionLineSnapshot $line,
        array $settlement,
        int $outstanding,
        int $refunded,
        int $netPaid,
    ): array {
        $payload = $line->payload();
        $storeLineCount = $this->lineCount($payload, 'store_stock_lines');

        return [
            'id' => $key,
            'line_no' => $line->lineNo(),
            'line_label' => $this->lineLabel($line, $payload, $storeLineCount),
            'line_subtitle' => $line->transactionType() === WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE
                ? $this->externalSummary($payload)
                : $this->storeStockLabels->summary($payload),
            'type_label' => $this->presentation->typeLabel($line->transactionType()),
            'transaction_type' => $line->transactionType(),
            'can_correct_service_only' => $line->transactionType() === WorkItem::TYPE_SERVICE_ONLY,
            'status' => $line->status(),
            'settlement_label' => (string) ($settlement['settlement_label'] ?? 'hutang'),
            'subtotal_rupiah' => $line->subtotalRupiah(),
            'allocated_rupiah' => (int) ($settlement['allocated_rupiah'] ?? 0),
            'refunded_rupiah' => $refunded,
            'net_paid_rupiah' => $netPaid,
            'outstanding_rupiah' => $outstanding,
            'has_service_component' => $line->serviceLabel() !== null,
            'store_stock_count' => $storeLineCount,
            'external_purchase_count' => $this->lineCount($payload, 'external_purchase_lines'),
            'package_breakdown' => $this->packages->map($line, $payload),
        ];
    }

    /** @param array<string, mixed> $payload */
    private function lineCount(array $payload, string $key): int
    {
        return count(is_array($payload[$key] ?? null) ? $payload[$key] : []);
    }

    /** @param array<string, mixed> $payload */
    private function externalSummary(array $payload): ?string
    {
        $parts = [];
        foreach (($payload['external_purchase_lines'] ?? []) as $line) {
            if (is_array($line)) {
                $parts[] = (string) ($line['cost_description'] ?? '').' x'.(int) ($line['qty'] ?? 0);
            }
        }

        return $parts === [] ? null : implode(' • ', $parts);
    }

    private function lineLabel(NoteRevisionLineSnapshot $line, array $payload, int $storeLineCount): string
    {
        $label = $line->serviceLabel();

        return $label === null || trim($label) === ''
            ? ($storeLineCount > 0 ? $this->storeStockLabels->primary($payload) : 'Rincian Nota')
            : $label;
    }
}
