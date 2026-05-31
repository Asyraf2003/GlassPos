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
                            <th>Tanggal</th>
                            <th>Nota</th>
                            <th>Customer</th>
                            <th class="text-end">Grand Total</th>
                            <th class="text-end">Sudah Dibayar</th>
                            <th class="text-end">Sisa Tagihan</th>
                            <th>Ringkasan Line</th>
                            <th style="width: 120px;">Aksi</th>
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
