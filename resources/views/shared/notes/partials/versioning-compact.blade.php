<div class="note-detail-version-list">
  <section class="note-detail-version-row note-detail-version-row--current">
    <div class="note-detail-version-head">
      <div class="note-detail-desktop-title-group">
        <span class="note-detail-version-name">v{{ (int) ($currentRevision['revision_number'] ?? 0) }}</span>
        @include('shared.notes.partials.status-badge', [
          'label' => 'Aktif',
          'tone' => 'info',
        ])
      </div>
      <time class="note-detail-version-time">
        {{ \App\Support\ViewDateFormatter::display($currentRevision['created_at'] ?? null, true) }}
      </time>
    </div>

    @if (!empty($currentRevision['line_snapshot_rows']))
      <div class="note-detail-version-lines">
        @foreach (($currentRevision['line_snapshot_rows'] ?? []) as $line)
          <div class="note-detail-version-line">
            <span>
              Rincian {{ (int) ($line['line_no'] ?? 0) }} · {{ $line['label'] ?? '-' }}
              ({{ $line['type_label'] ?? '-' }} · {{ $line['status'] ?? '-' }})
            </span>
            <strong>{{ number_format((int) ($line['subtotal_rupiah'] ?? 0), 0, ',', '.') }}</strong>
          </div>
        @endforeach
      </div>
    @endif

    @if (!empty($currentRevision['change_summary_lines']) || !empty($currentRevision['reason']))
      <div class="note-detail-version-summary">
        @foreach (($currentRevision['change_summary_lines'] ?? []) as $summary)
          <div>• {{ $summary }}</div>
        @endforeach
        @if (!empty($currentRevision['reason']))
          <div><strong>Alasan:</strong> {{ $currentRevision['reason'] }}</div>
        @endif
      </div>
    @endif
  </section>

  @foreach ($timelineRevisions as $entry)
    <section class="note-detail-version-row">
      <div class="note-detail-version-head">
        <span class="note-detail-version-name">v{{ (int) ($entry['revision_number'] ?? 0) }}</span>
        <time class="note-detail-version-time">
          {{ \App\Support\ViewDateFormatter::display($entry['created_at'] ?? null, true) }}
        </time>
      </div>

      @if (!empty($entry['line_snapshot_rows']))
        <div class="note-detail-version-lines">
          @foreach (($entry['line_snapshot_rows'] ?? []) as $line)
            <div class="note-detail-version-line">
              <span>
                Rincian {{ (int) ($line['line_no'] ?? 0) }} · {{ $line['label'] ?? '-' }}
                ({{ $line['type_label'] ?? '-' }} · {{ $line['status'] ?? '-' }})
              </span>
              <strong>{{ number_format((int) ($line['subtotal_rupiah'] ?? 0), 0, ',', '.') }}</strong>
            </div>
          @endforeach
        </div>
      @endif

      @if (!empty($entry['change_summary_lines']) || !empty($entry['reason']))
        <div class="note-detail-version-summary">
          @foreach (($entry['change_summary_lines'] ?? []) as $summary)
            <div>• {{ $summary }}</div>
          @endforeach
          @if (!empty($entry['reason']))
            <div><strong>Alasan:</strong> {{ $entry['reason'] }}</div>
          @endif
        </div>
      @endif
    </section>
  @endforeach
</div>
