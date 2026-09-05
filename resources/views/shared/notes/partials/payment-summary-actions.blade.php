<div class="card">
  <div class="card-body">
    <div class="d-grid gap-2 mb-3">
      @if ($note['can_edit_workspace'] ?? false)
        <a
          href="{{ route($detailConfig['workspace_edit_route'], ['noteId' => $note['id']]) }}"
          class="btn btn-primary"
        >
          Edit Nota
        </a>
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

    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
      <span class="text-muted">Total Nota</span>
      <strong
        class="text-body"
        data-payment-aggregate="grand_total"
        data-rupiah="{{ $note['grand_total_rupiah'] }}"
      >{{ number_format($note['grand_total_rupiah'], 0, ',', '.') }}</strong>
    </div>

    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
      <span class="text-muted">Sudah Dibayar</span>
      <strong
        class="text-body"
        data-payment-aggregate="net_paid"
        data-rupiah="{{ $note['net_paid_rupiah'] }}"
      >{{ number_format($note['net_paid_rupiah'], 0, ',', '.') }}</strong>
    </div>

    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
      <span class="text-muted">Pengembalian</span>
      <strong
        class="text-body"
        data-payment-aggregate="refunded"
        data-rupiah="{{ $note['total_refunded_rupiah'] }}"
      >{{ number_format($note['total_refunded_rupiah'], 0, ',', '.') }}</strong>
    </div>

    <div class="d-flex justify-content-between align-items-center py-3">
      <span class="fw-semibold text-body">Sisa Tagihan</span>
      <strong
        class="fs-5 text-body"
        data-payment-aggregate="outstanding"
        data-rupiah="{{ $note['outstanding_rupiah'] }}"
      >{{ number_format($note['outstanding_rupiah'], 0, ',', '.') }}</strong>
    </div>

    @include('shared.notes.partials.payment-timeline')

    @if (! empty($note['surplus_disposition_audit_timeline'] ?? []))
      <div class="border rounded p-3 bg-body mb-3">
        <div class="fw-semibold text-body mb-2">Pengembalian Surplus Revisi</div>
        <div class="d-grid gap-2">
          @foreach (($note['surplus_disposition_audit_timeline'] ?? []) as $auditItem)
            <div class="border rounded p-2 bg-body">
              <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                  <div class="fw-semibold text-body">{{ $auditItem['label'] ?? 'Pengembalian Belum Dibayar Ditandai' }}</div>
                  <div class="small text-muted">
                    Nominal {{ number_format((int) ($auditItem['amount_rupiah'] ?? 0), 0, ',', '.') }} ·
                    {{ $auditItem['remaining_label'] ?? 'Sisa pending' }} {{ number_format((int) ($auditItem['remaining_rupiah'] ?? ($auditItem['after_pending_rupiah'] ?? 0)), 0, ',', '.') }}
                  </div>
                  @if (! empty($auditItem['reason']))
                    <div class="small text-muted fst-italic mt-1">Alasan: {{ $auditItem['reason'] }}</div>
                  @endif
                </div>
                <div class="text-end small text-muted">
                  <div>{{ \App\Support\ViewDateFormatter::display($auditItem['occurred_at'] ?? null, true) }}</div>
                  @if (! empty($auditItem['actor_role']))
                    <div class="badge mt-1">{{ $auditItem['actor_role'] }}</div>
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
