@if ($note['correction_history'] !== [])
  <section class="note-detail-correction-block">
    <h5 class="note-detail-correction-title">Mutasi Nota</h5>

    <div class="note-detail-correction-list">
      @foreach ($note['correction_history'] as $entry)
        <article class="note-detail-correction-row">
          <div class="note-detail-correction-head">
            <strong>{{ $entry['event_label'] }}</strong>
            <time>{{ \App\Support\ViewDateFormatter::display($entry['created_at'] ?? null, true) }}</time>
          </div>

          @if (in_array($entry['mutation_type'] ?? '', ['note_cancelled', 'note_restored'], true) && ($entry['performed_by_actor_id'] ?? null))
            <div>Aktor: {{ $entry['performed_by_actor_id'] }}</div>
          @endif

          @if ($entry['reason'] !== null)
            <div><strong>Alasan:</strong> {{ $entry['reason'] }}</div>
          @endif

          @if ($entry['target_status'] !== null)
            <div><strong>Status tujuan:</strong> {{ $entry['target_status'] }}</div>
          @endif

          <div>
            Total Sebelum: {{ number_format((int) ($entry['before_total_rupiah'] ?? 0), 0, ',', '.') }}
            · Total Sesudah: {{ number_format((int) ($entry['after_total_rupiah'] ?? 0), 0, ',', '.') }}
            · Pengembalian Wajib: {{ number_format((int) ($entry['refund_required_rupiah'] ?? 0), 0, ',', '.') }}
          </div>
        </article>
      @endforeach
    </div>
  </section>
@endif
