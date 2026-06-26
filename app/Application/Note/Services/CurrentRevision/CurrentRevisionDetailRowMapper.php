<?php

declare(strict_types=1);

namespace App\Application\Note\Services\CurrentRevision;

use App\Application\Note\Services\WorkItemOperationalStatusResolver;
use App\Core\Note\Revision\NoteRevisionLineSnapshot;

final class CurrentRevisionDetailRowMapper
{
    public function __construct(
        private readonly WorkItemOperationalStatusResolver $statuses,
        private readonly CurrentRevisionDetailBaseRowMapper $baseRows,
        private readonly CurrentRevisionDetailRefundPayloadMapper $refunds,
        private readonly CurrentRevisionLineBillingComponentMapper $billingComponents,
    ) {
    }

    /**
     * @param list<NoteRevisionLineSnapshot> $lines
     * @param array<string, array<string, int|string>> $settlements
     * @return list<array<string, mixed>>
     */
    public function map(array $lines, array $settlements): array
    {
        return array_map(fn (NoteRevisionLineSnapshot $line): array => $this->mapLine($line, $settlements), $lines);
    }

    /**
     * @param array<string, array<string, int|string>> $settlements
     * @return array<string, mixed>
     */
    private function mapLine(NoteRevisionLineSnapshot $line, array $settlements): array
    {
        $key = $line->workItemRootId() ?? $line->id();
        $settlement = $settlements[$key] ?? $this->defaultSettlement($line);
        $payload = $line->payload();
        $netPaid = (int) ($settlement['net_paid_rupiah'] ?? 0);
        $outstanding = (int) ($settlement['outstanding_rupiah'] ?? $line->subtotalRupiah());
        $refunded = (int) ($settlement['refunded_rupiah'] ?? 0);
        $lineStatus = $this->statuses->resolve($outstanding, $refunded);

        return array_merge(
            $this->baseRows->map($key, $line, $settlement, $outstanding, $refunded, $netPaid),
            $this->refunds->map($payload, $netPaid),
            [
                'billing_components' => $this->billingComponents->map($line, $payload),
                'line_status' => $lineStatus,
                'can_edit' => $lineStatus === WorkItemOperationalStatusResolver::STATUS_OPEN,
                'can_pay' => $lineStatus === WorkItemOperationalStatusResolver::STATUS_OPEN,
                'can_refund' => $lineStatus === WorkItemOperationalStatusResolver::STATUS_CLOSE,
                'can_view_detail' => true,
            ],
        );
    }

    /** @return array<string, int|string> */
    private function defaultSettlement(NoteRevisionLineSnapshot $line): array
    {
        return [
            'allocated_rupiah' => 0,
            'refunded_rupiah' => 0,
            'net_paid_rupiah' => 0,
            'outstanding_rupiah' => $line->subtotalRupiah(),
            'settlement_label' => 'hutang',
        ];
    }
}
