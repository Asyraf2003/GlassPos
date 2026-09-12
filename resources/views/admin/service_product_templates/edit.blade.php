@extends('layouts.app')

@section('title', 'Edit Paket Service')
@section('heading', 'Edit Paket Service')

@section('content')
    <section class="section">
        <div class="row">
            <div class="col-12 col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-1">Perubahan hanya berlaku untuk lookup berikutnya. Nota historis tidak diubah</h4>
                    </div>

                    <div class="card-body">
                        @include('admin.service_product_templates.partials.form', [
                            'action' => route('admin.service-product-templates.update', ['templateId' => $template['id']]),
                            'method' => 'PUT',
                            'submitLabel' => 'Simpan Paket Service',
                        ])
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/static/js/pages/admin-service-product-template.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/static/css/admin-lookup.css') }}?v={{ config('app.asset_version') }}">
@endpush
