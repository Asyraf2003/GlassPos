<div class="note-detail-readonly-grid">
  <div class="note-detail-readonly-field">
    <div class="note-detail-readonly-label">No. Nota</div>
    <div class="note-detail-readonly-control">
      {{ $note['customer_name'] ?? ($note['note_header']['customer_name'] ?? 'Nota Pelanggan') }}
    </div>
  </div>

  <div class="note-detail-readonly-field">
    <div class="note-detail-readonly-label">Customer</div>
    <div class="note-detail-readonly-control">
      {{ $note['customer_name'] }}
    </div>
  </div>

  <div class="note-detail-readonly-field">
    <div class="note-detail-readonly-label">No. HP Customer</div>
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
      <div class="note-detail-readonly-label">Alasan Nota</div>
      <div class="note-detail-readonly-control">
        {{ $note['operational_note'] }}
      </div>
    </div>
  @endif

  <div class="note-detail-readonly-field">
    <div class="note-detail-readonly-label">Status Operasional</div>
    <div class="note-detail-readonly-control">
      <span class="badge border text-uppercase">
        {{ $note['operational_status'] ?? $note['payment_status'] ?? '-' }}
      </span>
    </div>
  </div>

  <div class="note-detail-readonly-field">
    <div class="note-detail-readonly-label">Jumlah Line</div>
    <div class="note-detail-readonly-control">
      {{ count($note['rows']) }} Line
    </div>
  </div>

  <div class="note-detail-readonly-field">
    <div class="note-detail-readonly-label">Ringkasan Line</div>
    <div class="note-detail-readonly-control">
      {{ $note['line_summary']['summary_label'] ?? 'Belum ada line.' }}
    </div>
  </div>
</div>
