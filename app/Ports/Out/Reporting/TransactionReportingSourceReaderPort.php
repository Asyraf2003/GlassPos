<?php

declare(strict_types=1);

namespace App\Ports\Out\Reporting;

interface TransactionReportingSourceReaderPort
{
    /**
     * @return list<array{
     *   note_id:string,
     *   transaction_date:string,
     *   customer_name:string,
     *   gross_transaction_rupiah:int,
     *   allocated_payment_rupiah:int,
     *   refunded_rupiah:int,
     *   refund_due_rupiah:int
     * }>
     */
    public function getTransactionSummaryPerNoteRows(
        string $fromTransactionDate,
        string $toTransactionDate,
    ): array;

    /**
     * @return array{
     *   total_notes:int,
     *   gross_transaction_rupiah:int,
     *   allocated_payment_rupiah:int,
     *   refunded_rupiah:int,
     *   refund_due_rupiah:int
     * }
     */
    public function getTransactionSummaryPerNoteReconciliation(
        string $fromTransactionDate,
        string $toTransactionDate,
    ): array;

    /**
     * @return list<array{
     *   note_id:string,
     *   event_date:string,
     *   event_type:string,
     *   direction:string,
     *   event_amount_rupiah:int,
     *   payment_method?:?string,
     *   customer_payment_id:?string,
     *   refund_id:?string,
     *   source_table:string,
     *   source_id:string,
     *   source_disposition_id:?string
     * }>
     */
    public function getTransactionCashLedgerPerNoteRows(
        string $fromEventDate,
        string $toEventDate,
    ): array;

    /**
     * @return array{
     *   total_in_rupiah:int,
     *   total_out_rupiah:int
     * }
     */
    public function getTransactionCashLedgerPerNoteReconciliation(
        string $fromEventDate,
        string $toEventDate,
    ): array;
}
