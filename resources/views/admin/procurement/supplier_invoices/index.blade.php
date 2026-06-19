@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Nota Pemasok')
@section('heading', 'Nota Pemasok')

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                    <div>
                        <h4 class="card-title mb-1">Tabel nota pemasok interaktif untuk admin</h4>
                    </div>

                    <div class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                        <form id="procurement-search-form" class="m-0 d-flex flex-grow-1">
                            <input
                                type="text"
                                id="procurement-search-input"
                                class="form-control"
                                placeholder="Cari nomor faktur atau nama PT"
                                autocomplete="off"
                                style="min-height: 48px;"
                            >
                        </form>

                        <button type="button" id="open-procurement-filter" class="btn btn-primary px-4" style="min-height: 48px;">
                            Filter
                        </button>

                        <a href="{{ route('admin.procurement.supplier-invoices.create') }}" class="btn btn-primary px-4 d-flex align-items-center justify-content-center" style="min-height: 48px;">
                            Buat Nota Pemasok
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-lg" id="procurement-invoice-table">
                        <thead>
                            <tr class="text-nowrap">
                                <th style="width: 64px;">No</th>
                                <th>Nomor Faktur</th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="nama_pt_pengirim">
                                        Nama PT
                                        <span class="ms-1 text-muted" data-sort-indicator="nama_pt_pengirim">↕</span>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="shipment_date">
                                        Tanggal Kirim
                                        <span class="ms-1 text-muted" data-sort-indicator="shipment_date">↕</span>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="due_date">
                                        Jatuh Tempo
                                        <span class="ms-1 text-muted" data-sort-indicator="due_date">↕</span>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="grand_total_rupiah">
                                        Total Keseluruhan
                                        <span class="ms-1 text-muted" data-sort-indicator="grand_total_rupiah">↕</span>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="total_paid_rupiah">
                                        Total Dibayar
                                        <span class="ms-1 text-muted" data-sort-indicator="total_paid_rupiah">↕</span>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="outstanding_rupiah">
                                        Sisa Tagihan
                                        <span class="ms-1 text-muted" data-sort-indicator="outstanding_rupiah">↕</span>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="receipt_count">
                                        Penerimaan Barang
                                        <span class="ms-1 text-muted" data-sort-indicator="receipt_count">↕</span>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="total_received_qty">
                                        Kuantitas Diterima
                                        <span class="ms-1 text-muted" data-sort-indicator="total_received_qty">↕</span>
                                    </button>
                                </th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="procurement-invoice-table-body">
                            <tr><td colspan="11" class="text-center text-muted py-4">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
                    <small id="procurement-invoice-table-summary" class="text-muted">Total: -</small>
                    <div id="procurement-invoice-table-pagination"></div>
                </div>

                <div id="procurement-active-filters" class="d-none mt-3 pt-3 border-top">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div class="d-flex flex-wrap gap-2" id="procurement-active-filter-chips"></div>

                        <button
                            type="button"
                            id="procurement-reset-all-filters"
                            class="btn btn-light-secondary btn-sm"
                        >
                            Reset Semua Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @include('admin.procurement.supplier_invoices.partials.filter_drawer')

        <div
            class="modal fade"
            id="procurement-action-modal"
            tabindex="-1"
            aria-labelledby="procurement-action-modal-title"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0 px-4 pt-4">
                        <div class="w-100">
                            <h3 class="modal-title fw-bold mb-1" id="procurement-action-modal-title">Aksi Nota Pemasok</h3>
                            <p class="mb-0 text-muted fs-6" id="procurement-action-modal-subtitle">
                                Pilih tindakan untuk nota pemasok.
                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body px-4 pb-4 pt-3">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <a
                                    href="#"
                                    id="procurement-action-detail-link"
                                    class="btn btn-outline-primary w-100 text-start py-3 px-4 h-100"
                                >
                                    <div class="fw-bold fs-5 mb-1">Detail</div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <a
                                    href="#"
                                    id="procurement-action-payment-link"
                                    class="btn btn-outline-primary w-100 text-start py-3 px-4 h-100"
                                >
                                    <div class="fw-bold fs-5 mb-1" id="procurement-action-payment-title">Bayar</div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <a
                                    href="#"
                                    id="procurement-action-edit-link"
                                    class="btn btn-outline-primary w-100 text-start py-3 px-4 h-100 disabled"
                                    aria-disabled="true"
                                    tabindex="-1"
                                >
                                    <div class="fw-bold fs-5 mb-1" id="procurement-action-edit-title">Edit Nota</div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <button
                                    type="button"
                                    id="procurement-action-void-button"
                                    class="btn btn-outline-danger w-100 text-start py-3 px-4 h-100"
                                    disabled
                                    aria-disabled="true"
                                >
                                    <div class="fw-bold fs-5 mb-1" id="procurement-action-void-title">Hapus Nota</div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="modal fade"
            id="procurement-payment-modal"
            tabindex="-1"
            aria-labelledby="procurement-payment-modal-title"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0 px-4 pt-4">
                        <div class="w-100">
                            <h3 class="modal-title fw-bold mb-1" id="procurement-payment-modal-title">Kirim Bukti Pembayaran</h3>
                            <p class="mb-0 text-muted fs-6" id="procurement-payment-modal-subtitle">
                                Kirim bukti pembayaran dari kamera atau galeri. Nota otomatis ditandai lunas sesuai sisa tagihan.
                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body px-4 pb-4 pt-3">
                        <form method="post" id="procurement-payment-form" enctype="multipart/form-data">
                            @csrf

                            <input type="hidden" name="payment_invoice_id" id="procurement-payment-invoice-id" value="{{ old('payment_invoice_id') }}">

                            <div class="form-group mb-4">
                                <label for="procurement-payment-proof-files" class="form-label">Bukti Pembayaran</label>
                                <input
                                    type="file"
                                    id="procurement-payment-proof-files"
                                    name="proof_files[]"
                                    class="form-control @error('proof_files') is-invalid @enderror @error('proof_files.*') is-invalid @enderror"
                                    accept="image/*,.pdf"
                                    multiple
                                    required
                                >
                                @error('proof_files')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                @error('proof_files.*')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <p class="fs-5 text-muted d-block mt-2">
                                    Pilih foto dari kamera/galeri atau PDF. Setelah dikirim, sistem mencatat pembayaran sebesar sisa tagihan dan menandai nota lunas.
                                </p>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Tutup</button>
                                <button type="submit" class="btn btn-primary">Kirim Bukti & Tandai Lunas</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="modal fade"
            id="procurement-void-modal"
            tabindex="-1"
            aria-labelledby="procurement-void-modal-title"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0 px-4 pt-4">
                        <div class="w-100">
                            <h3 class="modal-title fw-bold mb-1" id="procurement-void-modal-title">Hapus Nota</h3>
                            <p class="mb-0 text-muted fs-6" id="procurement-void-modal-subtitle">
                                Isi alasan pembatalan nota pemasok.
                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body px-4 pb-4 pt-3">
                        @error('supplier_invoice')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror

                        <form method="post" id="procurement-void-form">
                            @csrf

                            <input type="hidden" name="void_invoice_id" id="procurement-void-invoice-id" value="{{ old('void_invoice_id') }}">

                            <div class="form-group mb-4">
                                <label for="procurement-void-reason" class="form-label">Alasan Pembatalan</label>
                                <textarea
                                    id="procurement-void-reason"
                                    name="void_reason"
                                    class="form-control @error('void_reason') is-invalid @enderror"
                                    rows="4"
                                    placeholder="Contoh: Salah input sebelum ada pembayaran atau receipt."
                                    required
                                >{{ old('void_reason') }}</textarea>
                                @error('void_reason')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Tutup</button>
                                <button type="submit" class="btn btn-danger" id="procurement-void-submit" disabled>
                                    Hapus Nota
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    @if (session('clear_procurement_create_draft'))
        <script>
            try {
                window.localStorage.removeItem('admin.procurement.create-supplier-invoice.draft.v1');
                window.localStorage.removeItem('admin.procurement.create-supplier-invoice.draft.v2');
            } catch (_error) {
                // ignore localStorage failures
            }
        </script>
    @endif

    <script src="{{ asset('assets/static/js/shared/admin-money-input.js') }}?v={{ config('app.asset_version') }}"></script>
    <script>
        window.procurementInvoiceTableConfig = {
            endpoint: @json(route('admin.procurement.supplier-invoices.table')),
            detailBaseUrl: @json(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => '__ID__'])),
            paymentProofStoreBaseUrl: @json(route('admin.procurement.supplier-invoices.payment-proof.store', ['supplierInvoiceId' => '__ID__'])),
            oldPaymentInvoiceId: @json(old('payment_invoice_id')),
            oldPaymentDate: @json(old('payment_date', now()->format('Y-m-d'))),
            oldPaymentAmount: @json(old('amount')),
            oldVoidInvoiceId: @json(old('void_invoice_id')),
            oldVoidReason: @json(old('void_reason'))
        };
    </script>
    <script src="{{ asset('assets/static/js/pages/admin-procurement-invoices-table.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
