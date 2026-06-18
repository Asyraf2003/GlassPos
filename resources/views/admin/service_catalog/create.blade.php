@extends('layouts.app')

@section('title', 'Tambah Master Jasa')
@section('heading', 'Tambah Master Jasa')

@section('content')
    <section class="section">
        <div class="row">
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-1">Tambah Master Jasa</h4>
                    </div>

                    <div class="card-body">
                        @include('admin.service_catalog.partials.form', [
                            'action' => route('admin.services.store'),
                            'method' => 'POST',
                            'submitLabel' => 'Simpan Jasa',
                        ])
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
