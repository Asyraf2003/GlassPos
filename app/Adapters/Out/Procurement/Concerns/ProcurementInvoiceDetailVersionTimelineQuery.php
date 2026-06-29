<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement\Concerns;

use Illuminate\Support\Facades\DB;
use JsonException;

trait ProcurementInvoiceDetailVersionTimelineQuery
{
    /**
     * @return list<array<string, mixed>>
     */
    private function getVersionTimelineRows(string $supplierInvoiceId): array
    {
        return DB::table('supplier_invoice_versions')
            ->where('supplier_invoice_id', $supplierInvoiceId)
            ->orderByDesc('revision_no')
            ->get(['revision_no', 'event_name', 'changed_at', 'changed_by_actor_id', 'change_reason', 'snapshot_json'])
            ->map(fn (object $row): array => [
                'revision_no' => (int) $row->revision_no,
                'event_name' => (string) $row->event_name,
                'changed_at' => (string) $row->changed_at,
                'changed_by_actor_id' => $row->changed_by_actor_id !== null ? (string) $row->changed_by_actor_id : null,
                'change_reason' => $row->change_reason !== null ? (string) $row->change_reason : null,
                'snapshot' => $this->decodeVersionSnapshot((string) $row->snapshot_json),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeVersionSnapshot(string $snapshotJson): array
    {
        try {
            $decoded = json_decode($snapshotJson, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : [];
        } catch (JsonException) {
            return [];
        }
    }
}
