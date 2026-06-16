<div class="card">
  <div class="card-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
      <div>
        <h4 class="card-title mb-1">Riwayat Perubahan</h4>
        <p class="mb-0 text-muted">Perubahan nota yang pernah disimpan dari halaman edit.</p>
      </div>
      <span class="badge border">Riwayat Nota</span>
    </div>
  </div>
  <div class="card-body">
    <div class="row g-4">
      <div class="col-12 col-xl-5">
        <div class="border rounded p-3 h-100">
          <h5 class="mb-3">Versi Saat Ini</h5>
          <div class="ui-key-value d-flex justify-content-between py-2 border-bottom">
            <small>Versi</small>
            <div class="text-end">R{{ (int) ($currentRevision['revision_number'] ?? 0) }}</div>
          </div>
          <div class="ui-key-value d-flex justify-content-between py-2 border-bottom">
            <small>Customer</small>
            <div class="text-end">{{ $currentRevision['customer_name'] ?? '-' }}</div>
          </div>
          <div class="ui-key-value d-flex justify-content-between py-2 border-bottom">
            <small>Tanggal Nota</small>
            <div class="text-end">{{ \App\Support\ViewDateFormatter::display($currentRevision['transaction_date'] ?? null) }}</div>
          </div>
          <div class="ui-key-value d-flex justify-content-between py-2 border-bottom">
            <small>Jumlah Rincian</small>
            <div class="text-end">{{ (int) ($currentRevision['line_count'] ?? 0) }}</div>
          </div>
          <div class="ui-key-value d-flex justify-content-between py-2 border-bottom">
            <small>Grand Total</small>
            <div class="text-end">{{ number_format((int) ($currentRevision['grand_total_rupiah'] ?? 0), 0, ',', '.') }}</div>
          </div>
          <div class="ui-key-value d-flex justify-content-between py-2">
            <small>Dibuat Pada</small>
            <div class="text-end">{{ \App\Support\ViewDateFormatter::display($currentRevision['created_at'] ?? null, true) }}</div>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-7">
        <div class="border rounded p-3 h-100">
          <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <h5 class="mb-0">Versi Awal</h5>
            <span class="badge border">
              R{{ (int) ($baselineRevision['revision_number'] ?? 0) }}
            </span>
          </div>

          <div class="ui-key-value d-flex justify-content-between py-2 border-bottom">
            <small>Customer</small>
            <div class="text-end">{{ $baselineRevision['customer_name'] ?? '-' }}</div>
          </div>
          <div class="ui-key-value d-flex justify-content-between py-2 border-bottom">
            <small>Tanggal Nota</small>
            <div class="text-end">{{ \App\Support\ViewDateFormatter::display($baselineRevision['transaction_date'] ?? null) }}</div>
          </div>
          <div class="ui-key-value d-flex justify-content-between py-2 border-bottom">
            <small>Jumlah Rincian</small>
            <div class="text-end">{{ (int) ($baselineRevision['line_count'] ?? 0) }}</div>
          </div>
          <div class="ui-key-value d-flex justify-content-between py-2 border-bottom">
            <small>Grand Total</small>
            <div class="text-end">{{ number_format((int) ($baselineRevision['grand_total_rupiah'] ?? 0), 0, ',', '.') }}</div>
          </div>
          <div class="ui-key-value d-flex justify-content-between py-2">
            <small>Dibuat Pada</small>
            <div class="text-end">{{ \App\Support\ViewDateFormatter::display($baselineRevision['created_at'] ?? null, true) }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="border-top mt-4 pt-4">
      <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <div>
          <h5 class="mb-1">Daftar Perubahan</h5>
          <p class="text-muted mb-0 small">Urutan perubahan yang pernah tersimpan pada nota ini.</p>
        </div>
        <span class="badge border">{{ count($timelineRevisions) }} Perubahan</span>
      </div>

      @if ($timelineRevisions === [])
        <div class="text-muted">Belum ada perubahan yang tercatat.</div>
      @else
        <div class="timeline">
          @foreach ($timelineRevisions as $entry)
            <div class="timeline-item pb-4">
              <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-2">
                <div>
                  <h6 class="mb-1">Perubahan R{{ (int) ($entry['revision_number'] ?? 0) }}</h6>
                  <small class="text-muted">{{ \App\Support\ViewDateFormatter::display($entry['created_at'] ?? null, true) }}</small>
                </div>
                <span class="badge align-self-start">
                  Revisi Nota
                </span>
              </div>

              @if (!empty($entry['reason']))
                <div class="small text-muted mb-2">{{ $entry['reason'] }}</div>
              @endif

              @if (!empty($entry['created_by_actor_id']))
                <div class="small mb-2">
                  <span class="text-muted">Diproses oleh:</span> {{ $entry['created_by_actor_id'] }}
                </div>
              @endif

              <div class="border rounded p-3">
                <div class="row g-3">
                  <div class="col-12 col-md-4">
                    <small class="text-muted d-block">Customer</small>
                    <div class="fw-semibold">{{ $entry['customer_name'] ?? '-' }}</div>
                  </div>
                  <div class="col-12 col-md-4">
                    <small class="text-muted d-block">Tanggal Nota</small>
                    <div class="fw-semibold">{{ \App\Support\ViewDateFormatter::display($entry['transaction_date'] ?? null) }}</div>
                  </div>
                  <div class="col-12 col-md-2">
                    <small class="text-muted d-block">Rincian</small>
                    <div class="fw-semibold">{{ (int) ($entry['line_count'] ?? 0) }}</div>
                  </div>
                  <div class="col-12 col-md-2">
                    <small class="text-muted d-block">Total</small>
                    <div class="fw-semibold">{{ number_format((int) ($entry['grand_total_rupiah'] ?? 0), 0, ',', '.') }}</div>
                  </div>
                </div>

                @if (!empty($entry['line_snapshot_rows']))
                  <div class="mt-3 pt-3 border-top">
                    <div class="small fw-semibold mb-2">Isi Revisi</div>
                    <div class="d-flex flex-column gap-2">
                      @foreach (($entry['line_snapshot_rows'] ?? []) as $line)
                        <div class="d-flex justify-content-between align-items-start gap-3 small">
                          <div>
                            <div class="fw-semibold">
                              Rincian {{ (int) ($line['line_no'] ?? 0) }} · {{ $line['label'] ?? '-' }}
                            </div>
                            <div class="text-muted">{{ $line['type_label'] ?? '-' }}</div>
                          </div>
                          <div class="text-end fw-semibold">
                            Rp {{ number_format((int) ($line['subtotal_rupiah'] ?? 0), 0, ',', '.') }}
                          </div>
                        </div>
                      @endforeach
                    </div>
                  </div>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>
</div>
