<div class="note-detail-readonly-grid">
  <div class="note-detail-readonly-field note-detail-readonly-field--customer">
    <div class="note-detail-readonly-label">Pelanggan</div>
    <div class="note-detail-readonly-control">
      {{ $note['customer_name'] }}
    </div>
  </div>

  <div class="note-detail-readonly-field note-detail-readonly-field--phone">
    <div class="note-detail-readonly-label">No. HP</div>
    <div class="note-detail-readonly-control">
      {{ !empty($note['customer_phone']) ? $note['customer_phone'] : '-' }}
    </div>
  </div>

  <div class="note-detail-readonly-field note-detail-readonly-field--date">
    <div class="note-detail-readonly-label">Tanggal Nota</div>
    <div class="note-detail-readonly-control">
      {{ \App\Support\ViewDateFormatter::display($note['transaction_date'] ?? null) }}
    </div>
  </div>

  <div class="note-detail-readonly-field note-detail-readonly-field--status">
    <div class="note-detail-readonly-label">Status</div>
    <div class="note-detail-readonly-control">
      <span class="badge border text-uppercase">
        {{ $note['operational_status'] ?? $note['payment_status'] ?? '-' }}
      </span>
    </div>
  </div>

  @if (!empty($note['operational_note']))
    <div class="note-detail-readonly-field note-detail-readonly-field--reason">
      <div class="note-detail-readonly-label">Alasan Nota</div>
      <div class="note-detail-readonly-control">
        {{ $note['operational_note'] }}
      </div>
    </div>
  @endif

  <div class="note-detail-readonly-field note-detail-readonly-field--reference">
    <div class="note-detail-readonly-label">Ref Nota</div>
    <div class="note-detail-readonly-control" title="{{ $note['id'] }}">
      <span>{{ substr((string) $note['id'], 0, 8) }}</span>
      <span class="visually-hidden">{{ $note['id'] }}</span>
    </div>
  </div>
</div>
