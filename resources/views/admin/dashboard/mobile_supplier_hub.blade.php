@extends('layouts.app')

@section('title', 'Pembayaran Supplier')
@section('heading', 'Pembayaran Supplier')

@push('styles')
    <style>
        .mobile-supplier-hub {
            max-width: 720px;
            margin: 0 auto;
        }

        .mobile-supplier-hub-action {
            min-height: 118px;
            border-radius: 1.35rem;
            text-align: left;
            box-shadow: 0 .8rem 1.8rem rgba(15, 23, 42, .08);
        }

        .mobile-supplier-hub-action i {
            font-size: 1.75rem;
        }

        .mobile-supplier-row {
            width: 100%;
            border: 1px solid rgba(var(--bs-primary-rgb), .12);
            border-radius: 1rem;
            background: var(--bs-body-bg);
            box-shadow: 0 .45rem 1.2rem rgba(15, 23, 42, .045);
        }

        .mobile-supplier-download {
            color: inherit;
            text-decoration: none;
        }
    </style>
@endpush

@section('content')
    <section
        class="section mobile-supplier-hub"
        data-mobile-supplier-hub
        data-initial-tab="{{ $mobileTab }}"
    >
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6">
                <button
                    type="button"
                    class="btn btn-primary w-100 mobile-supplier-hub-action p-4"
                    data-mobile-hub-action="pay"
                    aria-pressed="false"
                >
                    <i class="bi bi-cash-stack d-block mb-2" aria-hidden="true"></i>
                    <strong class="d-block fs-5">Bayar Supplier</strong>
                    <span class="small opacity-75">Pilih nota yang masih punya hutang</span>
                </button>
            </div>

            <div class="col-12 col-sm-6">
                <button
                    type="button"
                    class="btn btn-outline-primary w-100 mobile-supplier-hub-action p-4"
                    data-mobile-hub-action="history"
                    aria-pressed="false"
                >
                    <i class="bi bi-receipt d-block mb-2" aria-hidden="true"></i>
                    <strong class="d-block fs-5">Cek Pembayaran Supplier</strong>
                    <span class="small">Bukti terbaru tampil paling atas</span>
                </button>
            </div>
        </div>

        <div class="d-none" data-mobile-hub-section="pay">
            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                <div>
                    <h5 class="mb-1">Nota Belum Lunas</h5>
                    <div class="small text-muted">Tap nota untuk kirim bukti dan melunasi sisa tagihan.</div>
                </div>
                <span class="badge bg-light-primary">{{ count($outstandingInvoices) }}</span>
            </div>

            @if ($outstandingInvoices === [])
                <div class="alert alert-success mb-0">Tidak ada hutang supplier yang perlu dibayar.</div>
            @else
                <div class="d-flex flex-column gap-3">
                    @foreach ($outstandingInvoices as $invoice)
                        <button
                            type="button"
                            class="mobile-supplier-row p-3 text-start"
                            data-mobile-pay-invoice
                            data-invoice-id="{{ $invoice['supplier_invoice_id'] }}"
                            data-invoice-no="{{ $invoice['invoice_no'] }}"
                            data-supplier-name="{{ $invoice['supplier_name'] }}"
                            data-outstanding-label="Rp {{ number_format($invoice['outstanding_rupiah'], 0, ',', '.') }}"
                            data-due-date="{{ $invoice['due_date'] }}"
                        >
                            <div class="d-flex align-items-start justify-content-between gap-3">
                                <div class="min-w-0">
                                    <strong class="d-block text-truncate">{{ $invoice['supplier_name'] }}</strong>
                                    <span class="small text-muted d-block">{{ $invoice['invoice_no'] }}</span>
                                    <span class="small text-muted d-block mt-1">
                                        Jatuh tempo: {{ \App\Support\ViewDateFormatter::display($invoice['due_date']) }}
                                    </span>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <small class="text-muted d-block">Sisa</small>
                                    <strong>Rp {{ number_format($invoice['outstanding_rupiah'], 0, ',', '.') }}</strong>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="d-none" data-mobile-hub-section="history">
            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                <div>
                    <h5 class="mb-1">Pembayaran Terbaru</h5>
                    <div class="small text-muted">Tap baris untuk langsung mengunduh bukti pembayaran.</div>
                </div>
                <span class="badge bg-light-primary">{{ count($recentPaymentProofs) }}</span>
            </div>

            @if ($recentPaymentProofs === [])
                <div class="alert alert-light border mb-0">Belum ada bukti pembayaran supplier.</div>
            @else
                <div class="d-flex flex-column gap-3">
                    @foreach ($recentPaymentProofs as $proof)
                        <a
                            href="{{ route('admin.procurement.supplier-payment-proof-attachments.show', ['attachmentId' => $proof['attachment_id'], 'download' => 1]) }}"
                            class="mobile-supplier-row mobile-supplier-download p-3"
                        >
                            <div class="d-flex align-items-start justify-content-between gap-3">
                                <div class="min-w-0">
                                    <strong class="d-block text-truncate">{{ $proof['supplier_name'] }}</strong>
                                    <span class="small text-muted d-block">{{ $proof['invoice_no'] }}</span>
                                    <span class="small text-muted d-block mt-1 text-truncate">{{ $proof['original_filename'] }}</span>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <strong class="d-block">Rp {{ number_format($proof['amount_rupiah'], 0, ',', '.') }}</strong>
                                    <small class="text-muted">{{ \App\Support\ViewDateFormatter::display($proof['paid_at']) }}</small>
                                    <i class="bi bi-download d-block mt-2" aria-hidden="true"></i>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="modal fade" id="mobile-supplier-payment-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-1">Bayar Supplier</h5>
                            <div class="small text-muted" data-mobile-payment-supplier></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">
                        <div class="border rounded p-3 mb-3">
                            <div class="small text-muted">Nota</div>
                            <strong data-mobile-payment-invoice></strong>
                            <div class="small text-muted mt-2">Sisa yang akan dilunasi</div>
                            <strong class="text-primary" data-mobile-payment-outstanding></strong>
                        </div>

                        <form
                            id="mobile-supplier-payment-form"
                            data-supplier-proof-direct-upload
                            data-scope-type="supplier_invoice"
                            data-scope-id=""
                            data-prepare-url="{{ route('admin.procurement.supplier-payment-proofs.direct-upload.prepare') }}"
                            data-finalize-url="{{ route('admin.procurement.supplier-payment-proofs.direct-upload.finalize', ['uploadIntentId' => '__INTENT__']) }}"
                            data-success-url="{{ route('admin.dashboard', ['mobile_tab' => 'history']) }}"
                        >
                            <div class="form-group mb-3">
                                <label for="mobile_supplier_proof_file" class="form-label">Galeri / File</label>
                                <input
                                    type="file"
                                    id="mobile_supplier_proof_file"
                                    class="form-control"
                                    accept=".jpg,.jpeg,.png,.webp,.heic,.heif,.pdf,image/jpeg,image/png,image/webp,image/heic,image/heif,application/pdf"
                                    required
                                >
                                <small class="text-muted d-block mt-1">Pilih satu bukti dari galeri/file, atau gunakan kamera.</small>
                            </div>

                            <div class="small text-muted mb-3" data-direct-upload-status aria-live="polite"></div>

                            <button
                                type="submit"
                                class="btn btn-primary w-100"
                                data-direct-upload-submit
                                data-submitting-label="Mengirim..."
                            >
                                Kirim Bukti &amp; Tandai Lunas
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/static/js/pages/admin-mobile-supplier-hub.js') }}?v={{ config('app.asset_version') }}"></script>
    @include('admin.procurement.partials.supplier_payment_proof_direct_upload_script')
@endpush
