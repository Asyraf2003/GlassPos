@extends('layouts.app')
@section('title', $pageTitle)
@section('heading', $pageTitle)

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/static/css/cashier-note-history.css') }}?v={{ config('app.asset_version') }}">
@endpush

@section('content')
<section class="section">
    <div class="cashier-note-history">
        <div class="cashier-note-history-toolbar">
            <form id="cashier-note-search-form" role="search">
                <label class="visually-hidden" for="cashier-note-search-input">Cari nota</label>
                <div class="cashier-note-history-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input
                        type="search"
                        id="cashier-note-search-input"
                        class="form-control"
                        placeholder="Cari pelanggan, telepon, atau nomor nota"
                        autocomplete="off"
                        enterkeyhint="search"
                        value="{{ $filters['search'] }}"
                    >
                </div>
            </form>

            <a href="{{ route('cashier.notes.workspace.create') }}" class="btn btn-primary cashier-note-create-action">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                Buat Nota
            </a>
        </div>

        <nav class="cashier-note-focus" aria-label="Fokus riwayat nota">
            <button type="button" data-history-bucket="unfinished" aria-pressed="true">Belum Selesai</button>
            <button type="button" data-history-bucket="completed" aria-pressed="false">Selesai</button>
        </nav>

        <div id="cashier-note-list" class="cashier-note-list" aria-live="polite" aria-busy="true">
            <div class="cashier-note-list-state">Memuat riwayat nota...</div>
        </div>

        <footer class="cashier-note-history-footer">
            <small id="cashier-note-table-summary" class="text-muted">Memuat ringkasan...</small>
            <div id="cashier-note-table-pagination"></div>
        </footer>
    </div>
</section>

<script id="cashier-note-index-config" type="application/json">@json([
    'endpoint' => route('cashier.notes.table'),
    'filters' => $filters,
])</script>
@push('scripts')
<script src="{{ asset('assets/static/js/pages/cashier-note-index.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
@endsection
