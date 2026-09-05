<div class="card note-detail-payment-card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
      <span class="text-muted small">Status Pembayaran</span>
      <span class="badge border" data-payment-aggregate="status">
        {{ $note['payment_status_label'] ?? '-' }}
      </span>
    </div>

    <div class="note-detail-payment-metrics mb-3">
      <div class="note-detail-payment-metric">
        <span>Total</span>
        <strong
          data-payment-aggregate="grand_total"
          data-rupiah="{{ $note['grand_total_rupiah'] }}"
        >{{ number_format($note['grand_total_rupiah'], 0, ',', '.') }}</strong>
      </div>

      <div class="note-detail-payment-metric">
        <span>Dibayar</span>
        <strong
          data-payment-aggregate="net_paid"
          data-rupiah="{{ $note['net_paid_rupiah'] }}"
        >{{ number_format($note['net_paid_rupiah'], 0, ',', '.') }}</strong>
      </div>

      <div class="note-detail-payment-metric">
        <span>Pengembalian</span>
        <strong
          data-payment-aggregate="refunded"
          data-rupiah="{{ $note['total_refunded_rupiah'] }}"
        >{{ number_format($note['total_refunded_rupiah'], 0, ',', '.') }}</strong>
      </div>

      <div class="note-detail-payment-metric note-detail-payment-metric--outstanding">
        <span>Sisa</span>
        <strong
          data-payment-aggregate="outstanding"
          data-rupiah="{{ $note['outstanding_rupiah'] }}"
        >{{ number_format($note['outstanding_rupiah'], 0, ',', '.') }}</strong>
      </div>
    </div>

    <div class="d-grid gap-2">
      @if ($note['can_edit_workspace'] ?? false)
        <a
          href="{{ route($detailConfig['workspace_edit_route'], ['noteId' => $note['id']]) }}"
          class="btn btn-primary"
        >
          Edit Nota
        </a>
      @endif

      @if ($note['can_show_partial_payment_action'] ?? false)
        <button
          type="button"
          class="btn btn-primary js-open-payment-intent"
          data-bs-toggle="modal"
          data-bs-target="#note-payment-modal"
          data-payment-intent="pay"
          data-payment-preset="manual"
        >
          Bayar Sebagian
        </button>
      @endif

      @if ($note['can_show_settle_payment_action'] ?? false)
        <button
          type="button"
          class="btn btn-outline-primary js-open-payment-intent"
          data-bs-toggle="modal"
          data-bs-target="#note-payment-modal"
          data-payment-intent="settle"
          data-payment-preset="manual"
        >
          Lunasi
        </button>
      @endif

      @if ($note['can_show_refund_form'] ?? false)
        <button
          type="button"
          class="btn btn-outline-danger opacity-50 disabled"
          data-bs-toggle="modal"
          data-bs-target="#note-refund-modal"
          id="note-refund-open-button"
          disabled
          aria-disabled="true"
        >
          Pengembalian Dana Rincian Terpilih
        </button>
      @endif
    </div>
  </div>
</div>
