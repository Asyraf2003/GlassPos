<div>
    @include('cashier.notes.partials.note-row-refund-style')

    <div class="table-responsive">
      <table class="table table-striped align-middle mb-0">
        <thead>
          <tr>
            <th>Line</th>
            <th>Item / Service</th>
            <th>Tipe</th>
            <th>Status</th>
            <th class="text-end">Subtotal</th>
            <th class="text-end">Sudah Dibayar</th>
            <th class="text-end">Refund</th>
            <th class="text-end">Sisa</th>
            <th>Dampak Refund</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($note['rows'] as $row)
            <tr
              @if ((bool) ($row['can_refund'] ?? false))
                role="button"
                tabindex="0"
                class="refund-row-hoverable"
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
              @endif
            >
              <td>{{ $row['line_no'] }}</td>
              <td>
                <div class="fw-semibold">{{ $row['line_label'] ?? '-' }}</div>
                @if (!empty($row['line_subtitle']))
                  <div class="small text-muted">{{ $row['line_subtitle'] }}</div>
                @endif
                @include('cashier.notes.partials.note-row-package-breakdown', ['row' => $row])
              </td>
              <td>{{ $row['type_label'] }}</td>
              <td>
                <span class="badge border text-uppercase">
                  {{ (string) ($row['line_status'] ?? '') !== '' ? $row['line_status'] : '-' }}
                </span>
              </td>
              <td class="text-end">{{ number_format((int) ($row['subtotal_rupiah'] ?? 0), 0, ',', '.') }}</td>
              <td class="text-end">{{ number_format((int) ($row['net_paid_rupiah'] ?? 0), 0, ',', '.') }}</td>
              <td class="text-end">{{ number_format((int) ($row['refunded_rupiah'] ?? 0), 0, ',', '.') }}</td>
              <td class="text-end">{{ number_format((int) ($row['outstanding_rupiah'] ?? 0), 0, ',', '.') }}</td>
              <td>
                <div class="small text-muted">{{ $row['refund_preview_label'] ?? '-' }}</div>
                @if ((int) ($row['refund_stock_return_count'] ?? 0) > 0)
                  <div class="small">Stok toko kembali: {{ (int) ($row['refund_stock_return_count'] ?? 0) }}</div>
                @endif
                @if ((int) ($row['refund_external_count'] ?? 0) > 0)
                  <div class="small">External dinetralkan: {{ (int) ($row['refund_external_count'] ?? 0) }}</div>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-center text-muted py-4">Belum ada line pada nota ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="small text-muted mt-3">Refund dipilih dari rincian yang aktif.</div>
</div>
