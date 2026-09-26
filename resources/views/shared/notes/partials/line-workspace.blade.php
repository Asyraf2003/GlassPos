<div class="note-detail-line-list">
  @if (!empty($note['line_summary']['summary_label']))
    <div class="note-detail-line-summary">{{ $note['line_summary']['summary_label'] }}</div>
  @endif

  @forelse ($note['rows'] as $row)
    <div
      @if ((bool) ($row['can_refund'] ?? false))
        role="button"
        tabindex="0"
        class="note-detail-line-row refund-row-hoverable"
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
        class="note-detail-line-row"
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

        @include('shared.notes.partials.status-badge', [
          'label' => (string) ($row['line_status'] ?? '') !== '' ? $row['line_status'] : '-',
        ])
      </div>

      <div class="note-detail-line-metrics">
        <div class="note-detail-line-metric">
          <div class="note-detail-line-metric-label">Subtotal</div>
          <div class="note-detail-line-metric-value">{{ number_format((int) ($row['subtotal_rupiah'] ?? 0), 0, ',', '.') }}</div>
        </div>

        <div class="note-detail-line-metric">
          <div class="note-detail-line-metric-label">Sudah Dibayar</div>
          <div class="note-detail-line-metric-value">{{ number_format((int) ($row['net_paid_rupiah'] ?? 0), 0, ',', '.') }}</div>
        </div>

        <div class="note-detail-line-metric">
          <div class="note-detail-line-metric-label">Pengembalian</div>
          <div class="note-detail-line-metric-value">{{ number_format((int) ($row['refunded_rupiah'] ?? 0), 0, ',', '.') }}</div>
        </div>

        <div class="note-detail-line-metric">
          <div class="note-detail-line-metric-label">Sisa</div>
          <div class="note-detail-line-metric-value">{{ number_format((int) ($row['outstanding_rupiah'] ?? 0), 0, ',', '.') }}</div>
        </div>
      </div>

      <div class="note-detail-line-description">
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
    <div class="note-detail-line-empty">Belum ada rincian pada nota ini.</div>
  @endforelse

  <div class="visually-hidden">Pengembalian dana dipilih dari rincian yang aktif.</div>
</div>
