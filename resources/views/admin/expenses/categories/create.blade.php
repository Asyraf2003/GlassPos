@extends('layouts.app')

@section('title', 'Tambah Kategori Pengeluaran')
@section('heading', 'Tambah Kategori Pengeluaran')

@section('content')
    <section class="section">
        <div class="row">
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex flex-row justify-content-between align-items-center gap-2">
                            <div>
                                <h4 class="card-title mb-1">Tambah Kategori Pengeluaran</h4>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        @if (($source ?? '') === 'expense_create')
                            <div class="alert alert-light-primary mb-4">
                                Anda datang dari form pengeluaran. Setelah kategori disimpan, halaman akan kembali ke form pengeluaran dan kategori baru langsung dipilih.
                            </div>
                        @endif

                        <form id="expense-category-create-form" action="{{ route('admin.expenses.categories.store') }}" method="post">
                            @csrf
                            <input type="hidden" name="source" value="{{ $source ?? '' }}">

                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group mb-4">
                                        <label for="code" class="form-label">Kode</label>
                                        <input
                                            type="text"
                                            id="code"
                                            name="code"
                                            value="{{ old('code') }}"
                                            class="form-control @error('code') is-invalid @enderror"
                                            placeholder="Contoh: EXP-ELEC"
                                            required
                                        >
                                        @error('code')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group mb-4">
                                        <label for="name" class="form-label">Nama</label>
                                        <input
                                            type="text"
                                            id="name"
                                            name="name"
                                            value="{{ old('name', $prefillKeyword ?? '') }}"
                                            class="form-control @error('name') is-invalid @enderror"
                                            placeholder="Contoh: Listrik Bengkel"
                                            required
                                        >
                                        @error('name')
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
                                            placeholder="Opsional"
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
                                    Simpan Kategori
                                </button>
                                <a href="{{ $backUrl ?? route('admin.expenses.categories.index') }}" class="btn btn-light-secondary">
                                    Batal
                                </a>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/static/js/pages/admin-expense-category-create.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
