@extends('layouts.app')
@section('title', $pageTitle)
@section('heading', $pageTitle)

@section('content')
<section class="section">
    <style>
        .cashier-note-index {
            --note-card: var(--cashier-surface);
            --note-border: var(--cashier-border);
            --note-muted: var(--cashier-muted);
            --note-text: var(--cashier-text);
            --note-primary-soft: var(--cashier-accent-soft);
            --note-primary-border: var(--cashier-accent-border);
            --note-shadow: var(--cashier-shadow);
            max-width: 860px;
            margin: 0 auto;
        }

        .cashier-note-index-shell {
            display: grid;
            gap: 1rem;
        }

        .cashier-note-step-card {
            border: 1px solid var(--note-border);
            border-radius: 1rem;
            background: var(--note-card);
            box-shadow: var(--note-shadow);
            overflow: hidden;
        }

        .cashier-note-step-header {
            display: flex;
            align-items: flex-start;
            gap: .85rem;
            padding: 1rem 1rem .75rem;
            border-bottom: 1px solid var(--note-border);
        }

        .cashier-note-step-number {
            width: 2.25rem;
            height: 2.25rem;
            flex: 0 0 2.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            color: var(--cashier-accent);
            background: var(--note-primary-soft);
            border: 1px solid var(--note-primary-border);
            font-weight: 800;
        }

        .cashier-note-step-title {
            margin: 0;
            color: var(--note-text);
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.35;
        }

        .cashier-note-step-help {
            margin: .18rem 0 0;
            color: var(--note-muted);
            font-size: .9rem;
            line-height: 1.55;
        }

        .cashier-note-step-body {
            padding: 1rem;
        }

        .cashier-note-index .btn,
        .cashier-note-index .form-control {
            min-height: 2.75rem;
        }

        .cashier-note-index .form-control {
            border-radius: .85rem;
            border-color: var(--note-border);
        }

        .cashier-note-index .text-muted {
            color: var(--note-muted) !important;
        }

        .cashier-note-action-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
        }

        .cashier-note-action-grid .btn {
            border-radius: .85rem;
            font-weight: 800;
        }

        .cashier-note-table-wrap {
            border: 1px solid var(--note-border);
            border-radius: .85rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .cashier-note-table-wrap table {
            margin-bottom: 0;
            min-width: 900px;
            --bs-table-bg: var(--note-card);
            --bs-table-color: var(--note-text);
            --bs-table-border-color: var(--note-border);
        }

        .cashier-note-index .pagination {
            flex-wrap: wrap;
            gap: .25rem;
        }

        @media (max-width: 575.98px) {
            .cashier-note-index {
                max-width: none;
            }

            .cashier-note-action-grid {
                grid-template-columns: 1fr;
            }

            .cashier-note-step-header,
            .cashier-note-step-body {
                padding-inline: .9rem;
            }
        }
    </style>

    <div class="cashier-note-index">

        <div class="cashier-note-index-shell">
            <div class="cashier-note-step-card">
                <div class="cashier-note-step-header">
                    <span class="cashier-note-step-number">1</span>
                    <div>
                        <h5 class="cashier-note-step-title">Cari & Aksi</h5>
                        <p class="cashier-note-step-help">Daftar kasir memakai window hari ini dan kemarin.</p>
                    </div>
                </div>

                <div class="cashier-note-step-body">
                    <form class="mb-3" id="cashier-note-search-form">
                        <label for="cashier-note-search-input" class="form-label fw-semibold">Cari Nota</label>
                        <input
                            type="text"
                            id="cashier-note-search-input"
                            class="form-control"
                            placeholder="Cari customer, no telp, atau ringkasan line"
                            autocomplete="off"
                            value="{{ $filters['search'] }}"
                        >
                    </form>

                    <div class="cashier-note-action-grid">
                        <button type="button" id="open-cashier-note-filter" class="btn btn-outline-primary">
                            <i class="bi bi-funnel me-2"></i>
                            Filter
                        </button>

                        <a href="{{ route('cashier.notes.workspace.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>
                            Buat Nota
                        </a>
                    </div>
                </div>
            </div>

            <div class="cashier-note-step-card">
                <div class="cashier-note-step-header">
                    <span class="cashier-note-step-number">2</span>
                    <div>
                        <h5 class="cashier-note-step-title">Daftar Nota</h5>
                        <p class="cashier-note-step-help">Geser tabel ke samping di layar kecil untuk melihat nominal dan aksi.</p>
                    </div>
                </div>

                <div class="cashier-note-step-body">
                    <div class="cashier-note-table-wrap">
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
        </div>

        @include('cashier.notes.partials.filter-drawer')
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
