<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-start gap-2">
      <div>
        <h4 class="card-title mb-0">Versioning Nota</h4>
      </div>
      <span class="badge border">{{ $revisionCount }} Revision</span>
    </div>
  </div>

  <div class="card-body">

    {{-- ============================================================ --}}
    {{-- REVISION AKTIF — Induk style                                 --}}
    {{-- ============================================================ --}}
    <div class="border rounded p-3 mb-4 bg-body">
      <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
        <div>
          <div class="small text-muted">Revision Aktif</div>
          {{-- Badge R-number sebagai "induk" — ukuran penuh, warna primer --}}
          <div class="fw-bold text-primary fs-5">
            R{{ (int) ($currentRevision['revision_number'] ?? 0) }}
          </div>
        </div>
        <div class="text-end small text-muted">
          <div>{{ \App\Support\ViewDateFormatter::display($currentRevision['created_at'] ?? null, true) }}</div>
        </div>
      </div>

      @if (!empty($currentRevision['line_snapshot_rows']))
        <div class="fw-semibold small mb-2 text-muted">Isi Revision Aktif</div>
        <div class="d-flex flex-column gap-2 mb-3">
          @foreach (($currentRevision['line_snapshot_rows'] ?? []) as $line)
            {{-- Line item — style induk: border penuh, padding normal --}}
            <div class="border rounded p-2 bg-body">
              <div class="d-flex justify-content-between gap-2">
                <div>
                  <div class="fw-semibold text-body">
                    Line {{ (int) ($line['line_no'] ?? 0) }} · {{ $line['label'] ?? '-' }}
                  </div>
                  <div class="small text-muted">
                    {{ $line['type_label'] ?? '-' }} · {{ $line['status'] ?? '-' }}
                  </div>
                </div>
                <div class="fw-semibold text-end text-body">
                  {{ number_format((int) ($line['subtotal_rupiah'] ?? 0), 0, ',', '.') }}
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @endif

      @if (!empty($currentRevision['change_summary_lines']))
        <div class="d-flex flex-column gap-1">
          @foreach (($currentRevision['change_summary_lines'] ?? []) as $summary)
            <div class="small text-muted fst-italic">• {{ $summary }}</div>
          @endforeach
        </div>
      @endif
    </div>

    {{-- ============================================================ --}}
    {{-- RIWAYAT REVISI — Turunan dari gaya induk di atas             --}}
    {{-- ============================================================ --}}
    <h6 class="mb-3 text-muted">Riwayat Revisi</h6>
    @if ($timelineRevisions === [])
      <div class="text-muted">Belum ada riwayat revisi.</div>
    @else
      <div class="d-flex flex-column gap-3">
        @foreach ($timelineRevisions as $entry)
          {{-- Wrapper entry — sama dengan induk: border rounded p-3 bg-body --}}
          <div class="border rounded p-3 bg-body">
            <div class="d-flex justify-content-between gap-2 mb-3">
              {{-- R-number mengikuti induk tapi sedikit lebih kecil (fs-6) --}}
              <div class="fw-bold text-primary fs-6">
                R{{ (int) ($entry['revision_number'] ?? 0) }}
              </div>
              <div class="text-end small text-muted">
                <div>{{ \App\Support\ViewDateFormatter::display($entry['created_at'] ?? null, true) }}</div>
                @if (!empty($entry['created_by_actor_id']))
                  <div class="badge mt-1">
                    {{ $entry['created_by_actor_id'] }}
                  </div>
                @endif
              </div>
            </div>

            @if (!empty($entry['line_snapshot_rows']))
              <div class="d-flex flex-column gap-2 mb-3">
                @foreach (($entry['line_snapshot_rows'] ?? []) as $line)
                  {{-- Line item turunan — mengikuti induk tapi pakai border-start --}}
                  {{-- sebagai penanda hierarki, tetap konsisten dengan induk    --}}
                  <div class="border rounded p-2 bg-body border-start border-3 border-secondary ps-3">
                    <div class="d-flex justify-content-between gap-2">
                      <div>
                        <div class="small fw-bold text-body">
                          Line {{ (int) ($line['line_no'] ?? 0) }} · {{ $line['label'] ?? '-' }}
                        </div>
                        <div class="small text-muted" style="font-size: 0.75rem;">
                          {{ $line['type_label'] ?? '-' }} · {{ $line['status'] ?? '-' }}
                        </div>
                      </div>
                      <div class="small fw-bold text-end text-body">
                        {{ number_format((int) ($line['subtotal_rupiah'] ?? 0), 0, ',', '.') }}
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            @endif

            @if (!empty($entry['change_summary_lines']) || !empty($entry['reason']))
              <div class="pt-2 border-top">
                @foreach (($entry['change_summary_lines'] ?? []) as $summary)
                  <div class="small text-muted">• {{ $summary }}</div>
                @endforeach
                @if (!empty($entry['reason']))
                  <div class="small text-muted mt-1 fst-italic">
                    <strong>Reason:</strong> {{ $entry['reason'] }}
                  </div>
                @endif
              </div>
            @endif
          </div>
        @endforeach
      </div>
    @endif

  </div>
</div>
