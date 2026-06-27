@if ($note['correction_history'] !== [])
    <div class="card mt-3">
        <div class="card-body">
            <div class="fw-bold mb-3">Riwayat Mutasi Nota</div>

            <div class="d-flex flex-column gap-3">
                @foreach ($note['correction_history'] as $entry)
                    <div class="border rounded p-3">
                        <div class="fw-bold">{{ $entry['event_label'] }}</div>
                        <div class="small text-muted">{{ \App\Support\ViewDateFormatter::display($entry['created_at'] ?? null, true) }}</div>

                        @if ($entry['reason'] !== null)
                            <div class="mt-2"><span class="text-muted">Alasan:</span> {{ $entry['reason'] }}</div>
                        @endif

                        @if ($entry['performed_by_actor_id'] !== null)
                            <div><span class="text-muted">Diproses oleh:</span> {{ $entry['performed_by_actor_id'] }}</div>
                        @endif

                        @if ($entry['target_status'] !== null)
                            <div><span class="text-muted">Status tujuan:</span> {{ $entry['target_status'] }}</div>
                        @endif

                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <span class="text-muted">Total Sebelum:</span>
                                {{ number_format((int) ($entry['before_total_rupiah'] ?? 0), 0, ',', '.') }}
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted">Total Sesudah:</span>
                                {{ number_format((int) ($entry['after_total_rupiah'] ?? 0), 0, ',', '.') }}
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted">Pengembalian Wajib:</span>
                                {{ number_format((int) ($entry['refund_required_rupiah'] ?? 0), 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
