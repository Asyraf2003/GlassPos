@extends('layouts.app')
@section('title', $pageTitle)
@section('heading', $pageTitle)

@section('content')
<section class="section">
    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                <div>
                    <h4 class="card-title mb-1">Area kerja kasir untuk memilih nota hari ini dan kemarin, lalu lanjut ke panel kerja per line</h4>
                </div>

                <div class="d-flex flex-column flex-md-row gap-2">
                    <form class="d-flex flex-column gap-1" id="cashier-note-search-form">
                        <input
                            type="text"
                            id="cashier-note-search-input"
                            class="form-control"
                            placeholder="Cari customer, no telp, atau ringkasan line"
                            autocomplete="off"
                            value="{{ $filters['search'] }}"
                        >
                    </form>

                    <button type="button" id="open-cashier-note-filter" class="btn btn-primary">
                        Filter
                    </button>

                    <a href="{{ route('cashier.notes.workspace.create') }}" class="btn btn-primary">
                        Buat Nota
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-lg" id="cashier-note-table">
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
                    <tbody id="cashier-note-table-body">
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                Sedang menyiapkan daftar nota kasir...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
                <small id="cashier-note-table-summary" class="text-muted">
                    Memuat ringkasan daftar nota kasir...
                </small>
                <div id="cashier-note-table-pagination"></div>
            </div>
        </div>
    </div>

    @include('cashier.notes.partials.filter-drawer')
</section>

<script id="cashier-note-index-config" type="application/json">@json([
    'endpoint' => route('cashier.notes.table'),
    'filters' => $filters,
])</script>
@push('scripts')
<script src="{{ asset('assets/static/js/pages/cashier-note-index.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush

@endsection
