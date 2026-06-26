<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\WorkItem\WorkItem;

final class NoteDetailRowMapper
{
    public function __construct(
        private readonly WorkItemOperationalStatusResolver $statuses,
        private readonly NoteDetailRowPresentationSupport $presentation,
        private readonly RefundImpactPayloadBuilder $refundImpact,
    ) {
    }

    public function map(array $rows, array $settlements): array
    {
        return array_map(fn (WorkItem $item): array => $this->mapItem($item, $settlements), $rows);
    }

    private function mapItem(WorkItem $item, array $settlements): array
    {
        $settlement = $settlements[$item->id()] ?? [
            'allocated_rupiah' => 0,
            'refunded_rupiah' => 0,
            'net_paid_rupiah' => 0,
            'outstanding_rupiah' => $item->subtotalRupiah()->amount(),
            'settlement_label' => 'hutang',
        ];

        $refunded = (int) ($settlement['refunded_rupiah'] ?? 0);
        $netPaid = (int) ($settlement['net_paid_rupiah'] ?? 0);
        $outstanding = (int) ($settlement['outstanding_rupiah'] ?? $item->subtotalRupiah()->amount());
        $lineStatus = $this->statuses->resolve($outstanding, $refunded);
        $storeLineCount = count($item->storeStockLines());
        $externalLineCount = count($item->externalPurchaseLines());
        $refundImpact = $this->refundImpact->fromWorkItem($item, $netPaid);
        $summary = is_array($refundImpact['effect_summary'] ?? null) ? $refundImpact['effect_summary'] : [];

        $refundStockReturnCount = (int) ($summary['stock_store_return_count'] ?? 0);
        $refundExternalCount = (int) ($summary['external_item_count'] ?? 0);

        return [
            'id' => $item->id(),
            'line_no' => $item->lineNo(),
            'line_label' => $this->presentation->lineLabel($item),
            'line_subtitle' => $this->presentation->lineSubtitle($item),
            'type_label' => $this->presentation->typeLabel($item),
            'transaction_type' => $item->transactionType(),
            'can_correct_service_only' => $item->transactionType() === WorkItem::TYPE_SERVICE_ONLY,
            'status' => $item->status(),
            'settlement_label' => (string) ($settlement['settlement_label'] ?? 'hutang'),
            'subtotal_rupiah' => $item->subtotalRupiah()->amount(),
            'allocated_rupiah' => (int) ($settlement['allocated_rupiah'] ?? 0),
            'refunded_rupiah' => $refunded,
            'net_paid_rupiah' => $netPaid,
            'outstanding_rupiah' => $outstanding,
            'has_service_component' => $item->serviceDetail() !== null,
            'store_stock_count' => $storeLineCount,
            'external_purchase_count' => $externalLineCount,
            'refund_stock_return_count' => $refundStockReturnCount,
            'refund_external_count' => $refundExternalCount,
            'refund_money_possible' => $netPaid > 0,
            'refund_preview_label' => $this->presentation->refundPreviewLabel($refundStockReturnCount, $refundExternalCount),
            'refund_impact' => $refundImpact,
            'line_status' => $lineStatus,
            'can_edit' => $lineStatus === WorkItemOperationalStatusResolver::STATUS_OPEN,
            'can_pay' => $lineStatus === WorkItemOperationalStatusResolver::STATUS_OPEN,
            'can_refund' => $lineStatus === WorkItemOperationalStatusResolver::STATUS_CLOSE,
            'can_view_detail' => true,
        ];
    }
}
