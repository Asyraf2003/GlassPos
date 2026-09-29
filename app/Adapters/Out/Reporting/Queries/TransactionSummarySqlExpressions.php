<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

final class TransactionSummarySqlExpressions
{
    public function allocated(TransactionSummaryQueryContext $context): string
    {
        if ($context->cutoff === null) {
            return 'COALESCE(cash_payment_totals.allocated_payment_rupiah, 0)';
        }
        return 'CASE WHEN historical_revision.id IS NOT NULL '
            .'THEN LEAST('.$context->effectiveTotalSql.', '.$this->settlementAvailable().') '
            .'ELSE COALESCE(cash_payment_totals.allocated_payment_rupiah, 0) END';
    }

    public function outstanding(TransactionSummaryQueryContext $context): string
    {
        if ($context->cutoff === null) {
            return 'CASE WHEN COALESCE(cash_refund_totals.refunded_rupiah, 0) > 0 '
                .'THEN COALESCE(note_history_projection.outstanding_rupiah, GREATEST(notes.total_rupiah - COALESCE(cash_payment_totals.allocated_payment_rupiah, 0) + COALESCE(cash_refund_totals.refunded_rupiah, 0), 0)) '
                .'ELSE GREATEST(notes.total_rupiah - COALESCE(cash_payment_totals.allocated_payment_rupiah, 0), 0) END';
        }
        $legacyAvailable = 'GREATEST(COALESCE(cash_payment_totals.allocated_payment_rupiah, 0) '
            .'- COALESCE(cash_refund_totals.refunded_rupiah, 0) '
            .'- GREATEST(COALESCE(refund_due_totals.refund_due_rupiah, 0), COALESCE(surplus_refund_payment_totals.surplus_refund_paid_rupiah, 0)), 0)';
        return 'CASE WHEN historical_revision.id IS NOT NULL '
            .'THEN GREATEST('.$context->effectiveTotalSql.' - '.$this->settlementAvailable().', 0) '
            .'ELSE GREATEST('.$context->effectiveTotalSql.' - '.$legacyAvailable.', 0) END';
    }

    private function settlementAvailable(): string
    {
        return 'GREATEST(COALESCE(historical_payments.gross_payment_rupiah, 0) '
            .'- COALESCE(cash_refund_totals.refunded_rupiah, 0) '
            .'- GREATEST(COALESCE(refund_due_totals.refund_due_rupiah, 0), COALESCE(surplus_refund_payment_totals.surplus_refund_paid_rupiah, 0)), 0)';
    }
}
