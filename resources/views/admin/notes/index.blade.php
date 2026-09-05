@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', $pageTitle)
@section('heading', $pageTitle)

@section('content')
<section class="section">
    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                <div>
                    <h4 class="card-title mb-1">Daftar Nota Admin</h4>
                </div>

                <div class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                    <form id="admin-note-search-form" class="m-0 d-flex">
                        <input
                            type="text"
                            id="admin-note-search-input"
                            class="form-control py-2"
                            placeholder="Cari customer, no telp, atau ringkasan line"
                            autocomplete="off"
                            value="{{ $filters['search'] }}"
                            style="min-height: 40px;"
                        >
                    </form>

                    <button type="button" id="open-admin-note-filter" class="btn btn-primary py-2">
                        Filter
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-lg" id="admin-note-table">
                    <thead>
                        <tr class="text-nowrap">
                            <th style="width: 64px;">No</th>
                            <th>
                                <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="created_at">
                                    Dibuat
                                    <span class="ms-1 text-muted" data-sort-indicator="created_at">↕</span>
                                </button>
                            </th>
                            <th>
                                <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="note_number">
                                    Nota
                                    <span class="ms-1 text-muted" data-sort-indicator="note_number">↕</span>
                                </button>
                            </th>
                            <th>
                                <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="customer_name">
                                    Pelanggan
                                    <span class="ms-1 text-muted" data-sort-indicator="customer_name">↕</span>
                                </button>
                            </th>
                            <th class="text-end">
                                <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="total_rupiah">
                                    Total Nota
                                    <span class="ms-1 text-muted" data-sort-indicator="total_rupiah">↕</span>
                                </button>
                            </th>
                            <th class="text-end">
                                <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="net_paid_rupiah">
                                    Sudah Dibayar
                                    <span class="ms-1 text-muted" data-sort-indicator="net_paid_rupiah">↕</span>
                                </button>
                            </th>
                            <th class="text-end">
                                <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="outstanding_rupiah">
                                    Sisa Tagihan
                                    <span class="ms-1 text-muted" data-sort-indicator="outstanding_rupiah">↕</span>
                                </button>
                            </th>
                            <th>Ringkasan Rincian</th>
                            <th class="text-center" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="admin-note-table-body">
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                Sedang menyiapkan daftar nota admin...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
                <small id="admin-note-table-summary" class="text-muted">
                    Memuat ringkasan daftar nota admin...
                </small>
                <div id="admin-note-table-pagination"></div>
            </div>
        </div>
    </div>

    @include('admin.notes.partials.filter-drawer')
</section>

<script id="admin-note-index-config" type="application/json">@json([
    'endpoint' => route('admin.notes.table'),
    'filters' => $filters,
])</script>
@push('scripts')
<script src="{{ asset('assets/static/js/pages/admin-note-index.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush

@endsection
