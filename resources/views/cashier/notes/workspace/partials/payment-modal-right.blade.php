<div class="col-12 col-xl-5" data-handset-payment-main>
    <div class="workspace-gform-panel h-100 d-flex flex-column">
        <div class="fw-semibold mb-3">Pembayaran</div>
        <div class="visually-hidden" id="workspace-payment-mode-text" aria-hidden="true">Belum dipilih</div>

        <div class="d-grid gap-2 mb-3" id="workspace-payment-choice-list">
            <button
                type="button"
                class="btn btn-outline-secondary text-start"
                id="workspace-payment-choice-full"
                data-payment-choice="full"
            >
                Bayar Penuh
            </button>

            <div class="visually-hidden" aria-hidden="true">
                Bayar Penuh memakai sisa tagihan dari sistem.
                Transfer mencatat nominal itu; tunai membuka kalkulator kembalian.
            </div>

            <button
                type="button"
                class="btn btn-outline-secondary text-start"
                id="workspace-payment-choice-partial"
                data-payment-choice="partial"
            >
                Bayar Sebagian
            </button>

            <button
                type="button"
                class="btn btn-outline-secondary text-start"
                id="workspace-payment-choice-skip"
                data-payment-choice="skip"
            >
                Simpan Nota Tanpa Pembayaran
            </button>
        </div>

        @if (($workspaceMode ?? 'create') === 'edit' && !empty($workspacePaymentSettlement['explanation']))
            <div class="workspace-gform-panel mb-3" id="workspace-payment-settlement-explanation">
                <div class="fw-semibold mb-2">Pembayaran tersimpan</div>
                <div class="small text-muted">
                    Total: {{ number_format((int) ($workspacePaymentSettlement['explanation']['gross_total_rupiah'] ?? 0), 0, ',', '.') }}
                </div>
                <div class="small text-muted">
                    Dibayar: {{ number_format((int) ($workspacePaymentSettlement['explanation']['net_paid_rupiah'] ?? 0), 0, ',', '.') }}
                </div>
                <div class="small text-muted">
                    Sisa: {{ number_format((int) ($workspacePaymentSettlement['explanation']['outstanding_rupiah'] ?? 0), 0, ',', '.') }}
                </div>
            </div>
        @endif

        <div id="workspace-payment-panel-partial" class="d-none">
            <div class="workspace-gform-panel">
                <div class="form-group mb-0">
                    <label for="inline_payment_amount_paid_display" class="form-label">Nominal Dibayar Sekarang</label>
                    <input
                        type="text"
                        id="inline_payment_amount_paid_display"
                        value="{{ !empty($oldInlinePayment['amount_paid_rupiah']) ? number_format((int) $oldInlinePayment['amount_paid_rupiah'], 0, ',', '.') : '' }}"
                        class="form-control form-control-lg fs-2 fw-bold py-3"
                        inputmode="numeric"
                        placeholder="Masukkan nominal pembayaran sebagian"
                    >
                </div>
            </div>
        </div>

        <div class="workspace-gform-panel mt-3" data-handset-trim>
            <div class="form-group mb-0">
                <label for="inline_payment_paid_at_display" class="form-label">Tanggal Bayar</label>
                <input
                    type="date"
                    data-ui-date="single"
                    id="inline_payment_paid_at_display"
                    value="{{ $oldInlinePayment['paid_at'] ?? $oldNote['transaction_date'] }}"
                    class="form-control"
                >
            </div>
        </div>
    </div>
</div>
