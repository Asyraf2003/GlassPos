<dl class="note-detail-info-list">
  <div class="note-detail-info-row">
    <dt class="note-detail-info-label">Pelanggan</dt>
    <dd class="note-detail-info-value">{{ $note['customer_name'] }}</dd>
  </div>

  <div class="note-detail-info-row">
    <dt class="note-detail-info-label">No. HP</dt>
    <dd class="note-detail-info-value">{{ !empty($note['customer_phone']) ? $note['customer_phone'] : '-' }}</dd>
  </div>

  <div class="note-detail-info-row">
    <dt class="note-detail-info-label">Tanggal Nota</dt>
    <dd class="note-detail-info-value">{{ \App\Support\ViewDateFormatter::display($note['transaction_date'] ?? null) }}</dd>
  </div>

  @if (!empty($note['operational_note']))
    <div class="note-detail-info-row">
      <dt class="note-detail-info-label">Alasan Nota</dt>
      <dd class="note-detail-info-value">{{ $note['operational_note'] }}</dd>
    </div>
  @endif

  <div class="note-detail-info-row">
    <dt class="note-detail-info-label">Ref Nota</dt>
    <dd class="note-detail-info-value note-detail-info-value--reference" title="{{ $note['id'] }}">
      <span>{{ substr((string) $note['id'], 0, 8) }}</span>
      <span class="visually-hidden">{{ $note['id'] }}</span>
    </dd>
  </div>
</dl>
