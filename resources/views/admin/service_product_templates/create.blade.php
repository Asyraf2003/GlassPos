@extends('layouts.app')

@section('title', 'Tambah Paket Service')
@section('heading', 'Tambah Paket Service')

@section('content')
    <section class="section">
        <div class="row">
            <div class="col-12 col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-1">Tambah Paket Service</h4>
                        <p class="text-muted mb-0">Paket ini dipakai kasir untuk autofill servis + produk dari template aktif.</p>
                    </div>

                    <div class="card-body">
                        @include('admin.service_product_templates.partials.form', [
                            'action' => route('admin.service-product-templates.store'),
                            'method' => 'POST',
                            'submitLabel' => 'Simpan Paket',
                        ])
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/static/js/shared/admin-money-input.js') }}?v={{ config('app.asset_version') }}"></script>
    <script>
        window.AdminMoneyInput?.bindBySelector(document);
    </script>
@endpush
