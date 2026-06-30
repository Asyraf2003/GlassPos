<div class="card">
    <div class="card-header">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <h4 class="card-title mb-1">Pengembalian Dana Rincian Terpilih</h4>
            </div>

            <span class="badge border">Panel Pengembalian Dana</span>
        </div>
    </div>

    <div class="card-body">
        @if ($errors->has('refund') || $errors->has('selected_row_ids') || $errors->has('customer_payment_id') || $errors->has('amount_rupiah') || $errors->has('refunded_at') || $errors->has('reason'))
            <div class="alert alert-danger py-2 px-3 mb-3">
                @if ($errors->has('refund'))
                    <div>{{ $errors->first('refund') }}</div>
                @endif
                @if ($errors->has('selected_row_ids'))
                    <div>{{ $errors->first('selected_row_ids') }}</div>
                @endif
                @if ($errors->has('customer_payment_id'))
                    <div>{{ $errors->first('customer_payment_id') }}</div>
                @endif
                @if ($errors->has('amount_rupiah'))
                    <div>{{ $errors->first('amount_rupiah') }}</div>
                @endif
                @if ($errors->has('refunded_at'))
                    <div>{{ $errors->first('refunded_at') }}</div>
                @endif
                @if ($errors->has('reason'))
                    <div>{{ $errors->first('reason') }}</div>
                @endif
            </div>
        @endif

        <form method="POST" action="{{ $refundAction }}" id="note-refund-form">
            @csrf
            <input
                type="hidden"
                name="idempotency_key"
                value="{{ old('idempotency_key', $refundModalConfig['idempotency_key'] ?? '') }}"
            >

            <div id="selected-refund-row-inputs"></div>

            <div class="border rounded p-3 mb-4">
                <div class="small text-muted mb-2">Ringkasan Rincian Pengembalian Terpilih</div>

                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">Jumlah Rincian Dipilih</span>
                    <strong id="selected-refund-row-count">0</strong>
                </div>

                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">Total yang Bisa Dikembalikan</span>
                    <strong id="selected-refund-refundable-total">0</strong>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2">
                    <span class="fw-semibold">Nominal Pengembalian Sekarang</span>
                    <strong id="selected-refund-total">0</strong>
                </div>
            </div>

            @if (($note['refund_payment_options'] ?? []) !== [])
                <div class="form-group mb-4">
                    <label for="refund_customer_payment_id" class="form-label">Sumber Pembayaran Histori</label>
                    <select
                        id="refund_customer_payment_id"
                        name="customer_payment_id"
                        class="form-select"
                        required
                    >
                        @foreach ($note['refund_payment_options'] as $option)
                            <option
                                value="{{ $option['value'] }}"
                                {{ old('customer_payment_id') === $option['value'] ? 'selected' : '' }}
                            >
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group mb-4">
                    <label for="refund_refunded_at" class="form-label">Tanggal Pengembalian Dana</label>
                    <input
                        type="date"
                        id="refund_refunded_at"
                        name="refunded_at"
                        value="{{ old('refunded_at', $refundDateDefault) }}"
                        class="form-control"
                        required
                    >
                </div>

                <div class="form-group mb-4">
                    <label for="refund_amount_rupiah" class="form-label">Nominal Pengembalian Dana</label>
                    <input
                        type="number"
                        min="1"
                        id="refund_amount_rupiah"
                        name="amount_rupiah"
                        value="{{ old('amount_rupiah') }}"
                        class="form-control"
                        placeholder="Isi nominal pengembalian untuk rincian yang dipilih"
                        required
                    >
                </div>

                <div class="form-group mb-4">
                    <label for="refund_reason" class="form-label">Alasan Pengembalian Dana</label>
                    <textarea
                        id="refund_reason"
                        name="reason"
                        rows="3"
                        class="form-control"
                        placeholder="Jelaskan rincian atau kejadian yang menjadi alasan pengembalian dana"
                        required
                    >{{ old('reason') }}</textarea>
                </div>

                <div class="ui-form-actions">
                    <button type="submit" class="btn btn-primary" id="note-refund-submit">Catat Pengembalian Dana Rincian</button>
                </div>
            @else
                <div class="border rounded p-3">
                    <div class="fw-semibold mb-1">Belum ada sumber pengembalian dana yang bisa dipilih</div>
                    <div class="text-muted small">
                        Semua pembayaran pada nota ini sudah habis dikembalikan atau data pembayaran lama belum tersedia untuk alur pengembalian dana.
                    </div>
                </div>
            @endif
        </form>
    </div>
</div>
