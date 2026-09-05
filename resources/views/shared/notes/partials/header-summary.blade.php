<div class="note-detail-readonly-grid">
  <div class="note-detail-readonly-field">
    <div class="note-detail-readonly-label">ID Nota</div>
    <div class="note-detail-readonly-control">
      {{ $note['id'] }}
    </div>
  </div>

  <div class="note-detail-readonly-field">
    <div class="note-detail-readonly-label">Pelanggan</div>
    <div class="note-detail-readonly-control">
      {{ $note['customer_name'] }}
    </div>
  </div>

  <div class="note-detail-readonly-field">
    <div class="note-detail-readonly-label">No. HP</div>
    <div class="note-detail-readonly-control">
      {{ !empty($note['customer_phone']) ? $note['customer_phone'] : '-' }}
    </div>
  </div>

  <div class="note-detail-readonly-field">
    <div class="note-detail-readonly-label">Tanggal Nota</div>
    <div class="note-detail-readonly-control">
      {{ \App\Support\ViewDateFormatter::display($note['transaction_date'] ?? null) }}
    </div>
  </div>

  @if (!empty($note['operational_note']))
    <div class="note-detail-readonly-field">
      <div class="note-detail-readonly-label">Keterangan</div>
      <div class="note-detail-readonly-control">
        {{ $note['operational_note'] }}
      </div>
    </div>
  @endif

  <div class="note-detail-readonly-field">
    <div class="note-detail-readonly-label">Status</div>
    <div class="note-detail-readonly-control">
      <span class="badge border text-uppercase">
        {{ $note['operational_status'] ?? $note['payment_status'] ?? '-' }}
      </span>
    </div>
  </div>
</div>
