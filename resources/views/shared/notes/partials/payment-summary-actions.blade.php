<div class="card">
  <div class="card-header">
    <h4 class="card-title mb-0">Status & Aksi Nota</h4>
  </div>

  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
      <span class="text-muted">Grand Total</span>
      <strong class="text-body">{{ number_format($note['grand_total_rupiah'], 0, ',', '.') }}</strong>
    </div>

    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
      <span class="text-muted">Sudah Dibayar</span>
      <strong class="text-body">{{ number_format($note['net_paid_rupiah'], 0, ',', '.') }}</strong>
    </div>

    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
      <span class="text-muted">Total Refund</span>
      <strong class="text-body">{{ number_format($note['total_refunded_rupiah'], 0, ',', '.') }}</strong>
    </div>

    <div class="d-flex justify-content-between align-items-center py-3">
      <span class="fw-semibold text-body">Sisa Tagihan</span>
      <strong class="fs-5 text-body">{{ number_format($note['outstanding_rupiah'], 0, ',', '.') }}</strong>
    </div>

    <div class="border rounded p-3 bg-body mb-3">
      <div class="small text-muted mb-1">Status Operasional</div>
      <div class="fw-bold text-uppercase text-body">{{ $note['payment_status_label'] ?? '-' }}</div>
    </div>

    @if (($note['surplus_disposition']['has_pending_refund_due_action'] ?? false) && ! empty($note['surplus_disposition']['pending_items'] ?? []))
      <div class="border rounded p-3 bg-body mb-3">
        <div class="small text-muted mb-1">Surplus Nota</div>
        <div class="fw-semibold text-body mb-2">Tandai Refund Due</div>
        <p class="small text-muted mb-3">
          Surplus pending dapat ditandai sebagai Refund Due. Ini belum berarti uang sudah keluar.
        </p>

        <div class="d-grid gap-3">
          @foreach (($note['surplus_disposition']['pending_items'] ?? []) as $pendingRefundDueItem)
            <form
              method="POST"
              action="{{ route('admin.notes.revision-settlements.refund-due.store', ['settlementId' => $pendingRefundDueItem['note_revision_settlement_id']]) }}"
              class="border rounded p-3"
            >
              @csrf

              <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted">Pending Refund Due</span>
                <strong class="text-body">
                  {{ number_format((int) ($pendingRefundDueItem['unresolved_pending_rupiah'] ?? 0), 0, ',', '.') }}
                </strong>
              </div>

              <div class="mb-3">
                <label class="form-label small text-muted" for="refund-due-amount-{{ $pendingRefundDueItem['note_revision_settlement_id'] }}">
                  Nominal Refund Due
                </label>
                <input
                  id="refund-due-amount-{{ $pendingRefundDueItem['note_revision_settlement_id'] }}"
                  type="number"
                  min="1"
                  step="1"
                  name="amount_rupiah"
                  value="{{ (int) ($pendingRefundDueItem['amount_default_rupiah'] ?? 0) }}"
                  class="form-control"
                  required
                >
              </div>

              <div class="mb-3">
                <label class="form-label small text-muted" for="refund-due-reason-{{ $pendingRefundDueItem['note_revision_settlement_id'] }}">
                  Alasan
                </label>
                <textarea
                  id="refund-due-reason-{{ $pendingRefundDueItem['note_revision_settlement_id'] }}"
                  name="reason"
                  class="form-control"
                  rows="3"
                  required
                ></textarea>
              </div>

              <button type="submit" class="btn btn-outline-primary w-100">
                Tandai Refund Due
              </button>
            </form>
          @endforeach
        </div>
      </div>
    @endif

    @if ($note['can_show_payment_form'] ?? false)
      <div class="d-grid gap-2">
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
      </div>
    @endif
  </div>
</div>
