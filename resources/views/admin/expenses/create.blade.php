@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Catat Pengeluaran Operasional')
@section('heading', 'Catat Pengeluaran Operasional')

@section('content')
    <section class="section">
        <div class="row">
            <div class="col-12 col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex flex-row justify-content-between align-items-center gap-2">
                            <div>
                                <h4 class="card-title mb-1">Catat Pengeluaran Operasional</h4>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <form id="expense-create-form" action="{{ route('admin.expenses.store') }}" method="post">
                            @csrf

                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group mb-4">
                                        <label class="form-label" for="expense-category-search-input">Kategori</label>

                                        <div id="expense-category-search-wrap" class="d-none">
                                            <input
                                                type="text"
                                                id="expense-category-search-input"
                                                class="form-control"
                                                placeholder="Ketik minimal 2 karakter nama atau kode kategori"
                                                autocomplete="off"
                                            >
                                            <small id="expense-category-search-helper" class="text-muted d-block mt-2">
                                                Ketik minimal 2 karakter untuk cari kategori. Enter pilih hasil. Jika tidak ada, Enter ke form kategori baru.
                                            </small>
                                            <div id="expense-category-search-results" class="list-group mt-2 d-none"></div>
                                        </div>

                                        <div id="expense-category-select-wrap">
                                            <select
                                                id="category_id"
                                                name="category_id"
                                                class="form-select @error('category_id') is-invalid @enderror"
                                                required
                                            >
                                                <option value="">Pilih kategori</option>
                                                @foreach ($categoryOptions as $option)
                                                    <option value="{{ $option['id'] }}" @selected(($selectedCategoryId ?? '') === $option['id'])>
                                                        {{ $option['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        @error('category_id')
                                            <div class="invalid-feedback d-block">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="form-group mb-4">
                                        <label for="expense_date" class="form-label">Tanggal</label>
                                        <input
                                            type="date"
                                            data-ui-date="single"
                                            id="expense_date"
                                            name="expense_date"
                                            value="{{ old('expense_date', now()->format('Y-m-d')) }}"
                                            class="form-control @error('expense_date') is-invalid @enderror"
                                            required
                                        >
                                        @error('expense_date')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="form-group mb-4" data-money-input-group>
                                        <label for="amount_rupiah_display" class="form-label">Nominal</label>

                                        <input
                                            type="hidden"
                                            id="amount_rupiah"
                                            name="amount_rupiah"
                                            value="{{ old('amount_rupiah') }}"
                                            data-money-raw
                                        >

                                        <input
                                            type="text"
                                            id="amount_rupiah_display"
                                            value="{{ old('amount_rupiah') }}"
                                            class="form-control @error('amount_rupiah') is-invalid @enderror"
                                            placeholder="Contoh: 150.000"
                                            inputmode="numeric"
                                            data-money-display
                                            required
                                        >

                                        @error('amount_rupiah')
                                            <div class="invalid-feedback d-block">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="form-group mb-4">
                                        <label for="payment_method" class="form-label">Metode Bayar</label>
                                        <select
                                            id="payment_method"
                                            name="payment_method"
                                            class="form-select @error('payment_method') is-invalid @enderror"
                                            required
                                        >
                                            <option value="">Pilih metode bayar</option>
                                            <option value="cash" @selected(old('payment_method') === 'cash')>cash</option>
                                            <option value="tf" @selected(old('payment_method') === 'tf')>tf</option>
                                        </select>
                                        @error('payment_method')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group mb-4">
                                        <label for="description" class="form-label">Deskripsi</label>
                                        <textarea
                                            id="description"
                                            name="description"
                                            class="form-control @error('description') is-invalid @enderror"
                                            rows="4"
                                            placeholder="Contoh: Bayar token listrik workshop"
                                            required
                                        >{{ old('description') }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-start gap-2">
                                <button type="submit" class="btn btn-primary">
                                    Simpan Pengeluaran
                                </button>
                                <a href="{{ route('admin.expenses.index') }}" class="btn btn-light-secondary">
                                    Batal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script id="expense-create-config" type="application/json">{!!
            json_encode([
                'categoryOptions' => $categoryOptions,
                'selectedCategoryId' => $selectedCategoryId ?? '',
                'createCategoryBaseUrl' => $createCategoryUrl ?? route('admin.expenses.categories.create'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
        !!}</script>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/static/js/shared/admin-money-input.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/admin-expense-create/category-search.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/admin-expense-create/flow.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/admin-expense-create/boot.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
