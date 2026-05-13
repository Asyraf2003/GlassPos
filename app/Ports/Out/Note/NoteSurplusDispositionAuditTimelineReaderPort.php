<?php

declare(strict_types=1);

namespace App\Ports\Out\Note;

interface NoteSurplusDispositionAuditTimelineReaderPort
{
    /**
     * @return list<array{
     *   event_id:string,
     *   event_name:string,
     *   disposition_id:string,
     *   refund_payment_id:?string,
     *   note_revision_settlement_id:string,
     *   note_revision_id:string,
     *   disposition_type:string,
     *   amount_rupiah:int,
     *   before_pending_rupiah:int,
     *   after_pending_rupiah:int,
     *   refund_due_rupiah:int,
     *   active_refund_paid_rupiah:int,
     *   remaining_refund_due_rupiah:int,
     *   effective_date:?string,
     *   actor_id:?string,
     *   actor_role:?string,
     *   reason:?string,
     *   occurred_at:string
     * }>
     */
    public function findSurplusAuditEventsByNoteRootId(string $noteRootId, int $limit = 10): array;

    /**
     * @return list<array{
     *   event_id:string,
     *   event_name:string,
     *   disposition_id:string,
     *   refund_payment_id:?string,
     *   note_revision_settlement_id:string,
     *   note_revision_id:string,
     *   disposition_type:string,
     *   amount_rupiah:int,
     *   before_pending_rupiah:int,
     *   after_pending_rupiah:int,
     *   refund_due_rupiah:int,
     *   active_refund_paid_rupiah:int,
     *   remaining_refund_due_rupiah:int,
     *   effective_date:?string,
     *   actor_id:?string,
     *   actor_role:?string,
     *   reason:?string,
     *   occurred_at:string
     * }>
     */
    public function findRefundDueCreatedEventsByNoteRootId(string $noteRootId, int $limit = 10): array;
}
