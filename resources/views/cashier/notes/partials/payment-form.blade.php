@if ($note['can_show_payment_form'])
    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <h4 class="card-title mb-1">Pembayaran Rincian Terpilih</h4>
                    <p class="mb-0 text-muted">
                        Form ini membaca rincian yang dipilih dari tabel nota. Pembayaran dilakukan untuk baris yang dicentang, bukan untuk nota secara umum.
                    </p>
                </div>

                <span class="badge border">Panel Bayar</span>
            </div>

            <p class="mt-2 mb-0 text-muted small">
                Kontrak backend lama masih dipertahankan sementara, tetapi pilihan rincian sudah menjadi input utama untuk alur pembayaran baru.
            </p>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ $paymentAction }}" id="note-payment-form">
                @csrf

                @if ($errors->has('payment'))
                    <div class="alert alert-danger py-2 px-3 mb-3">
                        {{ $errors->first('payment') }}
                    </div>
                @endif

                <div id="selected-payment-row-inputs"></div>

                <div class="border rounded p-3 mb-4">
                    <div class="small text-muted mb-2">Ringkasan Rincian Terpilih</div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Jumlah Rincian Dipilih</span>
                        <strong id="selected-payment-row-count">0</strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Total Sisa Tagihan Rincian Dipilih</span>
                        <strong id="selected-payment-outstanding-total">0</strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <span class="fw-semibold">Nominal Dibayar Sekarang</span>
                        <strong id="selected-payment-total">0</strong>
                    </div>
                </div>

                <div class="border rounded p-3 mb-4">
                    <div class="fw-semibold mb-1">Catatan Transisi</div>
                    <div class="small text-muted">
                        Opsi penuh atau sebagian tidak lagi ditonjolkan. Fokus utamanya sekarang adalah memilih rincian lalu mengisi nominal pembayaran untuk rincian yang dipilih.
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 d-none">
                        <input type="hidden" name="payment_scope" value="partial">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Metode</label>
                        <select class="form-select" name="payment_method" id="payment-method">
                            <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Tunai</option>
                            <option value="tf" {{ old('payment_method') === 'tf' ? 'selected' : '' }}>Transfer</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Tanggal Bayar</label>
                        <input
                            type="date"
                            class="form-control"
                            name="paid_at"
                            value="{{ old('paid_at', $paymentDateDefault) }}"
                            required
                        >
                    </div>

                    <div class="col-12">
                        <label class="form-label">Nominal Dibayar Sekarang</label>
                        <input
                            type="text"
                            class="form-control"
                            name="amount_paid"
                            id="amount-paid"
                            value="{{ old('amount_paid') }}"
                            placeholder="Isi nominal untuk rincian yang dipilih"
                        >
                    </div>

                    <div class="col-12">
                        <label class="form-label">Uang Masuk</label>
                        <input
                            type="text"
                            class="form-control"
                            name="amount_received"
                            id="amount-received"
                            value="{{ old('amount_received') }}"
                            placeholder="Wajib untuk pembayaran tunai"
                        >
                    </div>
                </div>

                <div class="border rounded p-3 mt-4">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                        <span class="text-muted">Sisa Setelah Bayar</span>
                        <strong id="payment-remaining-text">0</strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Kembalian Tunai</span>
                        <strong id="payment-change-text">0</strong>
                    </div>
                </div>

                <div class="ui-form-actions mt-3">
                    <button type="submit" class="btn btn-primary" id="note-payment-submit">
                        Catat Pembayaran Rincian
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
