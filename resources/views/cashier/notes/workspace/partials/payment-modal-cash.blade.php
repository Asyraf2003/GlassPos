<div style="max-width: 460px; margin: 0 auto;">
    <div class="workspace-gform-panel">
        <div class="visually-hidden">Kalkulator Tunai</div>
        <div class="visually-hidden" id="workspace-cash-mode-text" aria-hidden="true">Bayar Penuh</div>

        <div class="d-grid gap-3">
            <div class="workspace-gform-panel text-center">
                <div class="small text-muted mb-2">Tagihan</div>
                <div class="fs-1 fw-bold lh-sm" id="workspace-cash-payable-text">0</div>
            </div>

            <div class="workspace-gform-panel text-center" data-money-input-group>
                <label for="inline_payment_amount_received_display" class="small text-muted mb-2">Uang Pelanggan</label>

                <input type="hidden" value="" data-money-raw data-cash-received-raw>

                <input
                    type="text"
                    id="inline_payment_amount_received_display"
                    value=""
                    class="form-control border-0 bg-transparent text-center fs-1 fw-bold lh-sm p-0 shadow-none"
                    inputmode="numeric"
                    placeholder="0"
                    data-money-display
                    autocomplete="off"
                >
            </div>

            <div class="workspace-gform-panel text-center">
                <div class="small text-muted mb-2">Kembalian</div>
                <div class="fs-1 fw-bold lh-sm" id="workspace-cash-change-text">0</div>
            </div>

            <div class="workspace-gform-panel text-center">
                <div class="small text-muted mb-2">Sisa</div>
                <div class="fs-3 fw-bold lh-sm" id="workspace-cash-remaining-text">0</div>
            </div>
        </div>
    </div>
</div>
