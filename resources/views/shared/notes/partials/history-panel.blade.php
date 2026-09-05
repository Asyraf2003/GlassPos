<div class="note-detail-history-grid">
  <section class="note-detail-history-section note-detail-history-section--changes">
    @include('shared.notes.partials.versioning-compact', [
      'currentRevision' => ($note['revision_timeline']['current'] ?? []),
      'timelineRevisions' => array_slice(($note['revision_timeline']['timeline'] ?? []), 0, 3),
      'revisionCount' => count($note['revision_timeline']['timeline'] ?? []),
    ])
    @include('cashier.notes.partials.correction-history')
  </section>

  <section class="note-detail-history-section note-detail-history-section--payments">
    @include('shared.notes.partials.payment-timeline')
  </section>

  @if (! empty($note['surplus_disposition_audit_timeline'] ?? []))
    <section class="note-detail-history-section note-detail-history-section--refunds">
      <div class="note-detail-history-heading">
        <h5 class="mb-0">Riwayat Pengembalian Otomatis</h5>
        <span class="small text-muted">Pengembalian Surplus Revisi</span>
      </div>

      <div class="d-grid gap-2">
        @foreach (($note['surplus_disposition_audit_timeline'] ?? []) as $auditItem)
          <div class="note-detail-history-event">
            <div class="d-flex justify-content-between align-items-start gap-3">
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
    </section>
  @endif
</div>
