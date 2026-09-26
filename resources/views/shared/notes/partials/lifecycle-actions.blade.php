@if (isset($lifecycle))
  <div class="card mb-3" id="note-lifecycle">
    <div class="card-body">
      @if (in_array($lifecycle['mode'], ['cancel', 'restore'], true))
        <details>
          <summary class="fw-bold">{{ $lifecycle['mode'] === 'cancel' ? 'Batalkan Transaksi' : 'Pulihkan' }}</summary>
          @if ($lifecycle['mode'] === 'cancel')
            <p class="mt-3">Seluruh transaksi dibatalkan. Tagihan aktif menjadi Rp 0; penjualan, piutang dan profit transaksi tidak lagi aktif. Riwayat tetap tersimpan.</p>
            <p>Stok pada revisi ini: {{ $lifecycle['stock_units'] }} unit. Pengembalian menggunakan riwayat pengeluaran stok, satu kali. Pastikan barang kembali atau tidak jadi keluar.</p>
          @else
            <p class="mt-3">Revisi baru dibuat dari revisi {{ $lifecycle['revision_number'] }} dengan nilai Rp {{ number_format($lifecycle['source_total'], 0, ',', '.') }}. Riwayat pembatalan tetap tersimpan.</p>
            <p>Stok yang diperlukan: {{ $lifecycle['stock_units'] }} unit. Stok akan diperiksa dan dikeluarkan kembali. Tidak ada pembayaran otomatis.</p>
          @endif
          @if ($lifecycle['stale'])
            <p role="alert">Transaksi berubah. Muat ulang halaman sebelum mengirim permintaan baru.</p>
          @endif
          <form id="note-lifecycle-form" method="POST" action="{{ $lifecycle['action'] }}">
            @csrf
            <input type="hidden" name="lifecycle_form" value="{{ $lifecycle['form_id'] }}">
            <input type="hidden" name="base_revision_id" value="{{ $lifecycle['base_revision_id'] }}">
            <input type="hidden" name="idempotency_key" value="{{ $lifecycle['idempotency_key'] }}">
            @if ($lifecycle['mode'] === 'restore')
              <input type="hidden" name="source_revision_id" value="{{ $lifecycle['source_revision_id'] }}">
              <input type="hidden" name="cancellation_event_id" value="{{ $lifecycle['cancellation_event_id'] }}">
            @endif
            <label class="form-label" for="note-lifecycle-reason">Alasan {{ $lifecycle['mode'] === 'cancel' ? 'pembatalan' : 'pemulihan' }}</label>
            <textarea id="note-lifecycle-reason" name="reason" class="form-control mb-3" required maxlength="500">{{ $lifecycle['reason'] }}</textarea>
            <button class="btn btn-outline-danger" type="submit" @disabled($lifecycle['stale'])>{{ $lifecycle['mode'] === 'cancel' ? 'Batalkan Transaksi' : 'Pulihkan sebagai revisi baru' }}</button>
          </form>
        </details>
      @else
        <div class="fw-bold">Batalkan Transaksi</div>
        <p class="mb-0 mt-2">{{ $lifecycle['message'] }}</p>
      @endif
    </div>
  </div>
@endif
