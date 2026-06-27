@include('cashier.notes.partials.note-row-refund-style')

<style>
  .note-detail-line-list {
    display: grid;
    gap: .85rem;
  }

  .note-detail-line-card {
    border: 1px solid var(--note-detail-border);
    border-radius: .5rem;
    background: var(--note-detail-card);
    color: var(--note-detail-text);
    padding: 1rem;
    overflow: hidden;
  }

  .note-detail-line-card.refund-row-hoverable {
    cursor: pointer;
    transition: background-color .15s ease, box-shadow .15s ease, transform .05s ease;
  }

  .note-detail-line-card.refund-row-hoverable:hover {
    background: var(--note-detail-surface-subtle);
  }

  .note-detail-line-card.refund-row-selected {
    background: var(--note-detail-surface-subtle);
    box-shadow: inset 0 0 0 2px var(--note-detail-accent-border);
  }

  .note-detail-line-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: .85rem;
    padding-bottom: .85rem;
    margin-bottom: .85rem;
    border-bottom: 1px solid var(--note-detail-border);
  }

  .note-detail-line-main {
    min-width: 0;
  }

  .note-detail-line-title {
    margin: 0;
    color: var(--note-detail-text);
    font-weight: 800;
    line-height: 1.35;
    overflow-wrap: anywhere;
  }

  .note-detail-line-subtitle {
    margin-top: .25rem;
    color: var(--note-detail-muted);
    font-size: .85rem;
    line-height: 1.45;
    overflow-wrap: anywhere;
  }

  .note-detail-line-number {
    width: 2rem;
    height: 2rem;
    flex: 0 0 2rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: var(--note-detail-accent);
    background: var(--note-detail-accent-soft);
    border: 1px solid var(--note-detail-accent-border);
    font-weight: 800;
  }

  .note-detail-line-metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .75rem;
    margin-top: .85rem;
  }

  .note-detail-line-metric {
    border: 1px solid var(--note-detail-border);
    border-radius: .35rem;
    background: var(--note-detail-surface-subtle);
    padding: .7rem .75rem;
    min-width: 0;
  }

  .note-detail-line-metric-label {
    color: var(--note-detail-muted);
    font-size: .76rem;
    line-height: 1.35;
  }

  .note-detail-line-metric-value {
    margin-top: .25rem;
    color: var(--note-detail-text);
    font-weight: 800;
    line-height: 1.35;
    overflow-wrap: anywhere;
  }

  .note-detail-line-impact {
    margin-top: .85rem;
    padding-top: .85rem;
    border-top: 1px solid var(--note-detail-border);
    color: var(--note-detail-muted);
    font-size: .86rem;
    line-height: 1.5;
  }

  .note-detail-line-empty {
    border: 1px dashed var(--note-detail-border);
    border-radius: .5rem;
    background: var(--note-detail-surface-subtle);
    color: var(--note-detail-muted);
    padding: 1rem;
    text-align: center;
  }

  @media (max-width: 360px) {
    .note-detail-line-metrics {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="note-detail-line-list">
  @forelse ($note['rows'] as $row)
    <div
      @if ((bool) ($row['can_refund'] ?? false))
        role="button"
        tabindex="0"
        class="note-detail-line-card refund-row-hoverable"
        data-refund-row="1"
        data-row-id="{{ $row['id'] }}"
        data-line-no="{{ $row['line_no'] }}"
        data-line-label="{{ $row['line_label'] ?? '-' }}"
        data-type-label="{{ $row['type_label'] }}"
        data-refundable-rupiah="{{ (int) ($row['net_paid_rupiah'] ?? 0) }}"
        data-store-return-count="{{ (int) ($row['refund_stock_return_count'] ?? 0) }}"
        data-external-count="{{ (int) ($row['refund_external_count'] ?? 0) }}"
        data-preview-label="{{ $row['refund_preview_label'] ?? '-' }}"
        data-refund-impact='@json($row["refund_impact"] ?? [])'
        aria-pressed="false"
      @else
        class="note-detail-line-card"
      @endif
    >
      <div class="note-detail-line-header">
        <span class="note-detail-line-number">{{ $row['line_no'] }}</span>

        <div class="note-detail-line-main">
          <div class="note-detail-line-title">{{ $row['line_label'] ?? '-' }}</div>
          <div class="note-detail-line-subtitle">
            {{ $row['type_label'] }}
            @if (!empty($row['line_subtitle']))
              · {{ $row['line_subtitle'] }}
            @endif
          </div>

          @include('cashier.notes.partials.note-row-package-breakdown', ['row' => $row])
        </div>

        <span class="badge border text-uppercase">
          {{ (string) ($row['line_status'] ?? '') !== '' ? $row['line_status'] : '-' }}
        </span>
      </div>

      <div class="note-detail-line-metrics">
        <div class="note-detail-line-metric">
          <div class="note-detail-line-metric-label">Subtotal</div>
          <div class="note-detail-line-metric-value">
            {{ number_format((int) ($row['subtotal_rupiah'] ?? 0), 0, ',', '.') }}
          </div>
        </div>

        <div class="note-detail-line-metric">
          <div class="note-detail-line-metric-label">Sudah Dibayar</div>
          <div class="note-detail-line-metric-value">
            {{ number_format((int) ($row['net_paid_rupiah'] ?? 0), 0, ',', '.') }}
          </div>
        </div>

        <div class="note-detail-line-metric">
          <div class="note-detail-line-metric-label">Pengembalian</div>
          <div class="note-detail-line-metric-value">
            {{ number_format((int) ($row['refunded_rupiah'] ?? 0), 0, ',', '.') }}
          </div>
        </div>

        <div class="note-detail-line-metric">
          <div class="note-detail-line-metric-label">Sisa</div>
          <div class="note-detail-line-metric-value">
            {{ number_format((int) ($row['outstanding_rupiah'] ?? 0), 0, ',', '.') }}
          </div>
        </div>
      </div>

      <div class="note-detail-line-impact">
        <div>{{ $row['refund_preview_label'] ?? '-' }}</div>

        @if ((int) ($row['refund_stock_return_count'] ?? 0) > 0)
          <div>Stok toko kembali: {{ (int) ($row['refund_stock_return_count'] ?? 0) }}</div>
        @endif

        @if ((int) ($row['refund_external_count'] ?? 0) > 0)
          <div>Komponen luar dinetralkan: {{ (int) ($row['refund_external_count'] ?? 0) }}</div>
        @endif
      </div>
    </div>
  @empty
    <div class="note-detail-line-empty">
      Belum ada rincian pada nota ini.
    </div>
  @endforelse

  <div class="small text-muted">Pengembalian dana dipilih dari rincian yang aktif.</div>
</div>
