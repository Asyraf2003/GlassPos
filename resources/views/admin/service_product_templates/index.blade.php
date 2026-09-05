@extends('layouts.app')

@section('title', 'Service')
@section('heading', 'Service')

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                    <div>
                        <h4 class="card-title mb-1">Produk memakai harga jual katalog. Harga jasa mengikuti master jasa. Total paket wajib minimal produk + jasa</h4>
                    </div>

                    <div class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                        <form id="package-search-form" class="m-0 d-flex">
                            <input type="text" id="package-search-input" class="form-control py-2"
                                placeholder="Cari kode, produk, atau jasa" autocomplete="off">
                        </form>
                        <button type="button" id="open-package-filter" class="btn btn-primary py-2">Filter</button>
                        <a href="{{ route('admin.service-product-templates.create') }}" class="btn btn-primary py-2 d-inline-flex align-items-center">
                            Tambah Paket
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @error('product_id')
                    <div class="alert alert-danger">{{ $message }}</div>
                @enderror

                <div class="table-responsive">
                    <table class="table table-lg" id="package-table">
                        <thead>
                            <tr class="text-nowrap">
                                <th style="width: 64px;">No</th>
                                @foreach ([
                                    'service_name' => 'Paket',
                                    'product_name' => 'Produk',
                                    'default_service_price_rupiah' => 'Jasa',
                                    'package_total' => 'Total',
                                    'is_active' => 'Status',
                                ] as $sortKey => $label)
                                    <th><button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="{{ $sortKey }}">
                                        {{ $label }} <span class="ms-1 text-muted" data-sort-indicator="{{ $sortKey }}">↕</span>
                                    </button></th>
                                @endforeach
                                <th class="text-center" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="package-table-body">
                            <tr><td colspan="7" class="text-center text-muted py-4">Sedang memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
                    <small id="package-table-summary" class="text-muted">Menampilkan 0 sampai 0 dari 0 paket service</small>
                    <div id="package-table-pagination"></div>
                </div>
            </div>
        </div>

        @include('admin.service_product_templates.partials.filter_drawer')

        <div
            class="modal fade"
            id="package-service-action-modal"
            tabindex="-1"
            aria-labelledby="package-service-action-modal-title"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0 px-4 pt-4">
                        <div class="w-100">
                            <h3 class="modal-title fw-bold mb-1" id="package-service-action-modal-title">Aksi Service</h3>
                            <p class="mb-0 text-muted fs-6" id="package-service-action-modal-subtitle">
                                Pilih tindakan untuk paket service.
                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body px-4 pb-4 pt-3">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <a href="#" id="package-service-action-detail-link" class="btn btn-outline-primary w-100 text-start py-3 px-4 h-100">
                                    <div class="fw-bold fs-5 mb-1">Detail</div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <a href="#" id="package-service-action-edit-link" class="btn btn-outline-primary w-100 text-start py-3 px-4 h-100">
                                    <div class="fw-bold fs-5 mb-1">Edit Paket</div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <a href="#" id="package-service-action-product-link" class="btn btn-outline-primary w-100 text-start py-3 px-4 h-100">
                                    <div class="fw-bold fs-5 mb-1">Detail Produk</div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <form id="package-service-action-status-form" method="post" class="m-0">
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        id="package-service-action-status-button"
                                        class="btn btn-outline-warning w-100 text-start py-3 px-4"
                                    >
                                        <div class="fw-bold fs-5 mb-1" id="package-service-action-status-title">Nonaktifkan</div>
                                    </button>
                                </form>
                            </div>

                            <div class="col-12 col-md-6">
                                <a href="#" id="package-service-action-service-link" class="btn btn-outline-primary w-100 text-start py-3 px-4 h-100">
                                    <div class="fw-bold fs-5 mb-1">Edit Jasa</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>window.AdminPackageTableConfig = { endpoint: @json(route('admin.service-product-templates.table')) };</script>
    <script src="{{ asset('assets/static/js/pages/admin-service-product-templates-table.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/admin-package-service-actions.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
