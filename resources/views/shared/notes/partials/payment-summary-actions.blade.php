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

    @if (($canManageSurplusDisposition ?? false) && ($note['surplus_disposition']['has_pending_refund_due_action'] ?? false) && ! empty($note['surplus_disposition']['pending_items'] ?? []))
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
              data-refund-due-form
              data-refund-due-max-rupiah="{{ (int) ($pendingRefundDueItem['unresolved_pending_rupiah'] ?? 0) }}"
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
                  max="{{ (int) ($pendingRefundDueItem['unresolved_pending_rupiah'] ?? 0) }}"
                  step="1"
                  name="amount_rupiah"
                  value="{{ (int) ($pendingRefundDueItem['amount_default_rupiah'] ?? 0) }}"
                  class="form-control"
                  data-refund-due-amount
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

              <button
                type="submit"
                class="btn btn-outline-primary w-100"
                data-refund-due-submit
                data-loading-text="Menyimpan Refund Due..."
              >
                Tandai Refund Due
              </button>
            </form>
          @endforeach
        </div>
      </div>
    @endif

    @if (($canManageSurplusDisposition ?? false) && ($note['surplus_disposition']['has_pending_refund_paid_action'] ?? false) && ! empty($note['surplus_disposition']['refund_paid_items'] ?? []))
      <div class="border rounded p-3 bg-body mb-3">
        <div class="small text-muted mb-1">Surplus Nota</div>
        <div class="fw-semibold text-body mb-2">Catat Refund Paid</div>
        <p class="small text-muted mb-3">
          Refund Paid berarti uang surplus benar-benar sudah keluar dari kas.
        </p>

        <div class="d-grid gap-3">
          @foreach (($note['surplus_disposition']['refund_paid_items'] ?? []) as $refundPaidItem)
            <form
              method="POST"
              action="{{ route('admin.notes.revision-surplus-dispositions.refund-paid.store', ['dispositionId' => $refundPaidItem['note_revision_surplus_disposition_id']]) }}"
              class="border rounded p-3"
              data-refund-paid-form
              data-refund-paid-max-rupiah="{{ (int) ($refundPaidItem['remaining_refund_due_rupiah'] ?? 0) }}"
            >
              @csrf

              <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted">Sisa Refund Due</span>
                <strong class="text-body">
                  {{ number_format((int) ($refundPaidItem['remaining_refund_due_rupiah'] ?? 0), 0, ',', '.') }}
                </strong>
              </div>

              <div class="mb-3">
                <label class="form-label small text-muted" for="refund-paid-amount-{{ $refundPaidItem['note_revision_surplus_disposition_id'] }}">
                  Nominal Refund Paid
                </label>
                <input
                  id="refund-paid-amount-{{ $refundPaidItem['note_revision_surplus_disposition_id'] }}"
                  type="number"
                  min="1"
                  max="{{ (int) ($refundPaidItem['remaining_refund_due_rupiah'] ?? 0) }}"
                  step="1"
                  name="amount_rupiah"
                  value="{{ (int) ($refundPaidItem['amount_default_rupiah'] ?? 0) }}"
                  class="form-control"
                  data-refund-paid-amount
                  required
                >
              </div>

              <div class="mb-3">
                <label class="form-label small text-muted" for="refund-paid-effective-date-{{ $refundPaidItem['note_revision_surplus_disposition_id'] }}">
                  Tanggal Refund Paid
                </label>
                <input
                  id="refund-paid-effective-date-{{ $refundPaidItem['note_revision_surplus_disposition_id'] }}"
                  type="date"
                  name="effective_date"
                  value="{{ date('Y-m-d') }}"
                  class="form-control"
                  required
                >
              </div>

              <div class="mb-3">
                <label class="form-label small text-muted" for="refund-paid-reason-{{ $refundPaidItem['note_revision_surplus_disposition_id'] }}">
                  Alasan
                </label>
                <textarea
                  id="refund-paid-reason-{{ $refundPaidItem['note_revision_surplus_disposition_id'] }}"
                  name="reason"
                  class="form-control"
                  rows="3"
                  required
                ></textarea>
              </div>

              <input
                type="hidden"
                name="idempotency_key"
                value="refund-paid-{{ $refundPaidItem['note_revision_surplus_disposition_id'] }}-{{ (int) ($refundPaidItem['remaining_refund_due_rupiah'] ?? 0) }}"
              >

              <button
                type="submit"
                class="btn btn-outline-danger w-100"
                data-refund-paid-submit
                data-loading-text="Menyimpan Refund Paid..."
              >
                Catat Refund Paid
              </button>
            </form>
          @endforeach
        </div>
      </div>
    @endif


    @if (! empty($note['surplus_disposition_audit_timeline'] ?? []))
      <div class="border rounded p-3 bg-body mb-3">
        <div class="small text-muted mb-1">Timeline Audit Surplus</div>
        <div class="fw-semibold text-body mb-2">Riwayat Refund Due</div>
        <div class="d-grid gap-2">
          @foreach (($note['surplus_disposition_audit_timeline'] ?? []) as $auditItem)
            <div class="border rounded p-2 bg-body">
              <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                  <div class="fw-semibold text-body">{{ $auditItem['label'] ?? 'Refund Due Ditandai' }}</div>
                  <div class="small text-muted">
                    Amount {{ number_format((int) ($auditItem['amount_rupiah'] ?? 0), 0, ',', '.') }} ·
                    {{ $auditItem['remaining_label'] ?? 'Sisa pending' }} {{ number_format((int) ($auditItem['remaining_rupiah'] ?? ($auditItem['after_pending_rupiah'] ?? 0)), 0, ',', '.') }}
                  </div>
                  @if (! empty($auditItem['reason']))
                    <div class="small text-muted fst-italic mt-1">Reason: {{ $auditItem['reason'] }}</div>
                  @endif
                </div>
                <div class="text-end small text-muted">
                  <div>{{ \App\Support\ViewDateFormatter::display($auditItem['occurred_at'] ?? null, true) }}</div>
                  @if (! empty($auditItem['actor_role']))
                    <div class="badge bg-light-secondary text-secondary mt-1">{{ $auditItem['actor_role'] }}</div>
                  @endif
                </div>
              </div>
            </div>
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
