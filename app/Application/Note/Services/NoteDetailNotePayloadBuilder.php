<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

final class NoteDetailNotePayloadBuilder
{
    public function build(
        array $base,
        array $workspacePanel,
        array $operational,
        array $refundPaymentOptions,
        array $refundRows,
        array $billingRows,
        array $revisionTimeline,
        array $surplusDisposition,
        array $surplusDispositionAuditTimeline,
        array $history,
        bool $isOpen,
        bool $isClosed,
        bool $isRefunded,
        bool $hasOutstandingBillingRow,
    ): array {
        $openLineCount = (int) ($workspacePanel['line_summary']['open_count'] ?? 0);
        $netPaid = (int) ($operational['net_paid_rupiah'] ?? 0);
        $outstanding = (int) ($operational['outstanding_rupiah'] ?? 0);
        $status = (string) ($operational['operational_status'] ?? '');
        $refundRequired = max($netPaid - (int) $operational['grand_total_rupiah'], 0);
        $canShowPayment = ! $isRefunded
            && (int) ($operational['outstanding_rupiah'] ?? 0) > 0
            && $hasOutstandingBillingRow;
        $statusLabel = $this->statusLabel($status, $netPaid, $isRefunded);

        return $base + [
            'operational_status' => $operational['operational_status'],
            'is_open' => $isOpen,
            'is_closed' => $isClosed,
            'is_refunded' => $isRefunded,
            'grand_total_rupiah' => $operational['grand_total_rupiah'],
            'total_allocated_rupiah' => $operational['total_allocated_rupiah'],
            'total_refunded_rupiah' => $operational['total_refunded_rupiah'],
            'net_paid_rupiah' => $operational['net_paid_rupiah'],
            'outstanding_rupiah' => $operational['outstanding_rupiah'],
            'refund_required_rupiah' => $refundRequired,
            'can_add_rows' => $isOpen,
            'can_show_edit_actions' => $isOpen,
            'can_edit_workspace' => ! $isRefunded && ($isOpen || $isClosed),
            'can_show_workspace_panel' => $isOpen || $isClosed,
            'can_show_payment_form' => $canShowPayment,
            'can_show_partial_payment_action' => $canShowPayment && $netPaid <= 0,
            'can_show_settle_payment_action' => $canShowPayment && $outstanding > 0,
            'payment_status_label' => $statusLabel,
            'can_show_refund_form' => $refundRows !== [],
            'refund_payment_options' => $refundPaymentOptions,
            'can_show_correction_actions' => false,
            'correction_notice' => $isClosed
                ? 'Nota sudah lunas. Pembalikan dilakukan lewat pengembalian dana.'
                : ($isRefunded ? 'Nota sudah dikembalikan dan tidak bisa diedit dari workspace.' : null),
            'line_summary' => $workspacePanel['line_summary'],
            'rows' => $workspacePanel['rows'],
            'refund_rows' => $refundRows,
            'billing_rows' => $billingRows,
            'revision_timeline' => $revisionTimeline,
            'surplus_disposition' => $surplusDisposition,
            'surplus_disposition_audit_timeline' => $surplusDispositionAuditTimeline,
            'correction_history' => $history,
        ];
    }

    private function statusLabel(string $status, int $netPaid, bool $isRefunded): string
    {
        if ($isRefunded) {
            return 'Dikembalikan';
        }

        return match ($status) {
            'close', 'closed', 'paid' => 'Lunas',
            'canceled' => 'Batal',
            default => $netPaid > 0 ? 'Belum Lunas / Sebagian' : 'Belum Lunas',
        };
    }
}
