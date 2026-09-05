<section class="note-payment-timeline" aria-labelledby="note-payment-timeline-title" data-payment-timeline>
  <div class="note-payment-timeline__header">
    <h5 id="note-payment-timeline-title" class="note-payment-timeline__title">Riwayat Pembayaran</h5>
    <span class="note-payment-timeline__count">{{ count($note['payment_timeline'] ?? []) }} transaksi</span>
  </div>

  @if (empty($note['payment_timeline'] ?? []))
    <p class="note-payment-timeline__empty">Belum ada pembayaran untuk nota ini.</p>
  @else
    <ol class="note-payment-timeline__list">
      @foreach (($note['payment_timeline'] ?? []) as $paymentEvent)
        <li
          class="note-payment-timeline__event"
          data-payment-event
          data-payment-id="{{ $paymentEvent['payment_id'] }}"
          data-payment-amount-rupiah="{{ $paymentEvent['payment_amount_rupiah'] }}"
        >
          <div class="note-payment-timeline__event-head">
            <time datetime="{{ $paymentEvent['occurred_at'] }}">
              {{ \App\Support\ViewDateFormatter::display($paymentEvent['occurred_at'], true) }}
            </time>
            <strong>{{ $paymentEvent['semantic_label'] }}</strong>
          </div>

          <div class="note-payment-timeline__amount">
            Rp {{ number_format($paymentEvent['payment_amount_rupiah'], 0, ',', '.') }}
            <span>· {{ $paymentEvent['payment_method_label'] }}</span>
          </div>

          @if ($paymentEvent['has_cash_detail'])
            <div class="note-payment-timeline__cash">
              <span>Diterima Rp {{ number_format((int) $paymentEvent['amount_received_rupiah'], 0, ',', '.') }}</span>
              <span>Kembalian Rp {{ number_format((int) $paymentEvent['change_rupiah'], 0, ',', '.') }}</span>
            </div>
          @endif

          @if ($paymentEvent['show_allocated_amount'])
            <div class="note-payment-timeline__allocation">
              Dialokasikan ke nota Rp {{ number_format($paymentEvent['allocated_amount_rupiah'], 0, ',', '.') }}
            </div>
          @endif

          <div class="note-payment-timeline__remaining">
            Sisa Rp {{ number_format($paymentEvent['remaining_after_rupiah'], 0, ',', '.') }}
          </div>
        </li>
      @endforeach
    </ol>
  @endif
</section>
