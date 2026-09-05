@if ($note['can_show_payment_form'] ?? false)
<div
  class="modal fade"
  id="note-payment-modal"
  tabindex="-1"
  aria-hidden="true"
  data-allow-partial="{{ ($note['can_show_partial_payment_action'] ?? false) ? '1' : '0' }}"
  data-default-mode="{{ ($note['can_show_partial_payment_action'] ?? false) ? 'partial' : 'full' }}"
>
  <div class="modal-dialog modal-xl modal-dialog-centered" id="note-payment-modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title mb-0" id="detail-payment-title">Proses Nota</h5>
        <p
          class="visually-hidden"
          id="detail-payment-subtitle"
          aria-hidden="true"
        >Pilih aksi pembayaran, cek nominal, lalu bayar transfer atau tunai.</p>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>

      <form method="POST" action="{{ $paymentModalConfig['action'] ?? $paymentAction }}" id="note-payment-form">
        @csrf

        <input type="hidden" name="payment_scope" value="partial">
        <input type="hidden" name="payment_method" id="detail_payment_method" value="">
        <input type="hidden" name="paid_at" id="detail_payment_paid_at" value="{{ $paymentModalConfig['date_default'] ?? $paymentDateDefault }}">
        <input type="hidden" name="amount_paid" id="detail_payment_amount_paid" value="">
        <input type="hidden" name="amount_received" id="detail_payment_amount_received" value="">
        <input
          type="hidden"
          name="idempotency_key"
          value="{{ old('idempotency_key', $paymentModalConfig['idempotency_key'] ?? '') }}"
        >

        <div id="payment-selected-row-ids"></div>

        <div class="d-none" id="detail-payment-row-source">
          @foreach (($note['billing_rows'] ?? []) as $row)
            @if (!($row['is_paid'] ?? false) && (int) ($row['outstanding_rupiah'] ?? 0) > 0)
              <span
                data-payment-row-source
                data-billing-row-id="{{ $row['id'] }}"
                data-row-id="{{ $row['id'] }}"
                data-work-item-id="{{ $row['work_item_id'] }}"
                data-label="Baris {{ $row['line_no'] }} · {{ $row['component_label'] }}"
                data-type-label="{{ $row['domain_type_label'] ?? '-' }}"
                data-outstanding-rupiah="{{ (int) ($row['outstanding_rupiah'] ?? 0) }}"
                data-is-service-component="{{ ($row['is_service_component'] ?? false) ? '1' : '0' }}"
                data-is-product-component="{{ ($row['is_product_component'] ?? false) ? '1' : '0' }}"
                data-eligible-dp="{{ ($row['eligible_for_dp_preset'] ?? false) ? '1' : '0' }}"
              ></span>
            @endif
          @endforeach
        </div>

        <div class="modal-body">
          <div id="detail-payment-standard-view">
            <div class="row g-4">
              <div class="col-12 col-lg-7">
                <div class="border rounded p-3 h-100">
                  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div class="fw-semibold">Tagihan</div>
                    <span class="badge border" id="detail-payment-mode-text">
                      {{ ($note['can_show_partial_payment_action'] ?? false) ? 'Bayar Sebagian' : 'Lunasi' }}
                    </span>
                  </div>

                  <div class="d-flex flex-column gap-2" id="detail-payment-line-summary">
                    <div class="p-3 text-muted small">Belum ada sisa tagihan.</div>
                  </div>
                </div>
              </div>

              <div class="col-12 col-lg-5">
                <div class="border rounded p-3 mb-3">
                  <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Sisa Tagihan Terpilih</span>
                    <strong id="detail-payment-selected-total">0</strong>
                  </div>
                  <div class="d-flex justify-content-between pt-2">
                    <span class="fw-semibold">Dibayar</span>
                    <strong id="detail-payment-payable-text">0</strong>
                  </div>
                </div>

                @if ($note['can_show_partial_payment_action'] ?? false)
                  <div class="border rounded p-3 mb-3" id="detail-payment-partial-panel">
                    <label for="detail_payment_amount_paid_display" class="form-label">Nominal Bayar Sebagian</label>
                    <input
                      type="text"
                      class="form-control"
                      id="detail_payment_amount_paid_display"
                      value="{{ old('amount_paid') }}"
                      placeholder="Contoh: 50.000"
                    >
                  </div>
                @endif

                <div class="border rounded p-3">
                  <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Sisa Setelah Bayar</span>
                    <strong id="detail-payment-remaining-text">0</strong>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div id="detail-payment-cash-view" class="d-none">
            <div style="max-width: 460px; margin: 0 auto;">
              <div class="border rounded p-4">
                <div class="visually-hidden">Kalkulator Tunai</div>
                <div class="visually-hidden" id="workspace-cash-mode-text" aria-hidden="true">
                  {{ ($note['can_show_partial_payment_action'] ?? false) ? 'Bayar Sebagian' : 'Bayar Penuh' }}
                </div>

                <div class="d-grid gap-3">
                  <div class="border rounded p-4 text-center">
                    <div class="small text-muted mb-2">Tagihan</div>
                    <div class="fs-1 fw-bold lh-sm" id="workspace-cash-payable-text">0</div>
                  </div>

                  <div class="border rounded p-4 text-center" data-money-input-group>
                    <label for="inline_payment_amount_received_display" class="small text-muted mb-2">Uang Pelanggan</label>

                    <input type="hidden" value="" data-money-raw data-cash-received-raw>

                    <input
                      type="text"
                      id="inline_payment_amount_received_display"
                      value=""
                      class="form-control border-0 bg-transparent text-center fs-1 fw-bold lh-sm p-0 shadow-none"
                      inputmode="numeric"
                      placeholder="0"
                      data-money-display
                      autocomplete="off"
                    >
                  </div>

                  <div class="border rounded p-4 text-center">
                    <div class="small text-muted mb-2">Kembalian</div>
                    <div class="fs-1 fw-bold lh-sm" id="workspace-cash-change-text">0</div>
                  </div>

                  <div class="border rounded p-4 text-center">
                    <div class="small text-muted mb-2">Sisa</div>
                    <div class="fs-3 fw-bold lh-sm" id="workspace-cash-remaining-text">0</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <div class="ui-form-actions w-100 justify-content-between" id="detail-payment-footer-main">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-outline-primary" id="detail-payment-submit-transfer">
                Bayar Transfer
              </button>
              <button type="button" class="btn btn-primary" id="detail-payment-open-cash">
                Bayar Tunai
              </button>
            </div>
          </div>

          <div class="ui-form-actions w-100 justify-content-between d-none" id="detail-payment-footer-cash">
            <button type="button" class="btn btn-outline-secondary" id="detail-payment-back-cash">Kembali</button>
            <button type="submit" class="btn btn-primary" id="detail-payment-submit-cash">
              Simpan Tunai
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
@endif
