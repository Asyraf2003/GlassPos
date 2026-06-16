<div class="cashier-note-header-stack">
    <div>
        <div class="card h-100">
            <div class="card-header">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <h4 class="card-title mb-1">
                            Info Customer
                            <span class="visually-hidden">Header Nota</span>
                        </h4>
                        <p class="mb-0 text-muted">
                            Identitas customer dan status nota saat ini.
                        </p>
                    </div>

                    <span class="badge border">
                        {{ count($note['rows']) }} Line
                    </span>
                </div>

                <p class="mt-2 mb-0 text-muted small">
                    Rincian di bawah memakai data nota terbaru.
                </p>
            </div>

            <div class="card-body">
                <div class="ui-key-value d-flex justify-content-between align-items-start py-2 border-bottom">
                    <small>No. Nota</small>
                    <div class="text-end">{{ $note['customer_name'] ?? ($note['note_header']['customer_name'] ?? 'Nota Pelanggan') }}</div>
                </div>

                <div class="ui-key-value d-flex justify-content-between align-items-start py-2 border-bottom">
                    <small>Customer</small>
                    <div class="text-end">{{ $note['customer_name'] }}</div>
                </div>

                <div class="ui-key-value d-flex justify-content-between align-items-start py-2 border-bottom">
                    <small>No. Telp</small>
                    <div class="text-end">
                        {{ !empty($note['customer_phone']) ? $note['customer_phone'] : '-' }}
                    </div>
                </div>

                <div class="ui-key-value d-flex justify-content-between align-items-start py-2 border-bottom">
                    <small>Tanggal Nota</small>
                    <div class="text-end">{{ \App\Support\ViewDateFormatter::display($note['transaction_date'] ?? null) }}</div>
                </div>

                <div class="ui-key-value d-flex justify-content-between align-items-start py-2 border-bottom">
                    <small>Status Note</small>
                    <div class="text-end text-uppercase">{{ $note['note_state'] }}</div>
                </div>

                <div class="ui-key-value d-flex justify-content-between align-items-start py-2">
                    <small>Ringkasan Line</small>
                    <div class="text-end">
                        {{ $note['line_summary']['summary_label'] ?? 'Belum ada line.' }}
                    </div>
                </div>

                <div class="border rounded p-3 mt-3">
                    <div class="small text-muted mb-2">Komposisi Status Line</div>

                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge border">
                            Open: {{ (int) ($note['line_summary']['open_count'] ?? 0) }}
                        </span>
                        <span class="badge border">
                            Close: {{ (int) ($note['line_summary']['close_count'] ?? 0) }}
                        </span>
                        <span class="badge border">
                            Refund: {{ (int) ($note['line_summary']['refund_count'] ?? 0) }}
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div>
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-1">Ringkasan Pembayaran</h4>
                <p class="mb-0 text-muted">
                    Total, pembayaran, refund, dan sisa tagihan nota.
                </p>
            </div>

            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">Grand Total</span>
                    <strong>{{ number_format($note['grand_total_rupiah'], 0, ',', '.') }}</strong>
                </div>

                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">Sudah Dibayar</span>
                    <strong>{{ number_format($note['net_paid_rupiah'], 0, ',', '.') }}</strong>
                </div>

                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">Total Refund</span>
                    <strong>{{ number_format($note['total_refunded_rupiah'], 0, ',', '.') }}</strong>
                </div>

                <div class="d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted">Sisa Tagihan</span>
                    <strong>{{ number_format($note['outstanding_rupiah'], 0, ',', '.') }}</strong>
                </div>

                @if ((int) $note['refund_required_rupiah'] > 0)
                    <div class="border rounded p-3 mt-3">
                        <div class="small text-muted">Refund Wajib Saat Ini</div>
                        <div class="fs-5 fw-bold">{{ number_format($note['refund_required_rupiah'], 0, ',', '.') }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
