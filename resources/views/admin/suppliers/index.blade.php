@extends('layouts.app')

@section('title', 'Pemasok')
@section('heading', 'Pemasok')

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                    <div>
                        <h4 class="card-title mb-1">List data pemasok untuk admin</h4>
                    </div>

                    <form id="supplier-search-form" class="m-0 d-flex">
                        <input
                            type="text"
                            id="supplier-search-input"
                            class="form-control"
                            placeholder="Cari nama supplier"
                            autocomplete="off"
                        >
                    </form>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-lg" id="supplier-table">
                        <thead>
                            <tr class="text-nowrap">
                                <th style="width: 64px;">No</th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="nama_pt_pengirim">
                                        Nama PT Pengirim
                                        <span class="ms-1 text-muted" data-sort-indicator="nama_pt_pengirim">↕</span>
                                    </button>
                                </th>
                                <th class="text-end">
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="invoice_count">
                                        Jumlah Hutang
                                        <span class="ms-1 text-muted" data-sort-indicator="invoice_count">↕</span>
                                    </button>
                                </th>
                                <th class="text-end">
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="outstanding_rupiah">
                                        Sisa Hutang
                                        <span class="ms-1 text-muted" data-sort-indicator="outstanding_rupiah">↕</span>
                                    </button>
                                </th>
                                <th class="text-end">
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="invoice_unpaid_count">
                                        Hutang Belum Lunas
                                        <span class="ms-1 text-muted" data-sort-indicator="invoice_unpaid_count">↕</span>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="last_shipment_date">
                                        Pengiriman Terakhir
                                        <span class="ms-1 text-muted" data-sort-indicator="last_shipment_date">↕</span>
                                    </button>
                                </th>
                                <th style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="supplier-table-body">
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Sedang memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
                    <small id="supplier-table-summary" class="text-muted">Total: -</small>
                    <div id="supplier-table-pagination"></div>
                </div>
            </div>
        </div>

        <div
            class="modal fade"
            id="supplier-edit-modal"
            tabindex="-1"
            aria-labelledby="supplier-edit-modal-title"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <form
                        id="supplier-edit-form"
                        method="POST"
                        action="{{ url('/admin/suppliers/__ID__') }}"
                    >
                        @csrf
                        @method('PUT')

                        <input
                            type="hidden"
                            name="supplier_id"
                            id="supplier-edit-supplier-id"
                            value="{{ old('supplier_id') }}"
                        >

                        <div class="modal-header border-0 pb-0 px-4 pt-4">
                            <div class="w-100">
                                <h3 class="modal-title fw-bold mb-1" id="supplier-edit-modal-title">Edit Pemasok</h3>
                                <p class="mb-0 text-muted fs-6" id="supplier-edit-modal-subtitle">
                                    Ubah nama PT pemasok langsung dari daftar.
                                </p>
                            </div>

                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>

                        <div class="modal-body px-4 pb-3 pt-3">
                            <div
                                id="supplier-edit-form-alert"
                                class="alert alert-danger d-none"
                                role="alert"
                            ></div>

                            <div class="form-group">
                                <label class="form-label" for="supplier-edit-nama-pt-pengirim">Nama PT Pengirim</label>
                                <input
                                    type="text"
                                    id="supplier-edit-nama-pt-pengirim"
                                    name="nama_pt_pengirim"
                                    class="form-control"
                                    value="{{ old('nama_pt_pengirim') }}"
                                    placeholder="Masukkan nama PT pengirim"
                                    autocomplete="off"
                                >
                                <div id="supplier-edit-nama-pt-error" class="invalid-feedback d-none"></div>
                            </div>
                        </div>

                        <div class="modal-footer border-0 px-4 pb-4 pt-0">
                            <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        window.supplierTableConfig = {
            endpoint: @json(route('admin.suppliers.table')),
            editBaseUrl: @json(url('/admin/suppliers')),
            updateUrlTemplate: @json(url('/admin/suppliers/__ID__')),
            oldSupplierId: @json(old('supplier_id')),
            oldNamaPtPengirim: @json(old('nama_pt_pengirim')),
            hasUpdateErrors: @json($errors->has('nama_pt_pengirim') || $errors->has('supplier')),
            updateErrorMessage: @json($errors->first('nama_pt_pengirim') ?: $errors->first('supplier'))
        };
    </script>
    <script src="{{ asset('assets/static/js/pages/admin-suppliers-table.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
