@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Pengeluaran Operasional')
@section('heading', 'Pengeluaran Operasional')

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                        <div>
                            <h4 class="card-title mb-1">Daftar Pengeluaran Operasional</h4>
                        </div>

                        <div class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                            <form id="expense-search-form" class="m-0 d-flex">
                                <input
                                    type="text"
                                    id="expense-search-input"
                                    class="form-control py-2"
                                    placeholder="Cari kategori, kode kategori, deskripsi, metode bayar"
                                    autocomplete="off"
                                    style="min-height: 40px;"
                                >
                            </form>

                            <button type="button" id="open-expense-filter" class="btn btn-primary py-2">
                                Filter
                            </button>

                            <a href="{{ route('admin.expenses.categories.index') }}" class="btn btn-primary py-2 d-inline-flex align-items-center">
                                Kelola Kategori
                            </a>

                            <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary py-2 d-inline-flex align-items-center">
                                Catat Pengeluaran
                            </a>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-body">
                @include('admin.expenses.partials.filter_drawer')

                <div class="table-responsive">
                    <table class="table table-lg" id="expense-table">
                        <thead>
                            <tr class="text-nowrap">
                                <th style="width: 64px;">No</th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="expense_date">
                                        Tanggal
                                        <span class="ms-1 text-muted" data-sort-indicator="expense_date">↕</span>
                                    </button>
                                </th>
                                <th>Kategori</th>
                                <th>Deskripsi</th>
                                <th class="text-end">
                                    <button
                                        type="button"
                                        class="btn btn-link p-0 text-decoration-none w-100 text-end"
                                        data-sort-by="amount_rupiah"
                                    >
                                        Nominal
                                        <span class="ms-1 text-muted" data-sort-indicator="amount_rupiah">↕</span>
                                    </button>
                                </th>
                                <th>Metode Bayar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="expense-table-body">
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Sedang memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
                    <small id="expense-table-summary" class="text-muted">Total: -</small>
                    <div id="expense-table-pagination"></div>
                </div>
            </div>
        </div>

        <div
            class="modal fade"
            id="expense-delete-modal"
            tabindex="-1"
            aria-labelledby="expense-delete-modal-title"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0 px-4 pt-4">
                        <div class="w-100">
                            <h3 class="modal-title fw-bold mb-1" id="expense-delete-modal-title">Hapus Pengeluaran</h3>
                            <p class="mb-0 text-muted fs-6" id="expense-delete-modal-subtitle">
                                Pilih pengeluaran yang ingin dihapus.
                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body px-4 pb-4 pt-3">
                        <p class="mb-4 text-muted">
                            Data pengeluaran akan disembunyikan dari daftar aktif dan tidak lagi dihitung di laporan.
                        </p>

                        <form id="expense-delete-form" method="post">
                            @csrf
                            @method('DELETE')

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-danger">Ya, hapus pengeluaran</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        window.expenseTableConfig = {
            endpoint: @json(route('admin.expenses.table')),
            deleteUrlTemplate: @json(route('admin.expenses.delete', ['expenseId' => '__EXPENSE_ID__'])),
            csrfToken: @json(csrf_token())
        };
    </script>
    <script src="{{ asset('assets/static/js/pages/admin-expenses-table.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
