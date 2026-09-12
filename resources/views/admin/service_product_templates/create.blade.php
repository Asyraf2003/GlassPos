@extends('layouts.app')

@section('title', 'Tambah Paket Service')
@section('heading', 'Tambah Paket Service')

@section('content')
    <section class="section">
        <div class="row">
            <div class="col-12 col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-1">Paket ini dipakai kasir untuk mengisi jasa dan produk dari paket aktif</h4>
                    </div>

                    <div class="card-body">
                        @include('admin.service_product_templates.partials.form', [
                            'action' => route('admin.service-product-templates.store'),
                            'method' => 'POST',
                            'submitLabel' => 'Simpan Paket Service',
                        ])
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/static/js/shared/product-display.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/admin-service-product-template.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/static/css/admin-lookup.css') }}?v={{ config('app.asset_version') }}">
@endpush
