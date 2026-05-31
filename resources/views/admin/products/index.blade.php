@extends('layouts.app')

@section('title', 'Produk')
@section('heading', 'Produk')

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                    <div>
                        <h4 class="card-title mb-1">Tabel produk interaktif untuk admin</h4>
                    </div>

                    <div class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                        <form id="product-search-form" class="m-0 d-flex">
                            <input
                                type="text"
                                id="product-search-input"
                                class="form-control py-2" 
                                placeholder="Cari kode, nama, atau merek"
                                autocomplete="off"
                                style="min-height: 40px;"
                            >
                        </form>

                        <button type="button" id="open-product-filter" class="btn btn-primary py-2">
                            Filter
                        </button>

                        <a href="{{ route('admin.products.create') }}" class="btn btn-primary py-2 d-inline-flex align-items-center">
                            Tambah Produk
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-lg" id="product-table">
                        <thead>
                            <tr class="text-nowrap">
                                <th style="width: 64px;">No</th>
                                <th>Kode Barang</th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="nama_barang">
                                        Nama Barang
                                        <span class="ms-1 text-muted" data-sort-indicator="nama_barang">↕</span>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="merek">
                                        Merek
                                        <span class="ms-1 text-muted" data-sort-indicator="merek">↕</span>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="ukuran">
                                        Ukuran
                                        <span class="ms-1 text-muted" data-sort-indicator="ukuran">↕</span>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="harga_jual">
                                        Harga Jual
                                        <span class="ms-1 text-muted" data-sort-indicator="harga_jual">↕</span>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="stok_saat_ini">
                                        Stok Saat Ini
                                        <span class="ms-1 text-muted" data-sort-indicator="stok_saat_ini">↕</span>
                                    </button>
                                </th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="product-table-body">
                            <tr><td colspan="8" class="text-center text-muted py-4">Sedang memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
                    <small id="product-table-summary" class="text-muted">Total: -</small>
                    <div id="product-table-pagination"></div>
                </div>
            </div>
        </div>

        @include('admin.products.partials.filter_drawer')

        <div
            class="modal fade"
            id="product-action-modal"
            tabindex="-1"
            aria-labelledby="product-action-modal-title"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0 px-4 pt-4">
                        <div class="w-100">
                            <h3 class="modal-title fw-bold mb-1" id="product-action-modal-title">Aksi Produk</h3>
                            <p class="mb-0 text-muted fs-6" id="product-action-modal-subtitle">
                                Pilih tindakan untuk produk.
                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body px-4 pb-4 pt-3">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <a
                                    href="#"
                                    id="product-action-detail-link"
                                    class="btn btn-outline-primary w-100 text-start py-3 px-4"
                                >
                                    <div class="fw-bold fs-5 mb-1">Detail</div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <a
                                    href="#"
                                    id="product-action-edit-link"
                                    class="btn btn-outline-primary w-100 text-start py-3 px-4"
                                >
                                    <div class="fw-bold fs-5 mb-1">Edit identitas barang</div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <a
                                    href="#"
                                    id="product-action-stock-link"
                                    class="btn btn-outline-primary w-100 text-start py-3 px-4"
                                >
                                    <div class="fw-bold fs-5 mb-1">Ubah stok</div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <form id="product-action-delete-form" method="post" class="h-100">
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        id="product-action-delete-button"
                                        class="btn btn-outline-danger w-100 text-start py-3 px-4 h-100"
                                    >
                                        <div class="fw-bold fs-5 mb-1">Hapus Produk</div>
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
        window.productTableConfig = {
            endpoint: @json(route('admin.products.table')),
            showBaseUrl: @json(route('admin.products.show', ['productId' => '__ID__'])),
            editBaseUrl: @json(route('admin.products.edit', ['productId' => '__ID__'])),
            stockEditBaseUrl: @json(route('admin.products.stock.edit', ['productId' => '__ID__'])),
            deleteBaseUrl: @json(route('admin.products.delete', ['productId' => '__ID__'])),
        };
    </script>
    <script src="{{ asset('assets/static/js/pages/admin-products-table.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
