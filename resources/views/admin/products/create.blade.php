@extends('layouts.app')

@section('title', 'Tambah Produk')
@section('heading', 'Tambah Produk')
@section('back_url', route('admin.products.index'))

@section('content')
    <section class="section">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                            <div>
                                <h4 class="card-title mb-1">Form Produk Baru</h4>
                            </div>

                            @if (! empty($returnTo))
                                <div class="alert alert-light-primary border mb-0 py-2 px-3">
                                    <div class="fw-semibold mb-1">Mode kembali ke nota aktif</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card-body">
                        <form action="{{ route('admin.products.store') }}" method="post" id="product-master-form" data-product-master-form="1">
                            @csrf

                            <input
                                type="hidden"
                                name="return_to"
                                value="{{ old('return_to', $returnTo) }}"
                            >

                            <input
                                type="hidden"
                                name="return_label"
                                value="{{ old('return_label', $returnLabel ?? 'Kembali') }}"
                            >

                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group mb-4">
                                        <label for="kode_barang" class="form-label">Kode Barang</label>
                                        <input
                                            type="text"
                                            id="kode_barang"
                                            name="kode_barang"
                                            value="{{ old('kode_barang') }}"
                                            class="form-control @error('kode_barang') is-invalid @enderror"
                                            placeholder="Opsional"
                                        >
                                        @error('kode_barang')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group mb-4">
                                        <label for="nama_barang" class="form-label">Nama Barang</label>
                                        <input
                                            type="text"
                                            id="nama_barang"
                                            name="nama_barang"
                                            value="{{ old('nama_barang') }}"
                                            class="form-control @error('nama_barang') is-invalid @enderror"
                                            placeholder="Contoh: Ban Luar"
                                            required
                                        >
                                        @error('nama_barang')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group mb-4">
                                        <label for="merek" class="form-label">Merek</label>
                                        <input
                                            type="text"
                                            id="merek"
                                            name="merek"
                                            value="{{ old('merek') }}"
                                            class="form-control @error('merek') is-invalid @enderror"
                                            placeholder="Contoh: Federal"
                                            required
                                        >
                                        @error('merek')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group mb-4">
                                        <label for="ukuran" class="form-label">Ukuran</label>
                                        <input
                                            type="number"
                                            id="ukuran"
                                            name="ukuran"
                                            value="{{ old('ukuran') }}"
                                            class="form-control @error('ukuran') is-invalid @enderror"
                                            placeholder="Opsional"
                                            min="0"
                                            step="1"
                                        >
                                        @error('ukuran')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group mb-4" data-money-input-group>
                                        <label for="harga_jual_display" class="form-label">Harga Jual</label>

                                        <input
                                            type="hidden"
                                            id="harga_jual"
                                            name="harga_jual"
                                            value="{{ old('harga_jual') }}"
                                            data-money-raw
                                        >

                                        <input
                                            type="text"
                                            id="harga_jual_display"
                                            value="{{ old('harga_jual') }}"
                                            class="form-control @error('harga_jual') is-invalid @enderror"
                                            placeholder="Contoh: 15.000"
                                            inputmode="numeric"
                                            data-money-display
                                            required
                                        >

                                        @error('harga_jual')
                                            <div class="invalid-feedback d-block">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12 col-lg-6">
                                    <div class="form-group mb-4">
                                        <label for="reorder_point_qty" class="form-label">Mulai Perlu Restok (Reorder Point)</label>
                                        <input
                                            type="number"
                                            id="reorder_point_qty"
                                            name="reorder_point_qty"
                                            value="{{ old('reorder_point_qty', 5) }}"
                                            class="form-control @error('reorder_point_qty') is-invalid @enderror"
                                            placeholder="Contoh: 10"
                                            min="0"
                                            step="1"
                                        >
                                        @error('reorder_point_qty')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12 col-lg-6">
                                    <div class="form-group mb-4">
                                        <label for="critical_threshold_qty" class="form-label">Batas Stok Kritis</label>
                                        <input
                                            type="number"
                                            id="critical_threshold_qty"
                                            name="critical_threshold_qty"
                                            value="{{ old('critical_threshold_qty', 3) }}"
                                            class="form-control @error('critical_threshold_qty') is-invalid @enderror"
                                            placeholder="Contoh: 3"
                                            min="0"
                                            step="1"
                                        >
                                        @error('critical_threshold_qty')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="ui-form-actions">
                                <button type="submit" class="btn btn-primary">
                                    Simpan Produk
                                </button>

                                @if (! empty($returnTo))
                                    <a href="{{ $returnTo }}" class="btn btn-light-primary">
                                        {{ $returnLabel ?? 'Kembali' }}
                                    </a>
                                @else
                                    <a href="{{ route('admin.products.index') }}" class="btn btn-light-secondary">
                                        Batal
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/static/js/shared/admin-money-input.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/admin-product-master-form.js') }}?v={{ config('app.asset_version') }}"></script>
    <script>
        window.AdminMoneyInput?.bindBySelector(document);
    </script>
@endpush
