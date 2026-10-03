<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement\Concerns;

use App\Core\Procurement\SupplierInvoice\SupplierInvoice;

trait RecordsSupplierInvoiceHistory
{
    private function toVersionRecord(
        SupplierInvoice $supplierInvoice,
        int $revisionNo,
        string $eventName,
        \DateTimeImmutable $occurredAt,
        array $context,
        array $snapshot,
    ): array {
        return [
            'id' => $this->uuid->generate(),
            'supplier_invoice_id' => $supplierInvoice->id(),
            'revision_no' => $revisionNo,
            'event_name' => $eventName,
            'changed_by_actor_id' => $context['actor_id'],
            'change_reason' => $context['reason'],
            'changed_at' => $occurredAt,
            'snapshot_json' => json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ];
    }

    private function toAuditEventRecord(
        string $auditEventId,
        SupplierInvoice $supplierInvoice,
        int $revisionNo,
        string $eventName,
        \DateTimeImmutable $occurredAt,
        array $context,
        array $snapshot,
    ): array {
        return [
            'id' => $auditEventId,
            'bounded_context' => 'procurement',
            'aggregate_type' => 'supplier_invoice',
            'aggregate_id' => $supplierInvoice->id(),
            'event_name' => $eventName,
            'actor_id' => $context['actor_id'],
            'actor_role' => $context['actor_role'],
            'reason' => $context['reason'],
            'source_channel' => $context['source_channel'],
            'request_id' => null,
            'correlation_id' => $context['source_operation_id'] ?? null,
            'occurred_at' => $occurredAt,
            'metadata_json' => json_encode([
                'supplier_invoice' => $snapshot,
                'revision_no' => $revisionNo,
                ...(($context['source_operation_id'] ?? null) === null ? [] : [
                    'source_merge_operation_id' => $context['source_operation_id'],
                    'before_revision_no' => $revisionNo - 1,
                    'after_revision_no' => $revisionNo,
                ]),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ];
    }

    private function toAuditSnapshotRecord(
        string $auditEventId,
        string $snapshotKind,
        array $snapshot,
        \DateTimeImmutable $occurredAt,
    ): array {
        return [
            'id' => $this->uuid->generate(),
            'audit_event_id' => $auditEventId,
            'snapshot_kind' => $snapshotKind,
            'payload_json' => json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'created_at' => $occurredAt,
        ];
    }
}
