@if (! empty($note['refund_timeline'] ?? []))
  <section class="note-detail-history-section note-detail-history-section--refunds">
    <div class="note-detail-history-heading">
      <h5 class="mb-0">Riwayat Pengembalian Dana</h5>
      <span class="small text-muted">Refund historis, bukan tagihan aktif</span>
    </div>

    <div class="d-grid gap-2">
      @foreach ($note['refund_timeline'] as $refund)
        <div class="note-detail-history-event" data-refund-history-event="{{ $refund['id'] }}">
          <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
              <div class="fw-semibold text-body">
                Pengembalian {{ number_format((int) ($refund['amount_rupiah'] ?? 0), 0, ',', '.') }}
              </div>
              <div class="small text-muted">
                {{ \App\Support\ViewDateFormatter::display($refund['refunded_at'] ?? null) }}
              </div>

              @if (! empty($refund['reason']))
                <div class="small text-muted fst-italic mt-1">Alasan: {{ $refund['reason'] }}</div>
              @endif

              @if (! empty($refund['components'] ?? []))
                <div class="small mt-2">
                  @foreach ($refund['components'] as $component)
                    <div data-refund-history-component="{{ $component['component_ref_id'] }}">
                      {{ $component['label'] }}
                      · {{ number_format((int) ($component['refunded_amount_rupiah'] ?? 0), 0, ',', '.') }}
                    </div>
                  @endforeach
                </div>
              @endif
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </section>
@endif
