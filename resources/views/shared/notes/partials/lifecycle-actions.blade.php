@if (isset($lifecycle))
  <div class="note-detail-lifecycle" id="note-lifecycle">
    @if ($lifecycle['mode'] === 'cancel')
      <p>Seluruh transaksi dibatalkan. Tagihan aktif menjadi Rp 0; penjualan, piutang dan profit transaksi tidak lagi aktif. Riwayat tetap tersimpan.</p>
      <p>Stok pada revisi ini: {{ $lifecycle['stock_units'] }} unit. Pengembalian menggunakan riwayat pengeluaran stok, satu kali. Pastikan barang kembali atau tidak jadi keluar.</p>

      @if ($lifecycle['stale'])
        <p role="alert">Transaksi berubah. Muat ulang halaman sebelum mengirim permintaan baru.</p>
      @endif

      <form id="note-lifecycle-form" method="POST" action="{{ $lifecycle['action'] }}">
        @csrf
        <input type="hidden" name="lifecycle_form" value="{{ $lifecycle['form_id'] }}">
        <input type="hidden" name="base_revision_id" value="{{ $lifecycle['base_revision_id'] }}">
        <input type="hidden" name="idempotency_key" value="{{ $lifecycle['idempotency_key'] }}">

        <label class="form-label" for="note-lifecycle-reason">Alasan pembatalan</label>
        <textarea id="note-lifecycle-reason" name="reason" class="form-control mb-3" required maxlength="500">{{ $lifecycle['reason'] }}</textarea>
        <button class="btn btn-outline-danger" type="submit" @disabled($lifecycle['stale'])>Batalkan Transaksi</button>
      </form>
    @elseif ($lifecycle['mode'] === 'restore')
      <p>Revisi baru dibuat dari revisi {{ $lifecycle['revision_number'] }} dengan nilai Rp {{ number_format($lifecycle['source_total'], 0, ',', '.') }}. Riwayat pembatalan tetap tersimpan.</p>
      <p>Stok yang diperlukan: {{ $lifecycle['stock_units'] }} unit. Stok akan diperiksa dan dikeluarkan kembali. Tidak ada pembayaran otomatis.</p>

      @if ($lifecycle['stale'])
        <p role="alert">Transaksi berubah. Muat ulang halaman sebelum mengirim permintaan baru.</p>
      @endif

      <button
        type="button"
        class="btn btn-outline-danger"
        data-bs-toggle="modal"
        data-bs-target="#note-restore-modal"
        @disabled($lifecycle['stale'])
      >
        Pulihkan
      </button>

      <div class="modal fade" id="note-restore-modal" tabindex="-1" aria-labelledby="note-restore-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <form id="note-lifecycle-form" method="POST" action="{{ $lifecycle['action'] }}">
              @csrf
              <input type="hidden" name="lifecycle_form" value="{{ $lifecycle['form_id'] }}">
              <input type="hidden" name="base_revision_id" value="{{ $lifecycle['base_revision_id'] }}">
              <input type="hidden" name="idempotency_key" value="{{ $lifecycle['idempotency_key'] }}">
              <input type="hidden" name="source_revision_id" value="{{ $lifecycle['source_revision_id'] }}">
              <input type="hidden" name="cancellation_event_id" value="{{ $lifecycle['cancellation_event_id'] }}">

              <div class="modal-header">
                <h5 class="modal-title" id="note-restore-modal-title">Pulihkan Nota</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
              </div>

              <div class="modal-body">
                <label class="form-label" for="note-lifecycle-reason">Alasan pemulihan</label>
                <input
                  id="note-lifecycle-reason"
                  type="text"
                  name="reason"
                  class="form-control"
                  value="{{ $lifecycle['reason'] }}"
                  required
                  maxlength="500"
                  autocomplete="off"
                >
              </div>

              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-outline-danger" type="submit">Pulihkan sebagai revisi baru</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <script>
        document.getElementById('note-restore-modal')?.addEventListener('shown.bs.modal', function () {
          document.getElementById('note-lifecycle-reason')?.focus();
        });
      </script>
    @else
      <p class="mb-0">{{ $lifecycle['message'] }}</p>
    @endif
  </div>
@endif
