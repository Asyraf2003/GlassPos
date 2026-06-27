@if ($note['can_show_refund_form'] ?? false)
<div class="modal fade" id="note-refund-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title mb-0">Pengembalian Dana</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>

      <form method="POST" action="{{ $refundModalConfig['action'] ?? $refundAction }}" id="note-refund-form">
        @csrf
        <div id="note-refund-hidden-selected-rows"></div>
        <input type="hidden" id="refund_amount_rupiah" value="">

        <div class="modal-body">
          <div class="row g-4 align-items-start">
            <div class="col-12 col-lg-6">
              <div class="border rounded p-3 h-100">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                  <div>
                    <div class="fw-semibold">Rincian Terpilih</div>
                    <div class="small text-muted">Hanya rincian yang Anda klik akan diproses. Rincian yang belum dibayar akan dibatalkan tanpa pengembalian uang.</div>
                  </div>
                  <span class="badge border" id="refund-modal-selected-count">0</span>
                </div>

                <div id="note-refund-selected-lines" class="d-flex flex-column gap-2">
                  <div class="small text-muted">Belum ada rincian dipilih.</div>
                </div>
              </div>
            </div>

            <div class="col-12 col-lg-6">
              <div class="d-flex flex-column gap-3">
                <div class="border rounded p-3">
                  <div class="fw-semibold mb-3">Perkiraan Dampak</div>

                  <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Nominal Pengembalian Dana</span>
                    <strong id="refund-modal-selected-total">0</strong>
                  </div>

                  <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Stok Toko Kembali</span>
                    <strong id="refund-modal-stock-return-count">0</strong>
                  </div>

                  <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Komponen Luar Dinetralkan</span>
                    <strong id="refund-modal-external-count">0</strong>
                  </div>

                  <div class="small text-muted mt-3" id="refund-modal-impact-note">
                    Pengembalian dana akan dicatat untuk rincian yang dipilih sesuai nominal yang bisa dikembalikan saat ini.
                  </div>
                </div>

                <div class="border rounded p-3">
                  <div class="fw-semibold mb-2">Stok Toko Kembali</div>
                  <div id="refund-modal-store-returns" class="d-flex flex-column gap-2">
                    <div class="small text-muted">Tidak ada stok toko yang kembali.</div>
                  </div>
                </div>

                <div class="border rounded p-3">
                  <div class="fw-semibold mb-2">Komponen Luar Dinetralkan</div>
                  <div id="refund-modal-external-returns" class="d-flex flex-column gap-2">
                    <div class="small text-muted">Tidak ada komponen luar yang dinetralkan.</div>
                  </div>
                </div>

                <div class="border rounded p-3">
                  <div class="fw-semibold mb-3">Alasan Pengembalian Dana / Pembatalan</div>

                  <div class="mb-3">
                    <label class="form-label">Alasan</label>
                    <input
                      type="text"
                      id="note-refund-reason"
                      name="reason"
                      value="{{ old('reason') }}"
                      class="form-control"
                      required
                      autocomplete="off"
                      placeholder="Tulis alasan pengembalian dana atau pembatalan"
                    >
                  </div>

                  <div class="row g-3">
                    <div class="col-12">
                      <label class="form-label">Tanggal Pengembalian Dana / Pembatalan</label>
                      <input
                        type="date"
                        name="refunded_at"
                        value="{{ old('refunded_at', $refundModalConfig['date_default'] ?? $refundDateDefault) }}"
                        class="form-control"
                        required
                      >
                    </div>

                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <div class="ui-form-actions w-100 justify-content-between">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-danger" id="note-refund-submit" disabled>Catat Pengembalian Dana / Batalkan Rincian</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
@endif
