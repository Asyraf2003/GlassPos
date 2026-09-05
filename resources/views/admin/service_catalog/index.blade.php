@extends('layouts.app')

@section('title', 'Jasa')
@section('heading', 'Jasa')

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                    <div>
                        <h4 class="card-title mb-1">Dipakai untuk lookup kasir dan paket service</h4>
                    </div>

                    <div class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                        <form id="service-search-form" class="m-0 d-flex">
                            <input type="text" id="service-search-input" class="form-control py-2"
                                placeholder="Cari nama jasa" autocomplete="off">
                        </form>
                        <button type="button" id="open-service-filter" class="btn btn-primary py-2">Filter</button>
                        <a href="{{ route('admin.services.create') }}" class="btn btn-primary py-2 d-inline-flex align-items-center">
                            Tambah Jasa
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

                <div class="table-responsive">
                    <table class="table table-lg" id="service-table">
                        <thead>
                            <tr class="text-nowrap">
                                <th style="width: 64px;">No</th>
                                @foreach ([
                                    'name' => 'Nama Jasa',
                                    'normalized_name' => 'Nama Normal',
                                    'default_price_rupiah' => 'Default Harga',
                                    'is_active' => 'Status',
                                ] as $sortKey => $label)
                                    <th>
                                        <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="{{ $sortKey }}">
                                            {{ $label }} <span class="ms-1 text-muted" data-sort-indicator="{{ $sortKey }}">↕</span>
                                        </button>
                                    </th>
                                @endforeach
                                <th class="text-center" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="service-table-body">
                            <tr><td colspan="6" class="text-center text-muted py-4">Sedang memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
                    <small id="service-table-summary" class="text-muted">Menampilkan 0 sampai 0 dari 0 jasa</small>
                    <div id="service-table-pagination"></div>
                </div>
            </div>
        </div>

        @include('admin.service_catalog.partials.filter_drawer')

        <div
            class="modal fade"
            id="service-action-modal"
            tabindex="-1"
            aria-labelledby="service-action-modal-title"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0 px-4 pt-4">
                        <div class="w-100">
                            <h3 class="modal-title fw-bold mb-1" id="service-action-modal-title">Aksi Jasa</h3>
                            <p class="mb-0 text-muted fs-6" id="service-action-modal-subtitle">
                                Pilih tindakan untuk jasa.
                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body px-4 pb-4 pt-3">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <a href="#" id="service-action-edit-link" class="btn btn-outline-primary w-100 text-start py-3 px-4 h-100">
                                    <div class="fw-bold fs-5 mb-1">Edit Jasa</div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <form id="service-action-status-form" method="post" class="h-100">
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        id="service-action-status-button"
                                        class="btn btn-outline-warning w-100 text-start py-3 px-4 h-100"
                                    >
                                        <div class="fw-bold fs-5 mb-1" id="service-action-status-title">Nonaktifkan</div>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        window.AdminServiceTableConfig = {
            endpoint: @json(route('admin.services.table')),
        };
    </script>
    <script src="{{ asset('assets/static/js/pages/admin-services-table.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/admin-service-actions.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
