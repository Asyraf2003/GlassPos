<section class="workspace-panel workspace-checkout-panel" aria-label="Review dan pembayaran">
    <div class="workspace-checkout-heading" data-detail-only>
        <h4 class="workspace-panel-title">Review & Pembayaran</h4>
        <span class="workspace-line-count" id="workspace-summary-line-count">0 item</span>
    </div>

    <div class="workspace-active-lines" id="workspace-active-line-summary" data-detail-only>
        <div class="workspace-active-lines-empty">Belum ada rincian.</div>
    </div>

    <div class="workspace-checkout-total">
        <span>Total</span>
        <strong><span class="workspace-currency">Rp</span><span id="workspace-note-total-text">0</span></strong>
    </div>

    <div class="workspace-simple-actions" data-simple-only>
        <div class="workspace-action-grid">
            <button type="button" class="btn btn-outline-primary" data-simple-payment-action="skip" disabled>
                Simpan Nota
            </button>
            <button type="button" class="btn btn-outline-primary" data-simple-payment-action="partial" disabled>
                Bayar Sebagian
            </button>
            <button type="button" class="btn btn-primary" data-simple-payment-action="full" disabled>
                Bayar Penuh
            </button>
        </div>

        <div class="workspace-partial-quick d-none" id="workspace-simple-partial-panel">
            <label for="workspace-simple-partial-amount" class="form-label">Nominal dibayar sekarang</label>
            <div class="workspace-partial-input-row">
                <div class="workspace-money-prefix">
                    <span>Rp</span>
                    <input
                        type="text"
                        inputmode="numeric"
                        autocomplete="off"
                        id="workspace-simple-partial-amount"
                        class="form-control"
                        placeholder="0"
                    >
                </div>
                <button type="button" class="btn btn-primary" id="workspace-simple-partial-submit">Bayar</button>
            </div>
            <button type="button" class="workspace-text-button" id="workspace-simple-partial-cancel">Batal pembayaran sebagian</button>
        </div>
    </div>

    <div class="workspace-detail-actions" data-detail-only>
        <button type="button" class="btn btn-primary w-100" id="workspace-open-payment-dialog">
            Proses Nota
        </button>

        @if (($workspaceMode ?? 'create') === 'edit' && ($canShowRefundModal ?? false))
            <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#workspace-refund-modal">
                Pengembalian Dana
            </button>
        @endif
    </div>
</section>
