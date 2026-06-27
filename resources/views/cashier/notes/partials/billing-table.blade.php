<div class="card h-100">
  <div class="card-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
      <div>
        <h4 class="card-title mb-1">Rincian Tagihan</h4>
        <p class="mb-0 text-muted">Urutan tagihan yang dipakai saat pembayaran nota.</p>
      </div>
      <span class="badge border">{{ count($note['billing_rows'] ?? []) }} Baris Tagihan</span>
    </div>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped align-middle mb-0">
        <thead>
          <tr>
            <th>Baris</th>
            <th>Jenis Tagihan</th>
            <th>Komponen</th>
            <th>Status</th>
            <th class="text-end">Komponen</th>
            <th class="text-end">Sudah Dibayar</th>
            <th class="text-end">Refund</th>
            <th class="text-end">Sisa</th>
            <th>Keterangan</th>
          </tr>
        </thead>
        <tbody>
          @forelse (($note['billing_rows'] ?? []) as $row)
            <tr>
              <td>{{ $row['line_no'] }}</td>
              <td>{{ $row['domain_type_label'] }}</td>
              <td>
                <div class="fw-semibold">{{ $row['component_label'] }}</div>
                <div class="small text-muted">Urutan tagih {{ $row['component_order'] }}</div>
              </td>
              <td>
                <span class="badge border">{{ $row['status_label'] }}</span>
              </td>
              <td class="text-end">{{ number_format((int) ($row['component_total_rupiah'] ?? 0), 0, ',', '.') }}</td>
              <td class="text-end">{{ number_format((int) ($row['net_paid_rupiah'] ?? 0), 0, ',', '.') }}</td>
              <td class="text-end">{{ number_format((int) ($row['refunded_rupiah'] ?? 0), 0, ',', '.') }}</td>
              <td class="text-end">{{ number_format((int) ($row['outstanding_rupiah'] ?? 0), 0, ',', '.') }}</td>
              <td>
                @if ((bool) ($row['is_paid'] ?? false))
                  <div class="small text-muted">Komponen sudah lunas.</div>
                @elseif (! ($row['can_select_manually'] ?? false))
                  <div class="small text-muted">{{ $row['selection_blocked_reason'] ?? 'Ikuti urutan tagihan sebelumnya.' }}</div>
                @elseif ($row['eligible_for_dp_preset'] ?? false)
                  <div class="small text-muted">Masuk prioritas pengaturan DP.</div>
                @else
                  <div class="small text-muted">Bisa dipilih manual setelah komponen sebelumnya lunas.</div>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center text-muted py-4">Belum ada rincian tagihan untuk nota ini.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="small text-muted mt-3">Pembayaran mengikuti urutan tagihan yang masih aktif.</div>
  </div>
</div>
