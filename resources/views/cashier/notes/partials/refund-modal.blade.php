@if ($note['can_show_refund_form'] ?? false)
<div class="modal fade" id="note-refund-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title mb-0">Refund</h5>
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
                    <div class="fw-semibold">Line Terpilih</div>
                    <div class="small text-muted">Hanya line yang Anda klik akan diproses. Line belum dibayar akan dibatalkan tanpa refund uang.</div>
                  </div>
                  <span class="badge bg-light text-dark border" id="refund-modal-selected-count">0</span>
                </div>

                <div id="note-refund-selected-lines" class="d-flex flex-column gap-2">
                  <div class="small text-muted">Belum ada line dipilih.</div>
                </div>
              </div>
            </div>

            <div class="col-12 col-lg-6">
              <div class="d-flex flex-column gap-3">
                <div class="border rounded p-3">
                  <div class="fw-semibold mb-3">Perkiraan Dampak</div>

                  <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Nominal Refund</span>
                    <strong id="refund-modal-selected-total">0</strong>
                  </div>

                  <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Stok Toko Kembali</span>
                    <strong id="refund-modal-stock-return-count">0</strong>
                  </div>

                  <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Komponen External Dinetralkan</span>
                    <strong id="refund-modal-external-count">0</strong>
                  </div>

                  <div class="small text-muted mt-3" id="refund-modal-impact-note">
                    Refund akan dicatat untuk line yang dipilih sesuai total refundable saat ini.
                  </div>
                </div>

                <div class="border rounded p-3">
                  <div class="fw-semibold mb-2">Stok Toko Kembali</div>
                  <div id="refund-modal-store-returns" class="d-flex flex-column gap-2">
                    <div class="small text-muted">Tidak ada stok toko yang kembali.</div>
                  </div>
                </div>

                <div class="border rounded p-3">
                  <div class="fw-semibold mb-2">Komponen External Dinetralkan</div>
                  <div id="refund-modal-external-returns" class="d-flex flex-column gap-2">
                    <div class="small text-muted">Tidak ada komponen external yang dinetralkan.</div>
                  </div>
                </div>

                <div class="border rounded p-3">
                  <div class="fw-semibold mb-3">Alasan Refund / Pembatalan</div>

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
                      placeholder="Tulis alasan refund / pembatalan"
                    >
                  </div>

                  <div class="row g-3">
                    <div class="col-12">
                      <label class="form-label">Tanggal Refund / Pembatalan</label>
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
            <button type="submit" class="btn btn-danger" id="note-refund-submit" disabled>Catat Refund / Batalkan Line</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
@endif
