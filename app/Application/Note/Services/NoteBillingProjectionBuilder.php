<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Payment\Services\ResolveNotePayableComponents;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Payment\PaymentComponentAllocationReaderPort;
use App\Ports\Out\Payment\RefundComponentAllocationReaderPort;

final class NoteBillingProjectionBuilder
{
    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly ResolveNotePayableComponents $components,
        private readonly PaymentComponentAllocationReaderPort $payments,
        private readonly RefundComponentAllocationReaderPort $refunds,
        private readonly NoteBillingProjectionRowMapper $rows,
        private readonly NoteBillingProjectionFromWorkspaceRowsBuilder $workspaceRows,
    ) {}

    public function build(string $noteId): ?array
    {
        $note = $this->notes->getById(trim($noteId));
        if ($note === null) {
            return null;
        }

        $items = [];
        foreach ($note->workItems() as $item) {
            $items[$item->id()] = $item;
        }

        $paid = $this->sumAllocated($note->id());
        $refunded = $this->sumRefunded($note->id());
        $rows = [];
        $lineOutstanding = [];

        foreach ($this->components->fromNote($note) as $component) {
            $item = $items[$component->workItemId()] ?? null;
            if ($item === null) {
                continue;
            }
            $rows[] = $this->rows->map($component, $item, $paid, $refunded, $lineOutstanding);
        }

        return $rows;
    }

    public function buildFromWorkspaceRows(array $rows, ?string $noteId = null): array
    {
        if ($noteId !== null) {
            $paid = $this->sumAllocated($noteId);
            $refunded = $this->sumRefunded($noteId);
            foreach ($rows as &$row) {
                foreach ($row['billing_components'] ?? [] as $index => $component) {
                    $key = $component['component_type'].'::'.$component['component_ref_id'];
                    if (array_key_exists($key, $paid) || array_key_exists($key, $refunded)) {
                        $row['billing_components'][$index]['allocated_rupiah'] = $paid[$key] ?? 0;
                        $row['billing_components'][$index]['refunded_rupiah'] = $refunded[$key] ?? 0;
                    }
                }
            }
            unset($row);
        }

        return $this->workspaceRows->build($rows);
    }

    private function sumAllocated(string $noteId): array
    {
        $totals = [];
        foreach ($this->payments->listByNoteId($noteId) as $allocation) {
            $key = $allocation->componentType().'::'.$allocation->componentRefId();
            $totals[$key] = ($totals[$key] ?? 0) + $allocation->allocatedAmountRupiah()->amount();
        }

        return $totals;
    }

    private function sumRefunded(string $noteId): array
    {
        $totals = [];
        foreach ($this->refunds->listByNoteId($noteId) as $allocation) {
            $key = $allocation->componentType().'::'.$allocation->componentRefId();
            $totals[$key] = ($totals[$key] ?? 0) + $allocation->refundedAmountRupiah()->amount();
        }

        return $totals;
    }
}
