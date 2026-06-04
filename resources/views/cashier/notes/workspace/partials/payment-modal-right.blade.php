<div class="col-12 col-xl-5">
    <div class="workspace-gform-panel h-100 d-flex flex-column">
        <div class="fw-semibold mb-1">Pilih Aksi</div>

        <div class="small text-muted">Mode Aktif</div>
        <div class="fw-semibold fs-5 mb-3" id="workspace-payment-mode-text">Belum dipilih</div>

        <div class="d-grid gap-2 mb-3" id="workspace-payment-choice-list">
            <button
                type="button"
                class="btn btn-light text-start"
                id="workspace-payment-choice-full"
                data-payment-choice="full"
            >
                Bayar Penuh
            </button>

            <button
                type="button"
                class="btn btn-light text-start"
                id="workspace-payment-choice-partial"
                data-payment-choice="partial"
            >
                Bayar Sebagian
            </button>

            <button
                type="button"
                class="btn btn-light text-start"
                id="workspace-payment-choice-skip"
                data-payment-choice="skip"
            >
                Simpan Nota Tanpa Pembayaran
            </button>
        </div>

        @if (($workspaceMode ?? 'create') === 'edit' && !empty($workspacePaymentSettlement['explanation']))
            <div class="workspace-gform-panel mb-3" id="workspace-payment-settlement-explanation">
                <div class="fw-semibold mb-2">Ringkasan pembayaran tersimpan</div>
                <div class="small text-muted">
                    Total nota: {{ number_format((int) ($workspacePaymentSettlement['explanation']['gross_total_rupiah'] ?? 0), 0, ',', '.') }}
                </div>
                <div class="small text-muted">
                    Sudah dibayar: {{ number_format((int) ($workspacePaymentSettlement['explanation']['net_paid_rupiah'] ?? 0), 0, ',', '.') }}
                </div>
                <div class="small text-muted">
                    Sisa yang perlu dibayar: {{ number_format((int) ($workspacePaymentSettlement['explanation']['outstanding_rupiah'] ?? 0), 0, ',', '.') }}
                </div>
            </div>
        @endif

        <div id="workspace-payment-panel-partial" class="d-none">
            <div class="workspace-gform-panel">
                <div class="fw-semibold mb-1">Nominal Dibayar Sekarang</div>
                <div class="text-muted small mb-3">
                    Isi nominal pembayaran sebagian, lalu pilih transfer atau cash.
                </div>

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
                    <div class="form-text">
                        Nominal akan dibatasi maksimal sebesar total nota.
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
