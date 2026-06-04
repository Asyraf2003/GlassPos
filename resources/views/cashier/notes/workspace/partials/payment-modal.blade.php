<div
    class="modal fade"
    id="workspace-payment-modal"
    tabindex="-1"
    aria-hidden="true"
    @isset($workspacePaymentSettlement['amount_rupiah'])
        data-backend-payable-rupiah="{{ (int) $workspacePaymentSettlement['amount_rupiah'] }}"
    @endisset
    @isset($workspacePaymentSettlement['explanation']['basis'])
        data-backend-payment-basis="{{ $workspacePaymentSettlement['explanation']['basis'] }}"
    @endisset
    @isset($workspacePaymentSettlement['explanation']['net_paid_rupiah'])
        data-backend-net-paid-rupiah="{{ (int) $workspacePaymentSettlement['explanation']['net_paid_rupiah'] }}"
    @endisset
    @isset($workspacePaymentSettlement['explanation']['gross_total_rupiah'])
        data-backend-gross-total-rupiah="{{ (int) $workspacePaymentSettlement['explanation']['gross_total_rupiah'] }}"
    @endisset
>
    <div class="modal-dialog modal-dialog-centered modal-xl" id="workspace-payment-modal-dialog">
        <div class="modal-content">
            <input
                type="hidden"
                id="inline_payment_decision_hidden"
                name="inline_payment[decision]"
                value="{{ $oldInlinePayment['decision'] ?? 'skip' }}"
            >
            <input
                type="hidden"
                id="inline_payment_method_hidden"
                name="inline_payment[payment_method]"
                value="{{ $oldInlinePayment['payment_method'] ?? '' }}"
            >
            <input
                type="hidden"
                id="inline_payment_paid_at_hidden"
                name="inline_payment[paid_at]"
                value="{{ $oldInlinePayment['paid_at'] ?? $oldNote['transaction_date'] }}"
            >
            <input
                type="hidden"
                id="inline_payment_amount_paid_rupiah"
                name="inline_payment[amount_paid_rupiah]"
                value="{{ $oldInlinePayment['amount_paid_rupiah'] ?? '' }}"
            >
            <input
                type="hidden"
                id="inline_payment_amount_received_rupiah"
                name="inline_payment[amount_received_rupiah]"
                value="{{ $oldInlinePayment['amount_received_rupiah'] ?? '' }}"
            >

            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="workspace-payment-modal-title">Proses Nota</h5>
                    <p class="mb-0 text-muted small" id="workspace-payment-modal-subtitle">
                        Cek ringkasan transaksi, lalu lanjutkan dengan metode pembayaran.
                    </p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div id="workspace-payment-standard-view">
                    <div class="row g-4">
                        @include('cashier.notes.workspace.partials.payment-modal-left')
                        @include('cashier.notes.workspace.partials.payment-modal-right')
                    </div>
                </div>

                <div id="workspace-payment-cash-view" class="d-none">
                    @include('cashier.notes.workspace.partials.payment-modal-cash')
                </div>
            </div>

            @include('cashier.notes.workspace.partials.payment-modal-footer')
        </div>
    </div>
</div>
