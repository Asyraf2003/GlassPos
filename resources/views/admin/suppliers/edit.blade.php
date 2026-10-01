@extends('layouts.app')

@section('title', 'Edit Supplier')
@section('heading', 'Edit Supplier')

@section('content')
    <section class="section">
        <div class="row">
            <div class="col-12 col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex flex-row justify-content-between align-items-center gap-2">
                            <div>
                                <h4 class="card-title mb-1">Edit Supplier</h4>
                                <p class="mb-0 text-muted">Ubah nama pemasok utama tanpa mengubah riwayat pengadaan.</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        @error('supplier')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror

                        <form action="{{ route('admin.suppliers.update', ['supplierId' => $supplier->id()]) }}" method="post">
                            @csrf
                            @method('PUT')

                            <div class="form-group mb-4">
                                <label for="nama_pt_pengirim" class="form-label">Nama PT Pengirim</label>
                                <input
                                    type="text"
                                    id="nama_pt_pengirim"
                                    name="nama_pt_pengirim"
                                    value="{{ old('nama_pt_pengirim', $supplier->namaPtPengirim()) }}"
                                    class="form-control @error('nama_pt_pengirim') is-invalid @enderror"
                                    placeholder="Contoh: PT Sumber Makmur"
                                    required
                                >
                                @error('nama_pt_pengirim')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @include('admin.suppliers.partials.bank_fields', ['prefix' => 'supplier-page', 'bankName' => $supplier->bankName(), 'bankAccountNumber' => $supplier->bankAccountNumber()])

                            <div class="d-flex justify-content-start gap-2">
                                <button type="submit" class="btn btn-primary">
                                    Simpan Perubahan
                                </button>
                                <a href="{{ route('admin.suppliers.index') }}" class="btn btn-light-secondary">
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