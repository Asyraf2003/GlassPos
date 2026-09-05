<div style="max-width: 460px; margin: 0 auto;">
    <div class="workspace-gform-panel">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
            <div>
                <div class="fw-semibold fs-4">Kalkulator Tunai</div>
                <div class="text-muted">
                    Hanya tiga angka utama. Angka tengah langsung bisa diisi.
                </div>
            </div>

            <div class="text-end">
                <div class="small text-muted">Mode Pembayaran</div>
                <div class="fw-semibold fs-5" id="workspace-cash-mode-text">Bayar Penuh</div>
            </div>
        </div>

        <div class="d-grid gap-3">
            <div class="workspace-gform-panel text-center">
                <div class="small text-muted mb-2">Tagihan</div>
                <div class="fs-1 fw-bold lh-sm" id="workspace-cash-payable-text">0</div>
            </div>

            <div class="workspace-gform-panel text-center" data-money-input-group>
                <div class="small text-muted mb-2">Uang Pelanggan</div>

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

                <div class="form-text mt-3">
                    Ketik nominal, cek kembalian, lalu simpan tunai saat jumlah cukup.
                </div>
            </div>

            <div class="workspace-gform-panel text-center">
                <div class="small text-muted mb-2">Kembalian</div>
                <div class="fs-1 fw-bold lh-sm" id="workspace-cash-change-text">0</div>
            </div>

            <div class="workspace-gform-panel text-center">
                <div class="small text-muted mb-2">Sisa Setelah Bayar</div>
                <div class="fs-3 fw-bold lh-sm" id="workspace-cash-remaining-text">0</div>
            </div>
        </div>
    </div>
</div>
